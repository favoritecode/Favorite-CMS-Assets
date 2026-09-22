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

    public function findBySlug(string $slug): ?PythonService
    {
        try {
            $row = $this->db->selectOne("SELECT * FROM `favorite_web_tool_python_services` WHERE `slug` = ?", [$slug]);
            return $row ? PythonService::fromArray($row) : null;
        } catch (\Throwable) {
            return null;
        }
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
        $where = $activeOnly ? "WHERE LOWER(`status`) = 'active'" : "";
        $rows = $this->db->select("SELECT * FROM `favorite_web_tool_python_services` {$where} ORDER BY `name` ASC");
        return array_map(fn($row) => PythonService::fromArray($row), $rows);
    }

    public function create(array $data): PythonService
    {
        $name = trim((string)($data['name'] ?? ''));
        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '' && $name !== '') {
            $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        }

        $description = isset($data['description']) ? trim((string)$data['description']) : null;
        $baseUrl = rtrim((string)($data['base_url'] ?? ''), '/');
        $defaultEndpoint = isset($data['default_endpoint_path']) ? trim((string)$data['default_endpoint_path']) : '/download/api';
        if ($defaultEndpoint !== '' && !str_starts_with($defaultEndpoint, '/')) {
            $defaultEndpoint = '/' . $defaultEndpoint;
        }

        $httpMethod = strtoupper((string)($data['http_method'] ?? 'GET'));
        $authType = (string)($data['auth_type'] ?? 'none');
        $apiKey = !empty($data['api_key']) ? (string)$data['api_key'] : null;
        $timeout = max(1, min(120, (int)($data['timeout'] ?? 30)));
        $status = strtolower($data['status'] ?? (isset($data['is_active']) ? (!empty($data['is_active']) ? 'active' : 'disabled') : 'active'));

        try {
            $sql = "
                INSERT INTO `favorite_web_tool_python_services`
                (`name`, `slug`, `description`, `base_url`, `default_endpoint_path`, `http_method`, `auth_type`, `api_key`, `timeout`, `status`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $this->db->execute($sql, [
                $name,
                $slug,
                $description,
                $baseUrl,
                $defaultEndpoint,
                $httpMethod,
                $authType,
                $apiKey,
                $timeout,
                $status,
            ]);
        } catch (\Throwable) {
            // Fallback for older schema before migration 004
            $sql = "
                INSERT INTO `favorite_web_tool_python_services`
                (`name`, `base_url`, `auth_type`, `api_key`, `timeout`, `status`)
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $this->db->execute($sql, [
                $name,
                $baseUrl,
                $authType,
                $apiKey,
                $timeout,
                $status,
            ]);
        }

        $id = (int)$this->db->lastInsertId();
        return $this->findById($id) ?: new PythonService(array_merge($data, [
            'id'                    => $id,
            'slug'                  => $slug,
            'description'           => $description,
            'default_endpoint_path' => $defaultEndpoint,
            'http_method'           => $httpMethod,
            'status'                => $status,
        ]));
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['is_active']) && !isset($data['status'])) {
            $data['status'] = !empty($data['is_active']) ? 'active' : 'disabled';
        }

        $supportedFields = [
            'name', 'slug', 'description', 'base_url', 'default_endpoint_path',
            'http_method', 'auth_type', 'api_key', 'timeout', 'status'
        ];

        foreach ($supportedFields as $field) {
            if (array_key_exists($field, $data)) {
                $val = $data[$field];
                if ($field === 'base_url' && is_string($val)) {
                    $val = rtrim($val, '/');
                } elseif ($field === 'default_endpoint_path' && is_string($val) && $val !== '' && !str_starts_with($val, '/')) {
                    $val = '/' . $val;
                } elseif ($field === 'http_method' && is_string($val)) {
                    $val = strtoupper($val);
                } elseif ($field === 'status' && is_string($val)) {
                    $val = strtolower($val);
                }

                $fields[] = "`{$field}` = ?";
                $params[] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE `favorite_web_tool_python_services` SET " . implode(', ', $fields) . " WHERE `id` = ?";

        try {
            return (bool)$this->db->execute($sql, $params);
        } catch (\Throwable) {
            // Fallback for older schema without new columns
            $legacyFields = [];
            $legacyParams = [];
            foreach (['name', 'base_url', 'auth_type', 'api_key', 'timeout', 'status'] as $f) {
                if (array_key_exists($f, $data)) {
                    $legacyFields[] = "`{$f}` = ?";
                    $val = $data[$f];
                    if ($f === 'base_url' && is_string($val)) {
                        $val = rtrim($val, '/');
                    }
                    $legacyParams[] = $val;
                }
            }
            if (empty($legacyFields)) {
                return false;
            }
            $legacyParams[] = $id;
            $legacySql = "UPDATE `favorite_web_tool_python_services` SET " . implode(', ', $legacyFields) . " WHERE `id` = ?";
            return (bool)$this->db->execute($legacySql, $legacyParams);
        }
    }

    public function setStatus(int $id, string $status): bool
    {
        $status = strtolower($status) === 'active' ? 'active' : 'disabled';
        return (bool)$this->db->execute(
            "UPDATE `favorite_web_tool_python_services` SET `status` = ? WHERE `id` = ?",
            [$status, $id]
        );
    }

    public function toggleStatus(int $id): bool
    {
        $srv = $this->findById($id);
        if (!$srv) {
            return false;
        }
        $newStatus = $srv->isActive() ? 'disabled' : 'active';
        return $this->setStatus($id, $newStatus);
    }

    public function delete(int $id): bool
    {
        return (bool)$this->db->execute("DELETE FROM `favorite_web_tool_python_services` WHERE `id` = ?", [$id]);
    }

    public function count(): int
    {
        $res = $this->db->selectOne("SELECT COUNT(*) as count FROM `favorite_web_tool_python_services`");
        return (int)(is_object($res) ? ($res->count ?? 0) : ($res['count'] ?? 0));
    }
}
