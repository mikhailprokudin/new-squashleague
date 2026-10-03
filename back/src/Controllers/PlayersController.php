<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Repositories\PlayerRepository;
use App\Response;
use PDO;

final class PlayersController
{
    private PlayerRepository $players;
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
        $this->players = new PlayerRepository($this->db);
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
