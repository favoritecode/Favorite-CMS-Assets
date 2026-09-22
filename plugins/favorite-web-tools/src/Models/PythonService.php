<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Models;

class PythonService
{
    public ?int $id = null;
    public string $name = '';
    public string $base_url = '';
    public string $auth_type = 'none'; // 'none', 'bearer', 'api_key'
    public ?string $api_key = null;
    public int $timeout = 30;
    public string $status = 'active'; // 'active', 'disabled'
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(?array $attributes = null)
    {
        if ($attributes !== null) {
            foreach ($attributes as $k => $v) {
                if ($k === 'is_active') {
                    $this->status = !empty($v) ? 'active' : 'disabled';
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
        $srv = new self();
        $srv->id = isset($row['id']) ? (int)$row['id'] : null;
        $srv->name = (string)($row['name'] ?? '');
        $srv->base_url = rtrim((string)($row['base_url'] ?? ''), '/');
        $srv->auth_type = (string)($row['auth_type'] ?? 'none');
        $srv->api_key = isset($row['api_key']) ? (string)$row['api_key'] : null;
        $srv->timeout = (int)($row['timeout'] ?? 30);
        $srv->status = isset($row['status']) ? (string)$row['status'] : (isset($row['is_active']) ? (!empty($row['is_active']) ? 'active' : 'disabled') : 'active');
        $srv->created_at = isset($row['created_at']) ? (string)$row['created_at'] : null;
        $srv->updated_at = isset($row['updated_at']) ? (string)$row['updated_at'] : null;

        return $srv;
    }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'base_url'   => $this->base_url,
            'auth_type'  => $this->auth_type,
            'api_key'    => $this->api_key,
            'timeout'    => $this->timeout,
            'status'     => $this->status,
            'is_active'  => $this->isActive() ? 1 : 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
