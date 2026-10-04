<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class MatchRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function countBetween(int $playerA, int $playerB): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM matches
             WHERE (player1_id = :a AND player2_id = :b)
                OR (player1_id = :b2 AND player2_id = :a2)'
        );
        $stmt->execute([
            'a' => $playerA,
            'b' => $playerB,
            'a2' => $playerA,
            'b2' => $playerB,
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, int> opponentId => matchesPlayed
     */
    public function matchCountsForPlayer(int $playerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                CASE WHEN player1_id = :id THEN player2_id ELSE player1_id END AS opponent_id,
                COUNT(*) AS cnt
             FROM matches
             WHERE player1_id = :id2 OR player2_id = :id3
             GROUP BY opponent_id'
        );
        $stmt->execute([
            'id' => $playerId,
            'id2' => $playerId,
            'id3' => $playerId,
        ]);

        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[(int) $row['opponent_id']] = (int) $row['cnt'];
        }

        return $result;
    }

    public function create(
        int $player1Id,
        int $player2Id,
        int $winnerId,
        string $score,
        float $pointsPlayer1,
        float $pointsPlayer2
    ): array {
        $stmt = $this->db->prepare(
            'INSERT INTO matches
                (player1_id, player2_id, winner_id, score, points_player1, points_player2)
             VALUES
                (:player1_id, :player2_id, :winner_id, :score, :points_player1, :points_player2)'
        );
        $stmt->execute([
            'player1_id' => $player1Id,
            'player2_id' => $player2Id,
            'winner_id' => $winnerId,
            'score' => $score,
            'points_player1' => $pointsPlayer1,
            'points_player2' => $pointsPlayer2,
        ]);

        return $this->findById((int) $this->db->lastInsertId()) ?? [];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*,
                    p1.name AS player1_name, p2.name AS player2_name, w.name AS winner_name,
                    t1.name AS player1_team_name, t2.name AS player2_team_name
             FROM matches m
             JOIN players p1 ON p1.id = m.player1_id
             JOIN players p2 ON p2.id = m.player2_id
             JOIN players w ON w.id = m.winner_id
             JOIN teams t1 ON t1.id = p1.team_id
             JOIN teams t2 ON t2.id = p2.team_id
             WHERE m.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->normalize($row) : null;
    }

    public function listAll(): array
    {
        $stmt = $this->db->query(
            'SELECT m.*,
                    p1.name AS player1_name, p2.name AS player2_name, w.name AS winner_name,
                    t1.name AS player1_team_name, t2.name AS player2_team_name,
                    p1.division AS player1_division, p2.division AS player2_division
             FROM matches m
             JOIN players p1 ON p1.id = m.player1_id
             JOIN players p2 ON p2.id = m.player2_id
             JOIN players w ON w.id = m.winner_id
             JOIN teams t1 ON t1.id = p1.team_id
             JOIN teams t2 ON t2.id = p2.team_id
             ORDER BY m.created_at DESC, m.id DESC'
        );

        return array_map([$this, 'normalize'], $stmt->fetchAll());
    }

    public function listForPlayer(int $playerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT m.*,
                    p1.name AS player1_name, p2.name AS player2_name, w.name AS winner_name,
                    t1.name AS player1_team_name, t2.name AS player2_team_name,
                    p1.division AS player1_division, p2.division AS player2_division
             FROM matches m
             JOIN players p1 ON p1.id = m.player1_id
             JOIN players p2 ON p2.id = m.player2_id
             JOIN players w ON w.id = m.winner_id
             JOIN teams t1 ON t1.id = p1.team_id
             JOIN teams t2 ON t2.id = p2.team_id
             WHERE m.player1_id = :id OR m.player2_id = :id2
             ORDER BY m.created_at DESC, m.id DESC'
        );
        $stmt->execute(['id' => $playerId, 'id2' => $playerId]);

        return array_map([$this, 'normalize'], $stmt->fetchAll());
    }

    private function normalize(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['player1_id'] = (int) $row['player1_id'];
        $row['player2_id'] = (int) $row['player2_id'];
        $row['winner_id'] = (int) $row['winner_id'];
        $row['points_player1'] = (float) $row['points_player1'];
        $row['points_player2'] = (float) $row['points_player2'];

        return $row;
    }
}
