<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Models;

class ToolCategory
{
    public ?int $id = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $icon = '';
    public int $display_order = 0;
    public string $status = 'active';
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public int $tool_count = 0;

    public function __construct(?array $attributes = null)
    {
        if ($attributes !== null) {
            foreach ($attributes as $k => $v) {
                if ($k === 'is_active') {
                    $this->status = !empty($v) ? 'active' : 'inactive';
                } elseif (property_exists($this, $k)) {
                    $this->{$k} = $v;
                }
            }
        }
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function __get(string $name): mixed
    {
        if ($name === 'is_active') {
            return $this->isActive() ? 1 : 0;
        }
        return null;
    }

    public static function fromArray(array|object $row): self
    {
        if (is_object($row)) {
            $row = (array)$row;
        }
        $cat = new self();
        $cat->id = isset($row['id']) ? (int)$row['id'] : null;
        $cat->name = (string)($row['name'] ?? '');
        $cat->slug = (string)($row['slug'] ?? '');
        $cat->description = (string)($row['description'] ?? '');
        $cat->icon = (string)($row['icon'] ?? '');
        $cat->display_order = (int)($row['display_order'] ?? 0);
        $cat->status = (string)($row['status'] ?? 'active');
        $cat->created_at = isset($row['created_at']) ? (string)$row['created_at'] : null;
        $cat->updated_at = isset($row['updated_at']) ? (string)$row['updated_at'] : null;
        $cat->tool_count = (int)($row['tool_count'] ?? 0);

        return $cat;
    }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'description'   => $this->description,
            'icon'          => $this->icon,
            'display_order' => $this->display_order,
            'status'        => $this->status,
            'is_active'     => $this->isActive() ? 1 : 0,
            'tool_count'    => $this->tool_count,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
