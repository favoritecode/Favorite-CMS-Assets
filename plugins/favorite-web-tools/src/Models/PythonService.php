<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Models;

class PythonService
{
    public ?int $id = null;
    public string $name = '';
    public string $slug = '';
    public ?string $description = null;
    public string $base_url = '';
    public ?string $default_endpoint_path = '/download/api';
    public string $http_method = 'GET'; // 'GET', 'POST'
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

        if ($this->slug === '' && $this->name !== '') {
            $this->slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $this->name), '-'));
        }
    }

    public function isActive(): bool
    {
        return strtolower($this->status) === 'active';
    }

    public function getResolvedEndpoint(?string $endpoint = null): string
    {
        $base = rtrim($this->base_url, '/');
        $ep = trim((string)($endpoint ?: ($this->default_endpoint_path ?: '/')));
        if (!str_starts_with($ep, '/')) {
            $ep = '/' . $ep;
        }
        return $base . $ep;
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
        $srv->slug = (string)($row['slug'] ?? '');
        if ($srv->slug === '' && $srv->name !== '') {
            $srv->slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $srv->name), '-'));
        }
        $srv->description = isset($row['description']) ? (string)$row['description'] : null;
        $srv->base_url = rtrim((string)($row['base_url'] ?? ''), '/');
        $srv->default_endpoint_path = isset($row['default_endpoint_path']) ? (string)$row['default_endpoint_path'] : '/download/api';
        $srv->http_method = strtoupper((string)($row['http_method'] ?? 'GET'));
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
            'id'                    => $this->id,
            'name'                  => $this->name,
            'slug'                  => $this->slug,
            'description'           => $this->description,
            'base_url'              => $this->base_url,
            'default_endpoint_path' => $this->default_endpoint_path,
            'http_method'           => $this->http_method,
            'auth_type'             => $this->auth_type,
            'api_key'               => $this->api_key,
            'timeout'               => $this->timeout,
            'status'                => $this->status,
            'is_active'             => $this->isActive() ? 1 : 0,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}
