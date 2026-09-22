<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Models;

use FavoriteCMS\Tools\Support\AccessMode;
use FavoriteCMS\Tools\Support\ToolStatus;

class Tool
{
    public ?int $id = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public ?int $category_id = null;
    public string $engine = 'HTML';
    public ?string $frontend_design_slug = null;
    public string $access_mode = AccessMode::FREE;
    public string $status = ToolStatus::DRAFT;
    public array $input_schema = [];
    public array $output_schema = [];
    public array $configuration = [];
    public int $display_order = 0;
    public string $meta_title = '';
    public string $meta_description = '';
    public string $icon = '';
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $category_name = null;
    public ?string $category_slug = null;

    public function __construct(?array $attributes = null)
    {
        if ($attributes !== null) {
            foreach ($attributes as $k => $v) {
                $this->__set($k, $v);
            }
        }
    }

    public function isActive(): bool
    {
        return $this->status === ToolStatus::ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->status === ToolStatus::DRAFT;
    }

    public function isDisabled(): bool
    {
        return $this->status === ToolStatus::DISABLED;
    }

    public function isFree(): bool
    {
        return $this->access_mode === AccessMode::FREE;
    }

    public function isLoginRequired(): bool
    {
        return $this->access_mode === AccessMode::LOGIN_REQUIRED;
    }

    public function isMembershipRequired(): bool
    {
        return $this->access_mode === AccessMode::MEMBERSHIP_REQUIRED;
    }

    public function __get(string $name): mixed
    {
        if ($name === 'ui_schema') {
            return $this->configuration['ui_schema'] ?? [];
        }
        if ($name === 'handler_class') {
            return $this->configuration['handler_class'] ?? null;
        }
        if ($name === 'handler_id') {
            return $this->configuration['handler_id'] ?? null;
        }
        if ($name === 'html_source') {
            return $this->configuration['html_source'] ?? '';
        }
        if ($name === 'css_source') {
            return $this->configuration['css_source'] ?? '';
        }
        if ($name === 'js_source') {
            return $this->configuration['js_source'] ?? '';
        }
        if ($name === 'external_libraries') {
            return $this->configuration['external_libraries'] ?? [];
        }
        if ($name === 'python_service_id') {
            return isset($this->configuration['python_service_id']) && $this->configuration['python_service_id'] !== ''
                ? (int)$this->configuration['python_service_id']
                : null;
        }
        if ($name === 'python_endpoint') {
            return $this->configuration['python_endpoint'] ?? null;
        }
        if ($name === 'thumbnail') {
            return $this->configuration['thumbnail'] ?? null;
        }
        return null;
    }

    public function __set(string $name, mixed $value): void
    {
        $customConfigKeys = [
            'ui_schema', 'handler_class', 'handler_id', 'html_source', 'css_source',
            'js_source', 'external_libraries', 'python_service_id', 'python_endpoint', 'thumbnail'
        ];
        if (in_array($name, $customConfigKeys, true)) {
            $this->configuration[$name] = $value;
        } elseif (in_array($name, ['input_schema', 'output_schema', 'configuration'], true)) {
            $this->{$name} = self::decodeJsonField($value);
        } elseif (property_exists($this, $name)) {
            $this->{$name} = $value;
        }
    }

    public function __isset(string $name): bool
    {
        $customConfigKeys = [
            'ui_schema', 'handler_class', 'handler_id', 'html_source', 'css_source',
            'js_source', 'external_libraries', 'python_service_id', 'python_endpoint', 'thumbnail'
        ];
        if (in_array($name, $customConfigKeys, true)) {
            return isset($this->configuration[$name]) && $this->configuration[$name] !== '' && $this->configuration[$name] !== [];
        }
        return isset($this->{$name});
    }

    public function __unset(string $name): void
    {
        if (isset($this->configuration[$name])) {
            unset($this->configuration[$name]);
        } elseif (property_exists($this, $name)) {
            unset($this->{$name});
        }
    }

    public static function fromArray(array|object $row): self
    {
        if (is_object($row)) {
            $row = (array)$row;
        }
        $tool = new self();
        $tool->id = isset($row['id']) ? (int)$row['id'] : null;
        $tool->name = (string)($row['name'] ?? '');
        $tool->slug = (string)($row['slug'] ?? '');
        $tool->description = (string)($row['description'] ?? '');
        $tool->category_id = isset($row['category_id']) && $row['category_id'] !== null ? (int)$row['category_id'] : null;
        $tool->engine = (string)($row['engine'] ?? 'HTML');
        $tool->frontend_design_slug = isset($row['frontend_design_slug']) && $row['frontend_design_slug'] !== '' ? (string)$row['frontend_design_slug'] : null;
        $tool->access_mode = (string)($row['access_mode'] ?? AccessMode::FREE);
        $tool->status = (string)($row['status'] ?? ToolStatus::DRAFT);

        $tool->input_schema = self::decodeJsonField($row['input_schema'] ?? []);
        $tool->output_schema = self::decodeJsonField($row['output_schema'] ?? []);
        $tool->configuration = self::decodeJsonField($row['configuration'] ?? []);

        $tool->display_order = (int)($row['display_order'] ?? 0);
        $tool->meta_title = (string)($row['meta_title'] ?? '');
        $tool->meta_description = (string)($row['meta_description'] ?? '');
        $tool->icon = (string)($row['icon'] ?? '');
        $tool->created_at = isset($row['created_at']) ? (string)$row['created_at'] : null;
        $tool->updated_at = isset($row['updated_at']) ? (string)$row['updated_at'] : null;
        $tool->category_name = isset($row['category_name']) ? (string)$row['category_name'] : null;
        $tool->category_slug = isset($row['category_slug']) ? (string)$row['category_slug'] : null;

        return $tool;
    }

    public function getFrontendDesignSlug(): string
    {
        return !empty($this->frontend_design_slug) ? $this->frontend_design_slug : 'default';
    }

    public function toArray(): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'slug'                 => $this->slug,
            'description'          => $this->description,
            'category_id'          => $this->category_id,
            'category_name'        => $this->category_name,
            'category_slug'        => $this->category_slug,
            'engine'               => $this->engine,
            'frontend_design_slug' => $this->frontend_design_slug,
            'access_mode'          => $this->access_mode,
            'status'               => $this->status,
            'input_schema'      => $this->input_schema,
            'output_schema'     => $this->output_schema,
            'configuration'     => $this->configuration,
            'ui_schema'          => $this->ui_schema,
            'handler_class'      => $this->handler_class,
            'handler_id'         => $this->handler_id,
            'html_source'        => $this->html_source,
            'css_source'         => $this->css_source,
            'js_source'          => $this->js_source,
            'external_libraries' => $this->external_libraries,
            'python_service_id'  => $this->python_service_id,
            'python_endpoint'    => $this->python_endpoint,
            'thumbnail'          => $this->thumbnail,
            'display_order'      => $this->display_order,
            'meta_title'        => $this->meta_title,
            'meta_description'  => $this->meta_description,
            'icon'              => $this->icon,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }

    private static function decodeJsonField(mixed $val): array
    {
        if (is_array($val)) {
            return $val;
        }
        if (is_string($val) && trim($val) !== '') {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }
}
