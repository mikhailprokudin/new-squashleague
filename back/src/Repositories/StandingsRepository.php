<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StandingsRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function getStandings(): array
    {
        $teamsStmt = $this->db->query('SELECT id, name, slug FROM teams ORDER BY id');
        $teams = $teamsStmt->fetchAll();

        $playersStmt = $this->db->query(
            'SELECT
                p.id,
                p.team_id,
                p.division,
                p.name,
                COALESCE(stats.points, 0) AS points,
                COALESCE(stats.matches_played, 0) AS matches_played
             FROM players p
             LEFT JOIN (
                SELECT player_id, SUM(points) AS points, COUNT(*) AS matches_played
                FROM (
                    SELECT player1_id AS player_id, points_player1 AS points FROM matches
                    UNION ALL
                    SELECT player2_id AS player_id, points_player2 AS points FROM matches
                ) AS all_points
                GROUP BY player_id
             ) AS stats ON stats.player_id = p.id
             WHERE p.is_active = 1
             ORDER BY p.team_id, p.division, points DESC, p.name'
        );
        $players = $playersStmt->fetchAll();

        $byTeam = [];
        foreach ($teams as $team) {
            $byTeam[(int) $team['id']] = [
                'id' => (int) $team['id'],
                'name' => $team['name'],
                'slug' => $team['slug'],
                'players' => [],
            ];
        }

        foreach ($players as $player) {
            $teamId = (int) $player['team_id'];
            if (!isset($byTeam[$teamId])) {
                continue;
            }
            $byTeam[$teamId]['players'][] = [
                'id' => (int) $player['id'],
                'name' => $player['name'],
                'division' => $player['division'],
                'points' => (float) $player['points'],
                'matches_played' => (int) $player['matches_played'],
            ];
        }

        return array_values($byTeam);
    }
}
