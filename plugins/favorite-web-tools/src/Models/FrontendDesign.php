<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Models;

class FrontendDesign
{
    public ?int $id = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $version = '1.0.0';
    public string $status = 'active';
    public string $template_markup = '';
    public string $css_content = '';
    public string $js_content = '';
    public bool $js_enabled = false;
    public string $author = '';
    public string $category = 'Custom';
    public array $bindings_schema = [];
    public array $preview_data = [];
    public string $normalizer_key = 'default';
    public bool $is_builtin = false;
    public array $supported_normalizers = ['media', 'default'];
    public array $settings_schema = [];
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(array|object|null $attributes = null)
    {
        if ($attributes !== null) {
            if (is_object($attributes)) {
                $attributes = (array)$attributes;
            }
            foreach ($attributes as $k => $v) {
                $this->__set($k, $v);
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return strtolower($this->status) === 'active';
    }

    public function isBuiltin(): bool
    {
        return $this->is_builtin;
    }

    public function getTemplateMarkup(): string
    {
        return $this->template_markup;
    }

    public function getTemplateHtml(): string
    {
        return $this->template_markup;
    }

    public function getCssContent(): string
    {
        return $this->css_content;
    }

    public function getJsContent(): string
    {
        return $this->js_content;
    }

    public function isJsEnabled(): bool
    {
        return $this->js_enabled;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getBindingsSchema(): array
    {
        return $this->bindings_schema;
    }

    public function getPreviewData(): array
    {
        return $this->preview_data;
    }

    public function getNormalizerKey(): string
    {
        return $this->normalizer_key;
    }

    public function getSupportedNormalizers(): array
    {
        return $this->supported_normalizers;
    }

    public function getSettingsSchema(): array
    {
        return $this->settings_schema;
    }

    public function __set(string $name, mixed $value): void
    {
        if ($name === 'template_html') {
            $this->template_markup = (string)$value;
        } elseif ($name === 'is_active') {
            $this->status = !empty($value) ? 'active' : 'inactive';
        } elseif ($name === 'is_builtin') {
            $this->is_builtin = (bool)$value;
        } elseif ($name === 'js_enabled') {
            $this->js_enabled = !empty($value);
        } elseif ($name === 'id') {
            $this->id = $value !== null ? (int)$value : null;
        } elseif (in_array($name, ['bindings_schema', 'preview_data', 'supported_normalizers', 'settings_schema'], true)) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                $this->{$name} = is_array($decoded) ? $decoded : [];
            } elseif (is_array($value)) {
                $this->{$name} = $value;
            } else {
                $this->{$name} = [];
            }
        } elseif (property_exists($this, $name)) {
            $this->{$name} = (string)($value ?? '');
        }
    }

    public function __get(string $name): mixed
    {
        if ($name === 'template_html') {
            return $this->template_markup;
        }
        if ($name === 'is_active') {
            return $this->isActive();
        }
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        return null;
    }

    public function __isset(string $name): bool
    {
        if ($name === 'template_html' || $name === 'is_active') {
            return true;
        }
        return property_exists($this, $name);
    }

    public function toArray(): array
    {
        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'slug'                  => $this->slug,
            'description'           => $this->description,
            'version'               => $this->version,
            'author'                => $this->author,
            'category'              => $this->category,
            'status'                => $this->status,
            'is_active'             => $this->isActive() ? 1 : 0,
            'template_markup'       => $this->template_markup,
            'template_html'         => $this->template_markup,
            'css_content'           => $this->css_content,
            'js_content'            => $this->js_content,
            'js_enabled'            => $this->js_enabled ? 1 : 0,
            'bindings_schema'       => $this->bindings_schema,
            'preview_data'          => $this->preview_data,
            'normalizer_key'        => $this->normalizer_key,
            'is_builtin'            => $this->is_builtin ? 1 : 0,
            'supported_normalizers' => $this->supported_normalizers,
            'settings_schema'       => $this->settings_schema,
            'created_at'            => $this->created_at,
            'updated_at'            => $this->updated_at,
        ];
    }
}

