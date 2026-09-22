<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Repositories;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Models\ToolCategory;

class CategoryRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?ToolCategory
    {
        return $this->findById($id);
    }

    public function findById(int $id): ?ToolCategory
    {
        $row = $this->db->selectOne("SELECT * FROM `favorite_web_tool_categories` WHERE `id` = ?", [$id]);
        return $row ? ToolCategory::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?ToolCategory
    {
        $row = $this->db->selectOne("SELECT * FROM `favorite_web_tool_categories` WHERE `slug` = ?", [$slug]);
        return $row ? ToolCategory::fromArray($row) : null;
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
     * @return ToolCategory[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? "WHERE c.`status` = 'active'" : "";
        $sql = "
            SELECT c.*, COUNT(t.id) as tool_count
            FROM `favorite_web_tool_categories` c
            LEFT JOIN `favorite_web_tools` t ON t.category_id = c.id " . ($activeOnly ? "AND t.status = 'ACTIVE'" : "") . "
            {$where}
            GROUP BY c.id
            ORDER BY c.display_order ASC, c.name ASC
        ";

        $rows = $this->db->select($sql);
        return array_map(fn($row) => ToolCategory::fromArray($row), $rows);
    }

    public function create(array $data): ToolCategory
    {
        $sql = "
            INSERT INTO `favorite_web_tool_categories` 
            (`name`, `slug`, `description`, `icon`, `display_order`, `status`)
            VALUES (?, ?, ?, ?, ?, ?)
        ";

        $status = $data['status'] ?? (isset($data['is_active']) ? ($data['is_active'] ? 'active' : 'inactive') : 'active');

        $this->db->execute($sql, [
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['icon'] ?? null,
            (int)($data['display_order'] ?? 0),
            $status,
        ]);

        $id = (int)$this->db->lastInsertId();
        return $this->findById($id) ?: new ToolCategory(array_merge($data, ['id' => $id, 'status' => $status]));
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['is_active']) && !isset($data['status'])) {
            $data['status'] = $data['is_active'] ? 'active' : 'inactive';
        }

        foreach (['name', 'slug', 'description', 'icon', 'display_order', 'status'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE `favorite_web_tool_categories` SET " . implode(', ', $fields) . " WHERE `id` = ?";
        return $this->db->execute($sql, $params);
    }

    public function delete(int $id): bool
    {
        // Nullify foreign key reference in tools
        $this->db->execute("UPDATE `favorite_web_tools` SET `category_id` = NULL WHERE `category_id` = ?", [$id]);
        return $this->db->execute("DELETE FROM `favorite_web_tool_categories` WHERE `id` = ?", [$id]);
    }

    public function count(): int
    {
        $res = $this->db->selectOne("SELECT COUNT(*) as count FROM `favorite_web_tool_categories`");
        return (int)(is_object($res) ? ($res->count ?? 0) : ($res['count'] ?? 0));
    }
}
