<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Repositories;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Support\ToolStatus;

class ToolRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function find(int $id): ?Tool
    {
        return $this->findById($id);
    }

    public function findById(int $id): ?Tool
    {
        $sql = "
            SELECT t.*, c.name as category_name, c.slug as category_slug
            FROM `favorite_web_tools` t
            LEFT JOIN `favorite_web_tool_categories` c ON c.id = t.category_id
            WHERE t.id = ?
        ";
        $row = $this->db->selectOne($sql, [$id]);
        return $row ? Tool::fromArray($row) : null;
    }

    public function findBySlug(string $slug): ?Tool
    {
        $sql = "
            SELECT t.*, c.name as category_name, c.slug as category_slug
            FROM `favorite_web_tools` t
            LEFT JOIN `favorite_web_tool_categories` c ON c.id = t.category_id
            WHERE t.slug = ?
        ";
        $row = $this->db->selectOne($sql, [$slug]);
        return $row ? Tool::fromArray($row) : null;
    }

    public function all(): array
    {
        return $this->getAllAdminTools();
    }

    public function byCategory(int $catId): array
    {
        return $this->getAllAdminTools(['category' => $catId]);
    }

    public function byStatus(string $status): array
    {
        return $this->getAllAdminTools(['status' => $status]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->setStatus($id, $status);
    }

    /**
     * Get active tools for the public catalog and discovery API.
     */
    public function getPublicTools(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = ["t.`status` = 'ACTIVE'"];
        $params = [];

        if (!empty($filters['category'])) {
            $cat = trim((string)$filters['category']);
            if (is_numeric($cat)) {
                $where[] = "t.category_id = ?";
                $params[] = (int)$cat;
            } else {
                $where[] = "c.slug = ?";
                $params[] = $cat;
            }
        } elseif (!empty($filters['category_id'])) {
            $where[] = "t.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim((string)$filters['search']) . '%';
            $where[] = "(t.name LIKE ? OR t.description LIKE ?)";
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['engine'])) {
            $where[] = "t.engine = ?";
            $params[] = strtoupper(trim((string)$filters['engine']));
        }

        if (!empty($filters['access_mode'])) {
            $where[] = "t.access_mode = ?";
            $params[] = strtoupper(trim((string)$filters['access_mode']));
        }

        $whereClause = "WHERE " . implode(' AND ', $where);

        // Count total
        $countSql = "
            SELECT COUNT(t.id) as cnt
            FROM `favorite_web_tools` t
            LEFT JOIN `favorite_web_tool_categories` c ON c.id = t.category_id
            {$whereClause}
        ";
        $totalRow = $this->db->selectOne($countSql, $params);
        $total = is_object($totalRow) ? (int)($totalRow->cnt ?? 0) : (int)($totalRow['cnt'] ?? 0);

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $totalPages = max(1, (int)ceil($total / $perPage));
        $offset = ($page - 1) * $perPage;

        $orderBy = "ORDER BY t.display_order ASC, t.name ASC";
        if (!empty($filters['sort'])) {
            $orderBy = match ($filters['sort']) {
                'name'   => "ORDER BY t.name ASC",
                'newest' => "ORDER BY t.id DESC",
                default  => "ORDER BY t.display_order ASC, t.name ASC",
            };
        }

        $sql = "
            SELECT t.*, c.name as category_name, c.slug as category_slug
            FROM `favorite_web_tools` t
            LEFT JOIN `favorite_web_tool_categories` c ON c.id = t.category_id
            {$whereClause}
            {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $rows = $this->db->select($sql, $params);
        $items = array_map(fn($row) => Tool::fromArray($row), $rows);

        return [
            'items'      => $items,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * Get all tools for Admin Management with optional filters.
     *
     * @return Tool[]
     */
    public function getAllAdminTools(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "t.status = ?";
            $params[] = strtoupper(trim((string)$filters['status']));
        }

        if (!empty($filters['category'])) {
            $cat = trim((string)$filters['category']);
            if (is_numeric($cat)) {
                $where[] = "t.category_id = ?";
                $params[] = (int)$cat;
            } else {
                $where[] = "c.slug = ?";
                $params[] = $cat;
            }
        } elseif (!empty($filters['category_id'])) {
            $where[] = "t.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['engine'])) {
            $where[] = "t.engine = ?";
            $params[] = strtoupper(trim((string)$filters['engine']));
        }

        if (!empty($filters['access_mode'])) {
            $where[] = "t.access_mode = ?";
            $params[] = strtoupper(trim((string)$filters['access_mode']));
        }

        if (!empty($filters['search'])) {
            $search = '%' . trim((string)$filters['search']) . '%';
            $where[] = "(t.name LIKE ? OR t.slug LIKE ? OR t.description LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = empty($where) ? "" : "WHERE " . implode(' AND ', $where);

        $sql = "
            SELECT t.*, c.name as category_name, c.slug as category_slug
            FROM `favorite_web_tools` t
            LEFT JOIN `favorite_web_tool_categories` c ON c.id = t.category_id
            {$whereClause}
            ORDER BY t.display_order ASC, t.id DESC
        ";

        $rows = $this->db->select($sql, $params);
        return array_map(fn($row) => Tool::fromArray($row), $rows);
    }

    private ?bool $hasDesignSlugColumn = null;

    protected function hasDesignSlugColumn(): bool
    {
        if ($this->hasDesignSlugColumn !== null) {
            return $this->hasDesignSlugColumn;
        }

        try {
            $this->db->selectOne("SELECT `frontend_design_slug` FROM `favorite_web_tools` LIMIT 0");
            $this->hasDesignSlugColumn = true;
        } catch (\Throwable) {
            $this->hasDesignSlugColumn = false;
        }

        return $this->hasDesignSlugColumn;
    }

    public function create(array $data): Tool
    {
        $hasDesignCol = $this->hasDesignSlugColumn();

        $columns = ['name', 'slug', 'description', 'category_id', 'engine'];
        if ($hasDesignCol) {
            $columns[] = 'frontend_design_slug';
        }
        $columns = array_merge($columns, [
            'access_mode', 'status', 'input_schema', 'output_schema',
            'configuration', 'display_order', 'meta_title', 'meta_description', 'icon'
        ]);

        $colList = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $sql = "INSERT INTO `favorite_web_tools` (`{$colList}`) VALUES ({$placeholders})";

        $config = is_array($data['configuration'] ?? null) ? $data['configuration'] : (is_string($data['configuration'] ?? null) ? json_decode($data['configuration'], true) : []);
        if (!is_array($config)) {
            $config = [];
        }

        $customConfigKeys = [
            'ui_schema', 'handler_class', 'handler_id', 'html_source', 'css_source',
            'js_source', 'external_libraries', 'python_service_id', 'python_endpoint', 'thumbnail'
        ];
        foreach ($customConfigKeys as $extra) {
            if (array_key_exists($extra, $data) && !array_key_exists($extra, $config)) {
                $config[$extra] = $data[$extra];
            }
        }

        $values = [
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            isset($data['category_id']) && $data['category_id'] !== '' ? (int)$data['category_id'] : null,
            $data['engine'] ?? 'HTML',
        ];
        if ($hasDesignCol) {
            $values[] = $data['frontend_design_slug'] ?? null;
        }
        $values = array_merge($values, [
            $data['access_mode'] ?? 'FREE',
            $data['status'] ?? ToolStatus::DRAFT,
            is_array($data['input_schema'] ?? null) ? json_encode($data['input_schema']) : ($data['input_schema'] ?? null),
            is_array($data['output_schema'] ?? null) ? json_encode($data['output_schema']) : ($data['output_schema'] ?? null),
            !empty($config) ? json_encode($config) : null,
            (int)($data['display_order'] ?? 0),
            $data['meta_title'] ?? null,
            $data['meta_description'] ?? null,
            $data['icon'] ?? null,
        ]);

        $this->db->execute($sql, $values);

        $id = (int)$this->db->lastInsertId();
        return $this->findById($id) ?: new Tool(array_merge($data, ['id' => $id, 'configuration' => $config]));
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $jsonFields = ['input_schema', 'output_schema', 'configuration'];
        $allowedFields = [
            'name', 'slug', 'description', 'category_id', 'engine', 'access_mode', 'status',
            'input_schema', 'output_schema', 'configuration', 'display_order', 'meta_title',
            'meta_description', 'icon'
        ];
        if ($this->hasDesignSlugColumn()) {
            $allowedFields[] = 'frontend_design_slug';
        }

        // Merge extra config fields into configuration
        $config = null;
        if (array_key_exists('configuration', $data)) {
            $config = is_array($data['configuration']) ? $data['configuration'] : json_decode((string)$data['configuration'], true);
        }
        if (!is_array($config)) {
            $existing = $this->findById($id);
            $config = $existing ? $existing->configuration : [];
        }

        $hasExtraConfig = false;
        $customConfigKeys = [
            'ui_schema', 'handler_class', 'handler_id', 'html_source', 'css_source',
            'js_source', 'external_libraries', 'python_service_id', 'python_endpoint', 'thumbnail'
        ];
        foreach ($customConfigKeys as $extra) {
            if (array_key_exists($extra, $data)) {
                $config[$extra] = $data[$extra];
                $hasExtraConfig = true;
            }
        }

        if ($hasExtraConfig || array_key_exists('configuration', $data)) {
            $data['configuration'] = $config;
        }

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $val = $data[$field];
                if (in_array($field, $jsonFields, true)) {
                    $val = is_array($val) ? json_encode($val) : $val;
                } elseif ($field === 'category_id') {
                    $val = ($val !== '' && $val !== null) ? (int)$val : null;
                }
                $params[] = $val;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE `favorite_web_tools` SET " . implode(', ', $fields) . " WHERE `id` = ?";
        return $this->db->execute($sql, $params);
    }

    public function setStatus(int $id, string $status): bool
    {
        return $this->db->execute("UPDATE `favorite_web_tools` SET `status` = ? WHERE `id` = ?", [$status, $id]);
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM `favorite_web_tools` WHERE `id` = ?", [$id]);
    }

    public function countByStatus(): array
    {
        $rows = $this->db->select("SELECT `status`, COUNT(*) as cnt FROM `favorite_web_tools` GROUP BY `status`");
        $counts = [
            'total'    => 0,
            'active'   => 0,
            'draft'    => 0,
            'disabled' => 0,
        ];

        foreach ($rows as $row) {
            $st = strtolower((string)(is_object($row) ? ($row->status ?? '') : ($row['status'] ?? '')));
            $cnt = (int)(is_object($row) ? ($row->cnt ?? 0) : ($row['cnt'] ?? 0));
            $counts['total'] += $cnt;
            if (isset($counts[$st])) {
                $counts[$st] = $cnt;
            }
        }

        return $counts;
    }
}
