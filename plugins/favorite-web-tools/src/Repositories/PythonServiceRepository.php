<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Repositories;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Models\PythonService;

class PythonServiceRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?PythonService
    {
        return $this->findById($id);
    }

    public function findById(int $id): ?PythonService
    {
        $row = $this->db->selectOne("SELECT * FROM `favorite_web_tool_python_services` WHERE `id` = ?", [$id]);
        return $row ? PythonService::fromArray($row) : null;
    }

    public function all(): array
    {
        return $this->getAll(false);
    }

    public function allActive(): array
    {
        return $this->getAll(true);
    }

    /**
     * @return PythonService[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? "WHERE `status` = 'active'" : "";
        $rows = $this->db->select("SELECT * FROM `favorite_web_tool_python_services` {$where} ORDER BY `name` ASC");
        return array_map(fn($row) => PythonService::fromArray($row), $rows);
    }

    public function create(array $data): PythonService
    {
        $sql = "
            INSERT INTO `favorite_web_tool_python_services`
            (`name`, `base_url`, `auth_type`, `api_key`, `timeout`, `status`)
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $status = $data['status'] ?? (isset($data['is_active']) ? (!empty($data['is_active']) ? 'active' : 'disabled') : 'active');

        $this->db->execute($sql, [
            $data['name'],
            rtrim((string)$data['base_url'], '/'),
            $data['auth_type'] ?? 'none',
            $data['api_key'] ?? null,
            (int)($data['timeout'] ?? 30),
            $status,
        ]);

        $id = (int)$this->db->lastInsertId();
        return $this->findById($id) ?: new PythonService(array_merge($data, ['id' => $id, 'status' => $status]));
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['is_active']) && !isset($data['status'])) {
            $data['status'] = !empty($data['is_active']) ? 'active' : 'disabled';
        }

        foreach (['name', 'base_url', 'auth_type', 'api_key', 'timeout', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $val = $data[$field];
                if ($field === 'base_url' && is_string($val)) {
                    $val = rtrim($val, '/');
                }
                $params[] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE `favorite_web_tool_python_services` SET " . implode(', ', $fields) . " WHERE `id` = ?";
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM `favorite_web_tool_python_services` WHERE `id` = ?", [$id]);
    }

    public function count(): int
    {
        $res = $this->db->selectOne("SELECT COUNT(*) as count FROM `favorite_web_tool_python_services`");
        return (int)(is_object($res) ? ($res->count ?? 0) : ($res['count'] ?? 0));
    }
}
