<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PlayerRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findById(int $id, bool $activeOnly = true): ?array
    {
        $sql = 'SELECT p.*, t.name AS team_name, t.slug AS team_slug
                FROM players p
                JOIN teams t ON t.id = p.team_id
                WHERE p.id = :id';
        if ($activeOnly) {
            $sql .= ' AND p.is_active = 1';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function listActive(?int $teamId = null, ?string $division = null): array
    {
        $sql = 'SELECT p.*, t.name AS team_name, t.slug AS team_slug
                FROM players p
                JOIN teams t ON t.id = p.team_id
                WHERE p.is_active = 1';
        $params = [];

        if ($teamId !== null) {
            $sql .= ' AND p.team_id = :team_id';
            $params['team_id'] = $teamId;
        }
        if ($division !== null) {
            $sql .= ' AND p.division = :division';
            $params['division'] = $division;
        }

        $sql .= ' ORDER BY t.id, p.division, p.name';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function create(int $teamId, string $division, string $name): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO players (team_id, division, name) VALUES (:team_id, :division, :name)'
        );
        $stmt->execute([
            'team_id' => $teamId,
            'division' => $division,
            'name' => $name,
        ]);

        return $this->findById((int) $this->db->lastInsertId()) ?? [];
    }

    public function update(int $id, array $fields): ?array
    {
        $allowed = ['name', 'division', 'team_id'];
        $sets = [];
        $params = ['id' => $id];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $fields)) {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $fields[$key];
            }
        }

        if ($sets === []) {
            return $this->findById($id, false);
        }

        $sql = 'UPDATE players SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->findById($id, false);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE players SET is_active = 0 WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
