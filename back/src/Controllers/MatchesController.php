<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Repositories\MatchRepository;
use App\Repositories\PlayerRepository;
use App\Response;
use App\ScoringService;
use InvalidArgumentException;

final class MatchesController
{
    private PlayerRepository $players;
    private MatchRepository $matches;
    private ScoringService $scoring;

    public function __construct()
    {
        $db = Database::connection();
        $this->players = new PlayerRepository($db);
        $this->matches = new MatchRepository($db);
        $this->scoring = new ScoringService();
    }

    public function index(): void
    {
        Response::json(['matches' => $this->matches->listAll()]);
    }

    public function opponents(): void
    {
        $playerId = (int) ($_GET['player_id'] ?? 0);
        if ($playerId <= 0) {
            Response::error('player_id is required', 422);
            return;
        }

        $player = $this->players->findById($playerId);
        if ($player === null) {
            Response::error('Player not found', 404);
            return;
        }

        $all = $this->players->listActive();
        $counts = $this->matches->matchCountsForPlayer($playerId);
        $opponents = [];

        foreach ($all as $candidate) {
            if ((int) $candidate['id'] === $playerId) {
                continue;
            }
            if ((int) $candidate['team_id'] === (int) $player['team_id']) {
                continue;
            }

            $played = $counts[(int) $candidate['id']] ?? 0;
            $max = $this->scoring->maxMatchesForPair(
                (string) $player['division'],
                (string) $candidate['division']
            );

            if ($played >= $max) {
                continue;
            }

            $opponents[] = [
                'id' => (int) $candidate['id'],
                'name' => $candidate['name'],
                'team_id' => (int) $candidate['team_id'],
                'team_name' => $candidate['team_name'],
                'team_slug' => $candidate['team_slug'],
                'division' => $candidate['division'],
                'matches_played' => $played,
                'matches_remaining' => $max - $played,
            ];
        }

        usort($opponents, static function (array $a, array $b): int {
            return [$a['team_id'], $a['division'], $a['name']]
                <=> [$b['team_id'], $b['division'], $b['name']];
        });

        Response::json([
            'player' => [
                'id' => (int) $player['id'],
                'name' => $player['name'],
                'team_id' => (int) $player['team_id'],
                'division' => $player['division'],
            ],
            'opponents' => $opponents,
        ]);
    }

    public function store(): void
    {
        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);
        if (!is_array($body)) {
            Response::error('Invalid JSON body', 400);
            return;
        }

        $player1Id = (int) ($body['player1_id'] ?? 0);
        $player2Id = (int) ($body['player2_id'] ?? 0);
        $scoreInput = (string) ($body['score'] ?? '');

        if ($player1Id <= 0 || $player2Id <= 0 || $scoreInput === '') {
            Response::error('Required: player1_id, player2_id, score', 422);
            return;
        }

        if ($player1Id === $player2Id) {
            Response::error('Players must be different', 422);
            return;
        }

        $winnerIdInput = (int) ($body['winner_id'] ?? 0);
        $resolved = $this->resolveScore($scoreInput, $player1Id, $player2Id, $winnerIdInput);
        if ($resolved === null) {
            Response::error('Invalid score. Allowed: 3-0, 3-1, 3-2, 0-3, 1-3, 2-3', 422);
            return;
        }

        $winnerId = $resolved['winner_id'];
        $score = $resolved['score'];

        $player1 = $this->players->findById($player1Id);
        $player2 = $this->players->findById($player2Id);

        if ($player1 === null || $player2 === null) {
            Response::error('One or both players not found or inactive', 404);
            return;
        }

        if ((int) $player1['team_id'] === (int) $player2['team_id']) {
            Response::error('Players from the same team cannot play each other', 422);
            return;
        }

        $max = $this->scoring->maxMatchesForPair(
            (string) $player1['division'],
            (string) $player2['division']
        );
        $played = $this->matches->countBetween($player1Id, $player2Id);
        if ($played >= $max) {
            Response::error('Match limit reached for this pair', 422, [
                'matches_played' => $played,
                'max_matches' => $max,
            ]);
            return;
        }

        $loserId = $winnerId === $player1Id ? $player2Id : $player1Id;
        $winner = $winnerId === $player1Id ? $player1 : $player2;
        $loser = $loserId === $player1Id ? $player1 : $player2;

        try {
            $points = $this->scoring->calculate(
                (string) $winner['division'],
                (string) $loser['division'],
                $score
            );
        } catch (InvalidArgumentException $e) {
            Response::error($e->getMessage(), 422);
            return;
        }

        $pointsPlayer1 = $winnerId === $player1Id ? $points['winner'] : $points['loser'];
        $pointsPlayer2 = $winnerId === $player2Id ? $points['winner'] : $points['loser'];

        $match = $this->matches->create(
            $player1Id,
            $player2Id,
            $winnerId,
            $score,
            $pointsPlayer1,
            $pointsPlayer2
        );

        Response::json([
            'match' => $match,
            'scoring' => [
                'match_type' => $points['match_type'],
                'points_winner' => $points['winner'],
                'points_loser' => $points['loser'],
            ],
        ], 201);
    }

    /**
     * Preferred: score from player1's perspective (3-x or x-3).
     * Also accepted: normalized 3-x + winner_id (winner may be player2).
     * Stored score is always normalized to 3-0 / 3-1 / 3-2.
     *
     * @return array{winner_id: int, score: string}|null
     */
    private function resolveScore(
        string $score,
        int $player1Id,
        int $player2Id,
        int $winnerIdInput = 0
    ): ?array {
        $fromPlayer1 = [
            '3-0' => ['winner_id' => $player1Id, 'score' => '3-0'],
            '3-1' => ['winner_id' => $player1Id, 'score' => '3-1'],
            '3-2' => ['winner_id' => $player1Id, 'score' => '3-2'],
            '0-3' => ['winner_id' => $player2Id, 'score' => '3-0'],
            '1-3' => ['winner_id' => $player2Id, 'score' => '3-1'],
            '2-3' => ['winner_id' => $player2Id, 'score' => '3-2'],
        ];

        if (!isset($fromPlayer1[$score])) {
            return null;
        }

        $resolved = $fromPlayer1[$score];

        // Compat: client sent normalized 3-x plus explicit winner_id for player2
        if (
            $winnerIdInput > 0
            && in_array($score, ['3-0', '3-1', '3-2'], true)
            && in_array($winnerIdInput, [$player1Id, $player2Id], true)
            && $winnerIdInput !== $resolved['winner_id']
        ) {
            return [
                'winner_id' => $winnerIdInput,
                'score' => $score,
            ];
        }

        if ($winnerIdInput > 0 && $winnerIdInput !== $resolved['winner_id']) {
            return null;
        }

        return $resolved;
    }
}

