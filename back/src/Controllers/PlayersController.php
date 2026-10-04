<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Repositories\MatchRepository;
use App\Repositories\PlayerRepository;
use App\Response;
use App\ScoringService;
use PDO;

final class PlayersController
{
    private PlayerRepository $players;
    private MatchRepository $matches;
    private ScoringService $scoring;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->players = new PlayerRepository($this->db);
        $this->matches = new MatchRepository($this->db);
        $this->scoring = new ScoringService();
    }

    public function stats(int $id): void
    {
        $player = $this->players->findById($id);
        if ($player === null) {
            Response::error('Player not found', 404);
            return;
        }

        $rawMatches = $this->matches->listForPlayer($id);
        $playedMatches = [];
        $points = 0.0;
        $maxPossiblePoints = 0.0;

        foreach ($rawMatches as $match) {
            $isPlayer1 = (int) $match['player1_id'] === $id;
            $earned = $isPlayer1
                ? (float) $match['points_player1']
                : (float) $match['points_player2'];
            $points += $earned;

            $opponentId = $isPlayer1 ? (int) $match['player2_id'] : (int) $match['player1_id'];
            $opponentName = $isPlayer1 ? $match['player2_name'] : $match['player1_name'];
            $opponentTeam = $isPlayer1 ? $match['player2_team_name'] : $match['player1_team_name'];
            $opponentDivision = $isPlayer1
                ? (string) $match['player2_division']
                : (string) $match['player1_division'];

            $maxPossiblePoints += $this->scoring->maxWinnerPointsForMatch(
                (string) $player['division'],
                $opponentDivision
            );

            $won = (int) $match['winner_id'] === $id;
            $displayScore = $won
                ? (string) $match['score']
                : $this->invertScore((string) $match['score']);

            $playedMatches[] = [
                'id' => (int) $match['id'],
                'opponent_id' => $opponentId,
                'opponent_name' => $opponentName,
                'opponent_team_name' => $opponentTeam,
                'opponent_division' => $opponentDivision,
                'score' => $displayScore,
                'won' => $won,
                'points_earned' => $earned,
                'created_at' => $match['created_at'] ?? null,
            ];
        }

        $matchesPlayed = count($playedMatches);
        $usefulness = $matchesPlayed === 0 || $maxPossiblePoints <= 0
            ? null
            : round(($points / $maxPossiblePoints) * 100, 1);

        $counts = $this->matches->matchCountsForPlayer($id);
        $unplayed = [];
        foreach ($this->players->listActive() as $candidate) {
            $candidateId = (int) $candidate['id'];
            if ($candidateId === $id) {
                continue;
            }
            if ((int) $candidate['team_id'] === (int) $player['team_id']) {
                continue;
            }

            $playedVs = $counts[$candidateId] ?? 0;
            $max = $this->scoring->maxMatchesForPair(
                (string) $player['division'],
                (string) $candidate['division']
            );
            $remaining = $max - $playedVs;
            if ($remaining <= 0) {
                continue;
            }

            for ($i = 0; $i < $remaining; $i++) {
                $unplayed[] = [
                    'opponent_id' => $candidateId,
                    'opponent_name' => $candidate['name'],
                    'opponent_team_name' => $candidate['team_name'],
                    'opponent_division' => $candidate['division'],
                ];
            }
        }

        usort($unplayed, static function (array $a, array $b): int {
            return [$a['opponent_team_name'], $a['opponent_division'], $a['opponent_name']]
                <=> [$b['opponent_team_name'], $b['opponent_division'], $b['opponent_name']];
        });

        Response::json([
            'player' => $this->formatPlayer($player),
            'points' => $points,
            'matches_played' => $matchesPlayed,
            'max_possible_points' => $maxPossiblePoints,
            'usefulness' => $usefulness,
            'played_matches' => $playedMatches,
            'unplayed_matches' => $unplayed,
        ]);
    }

    private function invertScore(string $score): string
    {
        return match ($score) {
            '3-0' => '0-3',
            '3-1' => '1-3',
            '3-2' => '2-3',
            default => $score,
        };
    }

    public function index(): void
    {
        $teamId = isset($_GET['team_id']) ? (int) $_GET['team_id'] : null;
        $division = isset($_GET['division']) ? (string) $_GET['division'] : null;

        if ($division !== null && !in_array($division, ['red', 'yellow'], true)) {
            Response::error('division must be red or yellow', 422);
            return;
        }

        $list = $this->players->listActive($teamId ?: null, $division);
        Response::json(['players' => array_map([$this, 'formatPlayer'], $list)]);
    }

    public function store(): void
    {
        $body = $this->jsonBody();
        $teamId = (int) ($body['team_id'] ?? 0);
        $division = (string) ($body['division'] ?? '');
        $name = trim((string) ($body['name'] ?? ''));

        if ($teamId <= 0 || $name === '' || !in_array($division, ['red', 'yellow'], true)) {
            Response::error('Required: team_id, division (red|yellow), name', 422);
            return;
        }

        if (!$this->teamExists($teamId)) {
            Response::error('Team not found', 404);
            return;
        }

        $player = $this->players->create($teamId, $division, $name);
        Response::json(['player' => $this->formatPlayer($player)], 201);
    }

    public function update(int $id): void
    {
        $existing = $this->players->findById($id, false);
        if ($existing === null) {
            Response::error('Player not found', 404);
            return;
        }

        $body = $this->jsonBody();
        $fields = [];

        if (array_key_exists('name', $body)) {
            $name = trim((string) $body['name']);
            if ($name === '') {
                Response::error('name cannot be empty', 422);
                return;
            }
            $fields['name'] = $name;
        }

        if (array_key_exists('division', $body)) {
            $division = (string) $body['division'];
            if (!in_array($division, ['red', 'yellow'], true)) {
                Response::error('division must be red or yellow', 422);
                return;
            }
            $fields['division'] = $division;
        }

        if (array_key_exists('team_id', $body)) {
            $teamId = (int) $body['team_id'];
            if (!$this->teamExists($teamId)) {
                Response::error('Team not found', 404);
                return;
            }
            $fields['team_id'] = $teamId;
        }

        // Soft-reactivate when replacing a deleted slot
        if (array_key_exists('is_active', $body)) {
            $stmt = $this->db->prepare('UPDATE players SET is_active = :active WHERE id = :id');
            $stmt->execute([
                'active' => (int) ((bool) $body['is_active']),
                'id' => $id,
            ]);
        }

        $player = $this->players->update($id, $fields);
        Response::json(['player' => $this->formatPlayer($player ?? [])]);
    }

    public function destroy(int $id): void
    {
        if ($this->players->findById($id, false) === null) {
            Response::error('Player not found', 404);
            return;
        }

        $this->players->softDelete($id);
        Response::json(['ok' => true]);
    }

    private function teamExists(int $teamId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM teams WHERE id = :id');
        $stmt->execute(['id' => $teamId]);

        return (bool) $stmt->fetchColumn();
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function formatPlayer(array $player): array
    {
        return [
            'id' => (int) ($player['id'] ?? 0),
            'team_id' => (int) ($player['team_id'] ?? 0),
            'team_name' => $player['team_name'] ?? null,
            'team_slug' => $player['team_slug'] ?? null,
            'division' => $player['division'] ?? null,
            'name' => $player['name'] ?? null,
            'is_active' => isset($player['is_active']) ? (bool) $player['is_active'] : true,
        ];
    }
}
