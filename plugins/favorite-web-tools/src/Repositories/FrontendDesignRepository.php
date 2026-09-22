<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Repositories;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Models\FrontendDesign;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FrontendDesignRepository
{
    protected Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
        if (method_exists($this->db, 'registerPrefixableTables')) {
            $this->db->registerPrefixableTables([
                'favorite_web_tool_frontend_designs',
                'favorite_web_tools',
            ]);
        }
    }

    public function findById(int $id): ?FrontendDesign
    {
        try {
            $row = $this->db->selectOne("SELECT * FROM `favorite_web_tool_frontend_designs` WHERE `id` = ?", [$id]);
            return $row ? new FrontendDesign($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findBySlug(string $slug): ?FrontendDesign
    {
        try {
            $row = $this->db->selectOne("SELECT * FROM `favorite_web_tool_frontend_designs` WHERE `slug` = ?", [$slug]);
            return $row ? new FrontendDesign($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function find(int $id): ?FrontendDesign
    {
        return $this->findById($id);
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
     * @return FrontendDesign[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        try {
            $where = $activeOnly ? "WHERE LOWER(`status`) = 'active'" : "";
            $rows = $this->db->select("SELECT * FROM `favorite_web_tool_frontend_designs` {$where} ORDER BY `is_builtin` DESC, `name` ASC");
            return array_map(fn($row) => new FrontendDesign($row), $rows);
        } catch (Throwable) {
            return [];
        }
    }

    public function create(array $data): FrontendDesign
    {
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Design name is required.');
        }

        $slug = trim((string)($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        }
        $slug = strtolower(preg_replace('/[^a-z0-9_-]/', '', $slug));
        if ($slug === '') {
            throw new InvalidArgumentException('A valid alphanumeric slug is required.');
        }

        $existing = $this->findBySlug($slug);
        if ($existing !== null) {
            throw new InvalidArgumentException("A design with slug '{$slug}' already exists.");
        }

        $template = (string)($data['template_markup'] ?? ($data['template_html'] ?? ($data['template'] ?? '')));
        if (trim($template) === '') {
            throw new InvalidArgumentException('Template markup is required.');
        }

        $status = isset($data['status']) ? strtolower((string)$data['status']) : (isset($data['is_active']) ? (!empty($data['is_active']) ? 'active' : 'inactive') : 'active');
        $bindings = $data['bindings_schema'] ?? ($data['bindings'] ?? []);
        $previewData = $data['preview_data'] ?? ($data['preview'] ?? []);

        $bindingsJson = is_array($bindings) ? json_encode($bindings, JSON_UNESCAPED_SLASHES) : (is_string($bindings) ? $bindings : '{}');
        $previewJson = is_array($previewData) ? json_encode($previewData, JSON_UNESCAPED_SLASHES) : (is_string($previewData) ? $previewData : '{}');

        $this->db->execute("
            INSERT INTO `favorite_web_tool_frontend_designs` (
                `name`, `slug`, `description`, `version`, `status`,
                `template_markup`, `css_content`, `js_content`,
                `bindings_schema`, `preview_data`, `normalizer_key`, `is_builtin`,
                `created_at`, `updated_at`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ", [
            $name,
            $slug,
            isset($data['description']) ? trim((string)$data['description']) : null,
            trim((string)($data['version'] ?? '1.0.0')),
            strtolower((string)($data['status'] ?? 'active')),
            $template,
            isset($data['css_content']) ? (string)$data['css_content'] : null,
            isset($data['js_content']) ? (string)$data['js_content'] : null,
            $bindingsJson,
            $previewJson,
            trim((string)($data['normalizer_key'] ?? 'default')),
            !empty($data['is_builtin']) ? 1 : 0,
        ]);

        $created = $this->findBySlug($slug);
        if (!$created) {
            throw new RuntimeException("Failed to retrieve created frontend design '{$slug}'.");
        }
        return $created;
    }

    public function update(int $id, array $data): FrontendDesign
    {
        $design = $this->findById($id);
        if (!$design) {
            throw new RuntimeException("Frontend design #{$id} not found.");
        }

        $name = isset($data['name']) ? trim((string)$data['name']) : $design->name;
        if ($name === '') {
            throw new InvalidArgumentException('Design name cannot be empty.');
        }

        $slug = isset($data['slug']) ? strtolower(preg_replace('/[^a-z0-9_-]/', '', trim((string)$data['slug']))) : $design->slug;
        if ($slug === '') {
            throw new InvalidArgumentException('Slug cannot be empty.');
        }

        // If slug changed, ensure uniqueness
        if ($slug !== $design->slug) {
            $existing = $this->findBySlug($slug);
            if ($existing !== null && $existing->id !== $id) {
                throw new InvalidArgumentException("A design with slug '{$slug}' already exists.");
            }
        }

        $template = array_key_exists('template_markup', $data)
            ? (string)$data['template_markup']
            : (array_key_exists('template_html', $data) ? (string)$data['template_html'] : (array_key_exists('template', $data) ? (string)$data['template'] : $design->template_markup));
        if (trim($template) === '') {
            throw new InvalidArgumentException('Template markup cannot be empty.');
        }

        $css = array_key_exists('css_content', $data) ? (string)$data['css_content'] : $design->css_content;
        $js = array_key_exists('js_content', $data) ? (string)$data['js_content'] : $design->js_content;
        $description = array_key_exists('description', $data) ? (string)$data['description'] : $design->description;
        $version = isset($data['version']) ? trim((string)$data['version']) : $design->version;
        $status = isset($data['status']) ? strtolower((string)$data['status']) : (isset($data['is_active']) ? (!empty($data['is_active']) ? 'active' : 'inactive') : $design->status);
        $normalizerKey = isset($data['normalizer_key']) ? trim((string)$data['normalizer_key']) : $design->normalizer_key;

        $bindings = array_key_exists('bindings_schema', $data) ? $data['bindings_schema'] : $design->bindings_schema;
        $preview = array_key_exists('preview_data', $data) ? $data['preview_data'] : $design->preview_data;

        $bindingsJson = is_array($bindings) ? json_encode($bindings, JSON_UNESCAPED_SLASHES) : (is_string($bindings) ? $bindings : '{}');
        $previewJson = is_array($preview) ? json_encode($preview, JSON_UNESCAPED_SLASHES) : (is_string($preview) ? $preview : '{}');

        $this->db->execute("
            UPDATE `favorite_web_tool_frontend_designs` SET
                `name` = ?,
                `slug` = ?,
                `description` = ?,
                `version` = ?,
                `status` = ?,
                `template_markup` = ?,
                `css_content` = ?,
                `js_content` = ?,
                `bindings_schema` = ?,
                `preview_data` = ?,
                `normalizer_key` = ?,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = ?
        ", [
            $name,
            $slug,
            $description,
            $version,
            $status,
            $template,
            $css,
            $js,
            $bindingsJson,
            $previewJson,
            $normalizerKey,
            $id,
        ]);

        return $this->findById($id) ?? $design;
    }

    public function delete(int $id): bool
    {
        $design = $this->findById($id);
        if (!$design) {
            return false;
        }

        if ($design->isBuiltin()) {
            throw new InvalidArgumentException("Built-in system designs cannot be deleted.");
        }

        // Cannot delete if active tools are using this design
        $usageCount = $this->getUsageCount($design->slug);
        if ($usageCount > 0) {
            throw new InvalidArgumentException("Cannot delete design '{$design->name}' because it is assigned to {$usageCount} tool(s). Reassign those tools first.");
        }

        $this->db->execute("DELETE FROM `favorite_web_tool_frontend_designs` WHERE `id` = ?", [$id]);
        return true;
    }

    public function duplicate(int $id): FrontendDesign
    {
        $original = $this->findById($id);
        if (!$original) {
            throw new RuntimeException("Design #{$id} not found.");
        }

        $baseSlug = $original->slug . '-copy';
        $newSlug = $baseSlug;
        $counter = 1;
        while ($this->findBySlug($newSlug) !== null) {
            $counter++;
            $newSlug = "{$baseSlug}-{$counter}";
        }

        return $this->create([
            'name'            => $original->name . ' (Copy)',
            'slug'            => $newSlug,
            'description'     => $original->description,
            'version'         => $original->version,
            'status'          => 'active',
            'template_markup' => $original->template_markup,
            'css_content'     => $original->css_content,
            'js_content'      => $original->js_content,
            'bindings_schema' => $original->bindings_schema,
            'preview_data'    => $original->preview_data,
            'normalizer_key'  => $original->normalizer_key,
            'is_builtin'      => 0, // Duplicates are user custom designs
        ]);
    }

    public function getUsageCount(string $slug): int
    {
        if ($slug === '') {
            return 0;
        }

        try {
            $row = $this->db->selectOne(
                "SELECT COUNT(*) as cnt FROM `favorite_web_tools` WHERE `frontend_design_slug` = ? OR `configuration` LIKE ?",
                [$slug, '%"frontend_design_slug":"' . $slug . '"%']
            );
            if (!$row) {
                return 0;
            }
            if (is_object($row)) {
                return (int)($row->cnt ?? 0);
            }
            return (int)($row['cnt'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Seeds initial built-in designs if not present.
     */
    public function seedBuiltins(): void
    {
        $builtins = [
            [
                'name'            => 'Default Tool UI',
                'slug'            => 'default',
                'description'     => 'Standard clean code/pre formatted tool output with copy button.',
                'version'         => '1.0.0',
                'status'          => 'active',
                'normalizer_key'  => 'default',
                'is_builtin'      => 1,
                'bindings_schema' => ['raw_json' => 'string', 'value' => 'mixed'],
                'preview_data'    => ['value' => "Sample output string or object", 'raw_json' => "{\n  \"status\": \"success\"\n}"],
                'template_markup' => '<div class="fwt-design-default"><pre class="fwt-result-code">{{raw_json}}</pre></div>',
                'css_content'     => '.fwt-design-default pre { font-family: monospace; padding: 14px; border-radius: 8px; overflow-x: auto; }',
                'js_content'      => null,
            ],
            [
                'name'            => 'Media Downloader Cards',
                'slug'            => 'media-downloader-cards',
                'description'     => 'Universal media format cards displaying video/audio resolutions, codecs, file sizes, and secure download buttons.',
                'version'         => '1.0.0',
                'status'          => 'active',
                'normalizer_key'  => 'media',
                'is_builtin'      => 1,
                'bindings_schema' => [
                    'title'     => 'string',
                    'thumbnail' => 'url',
                    'duration'  => 'string',
                    'formats'   => 'array',
                ],
                'preview_data'    => [
                    'title'         => 'Sample High Definition Video Stream',
                    'thumbnail'     => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&q=80',
                    'duration'      => '1:45',
                    'has_thumbnail' => true,
                    'formats'       => [
                        [
                            'formatId'           => '18',
                            'quality'            => '360p',
                            'ext'                => 'MP4',
                            'codec'              => 'H.264 / AAC',
                            'filesize_formatted' => '4.8 MB',
                            'stream_type'        => 'Video + Audio',
                            'badge_class'        => 'fwt-badge-video-audio',
                            'download_url'       => 'https://example.com/download/360p.mp4',
                            'has_download_url'   => true,
                        ],
                        [
                            'formatId'           => '137',
                            'quality'            => '1080p',
                            'ext'                => 'MP4',
                            'codec'              => 'H.264',
                            'filesize_formatted' => '27.7 MB',
                            'stream_type'        => 'Video only',
                            'badge_class'        => 'fwt-badge-video-only',
                            'download_url'       => 'https://example.com/download/1080p.mp4',
                            'has_download_url'   => true,
                        ],
                        [
                            'formatId'           => '140',
                            'quality'            => '129 kbps',
                            'ext'                => 'M4A',
                            'codec'              => 'AAC',
                            'filesize_formatted' => '950 KB',
                            'stream_type'        => 'Audio only',
                            'badge_class'        => 'fwt-badge-audio-only',
                            'download_url'       => 'https://example.com/download/audio.m4a',
                            'has_download_url'   => true,
                        ],
                    ],
                ],
                'template_markup' => '<div class="fwt-media-design">
{{#if has_thumbnail}}
  <div class="fwt-media-meta-card">
    <img src="{{thumbnail}}" alt="{{title}}" class="fwt-media-thumb">
    <div class="fwt-media-info">
      <div class="fwt-media-title">{{title}}</div>
      {{#if duration}}<div class="fwt-media-duration">Duration: {{duration}}</div>{{/if}}
    </div>
  </div>
{{/if}}

  <h4 class="fwt-media-heading">Available Downloads</h4>
  <div class="fwt-media-list">
{{#formats}}
    <div class="fwt-media-card">
      <div class="fwt-media-left">
        <span class="fwt-badge {{badge_class}}">{{stream_type}}</span>
        <span class="fwt-media-quality">{{quality}}</span>
        <span class="fwt-media-ext">{{ext}}</span>
        {{#if codec}}<span class="fwt-media-codec">{{codec}}</span>{{/if}}
        {{#if filesize_formatted}}<span class="fwt-media-size">{{filesize_formatted}}</span>{{/if}}
        {{#if label}}<span class="fwt-media-label" title="{{label}}">{{label}}</span>{{/if}}
      </div>
      <div class="fwt-media-right">
        {{#if has_download_url}}
        <a href="{{download_url}}" target="_blank" rel="noopener noreferrer" download class="fwt-btn-download">Download</a>
        {{/if}}
        {{^has_download_url}}
        <span class="fwt-btn-disabled">Download unavailable</span>
        {{/has_download_url}}
      </div>
    </div>
{{/formats}}
  </div>

{{#if raw_json}}
  <details class="fwt-media-raw">
    <summary>View Technical Details (Raw JSON)</summary>
    <pre>{{raw_json}}</pre>
  </details>
{{/if}}
</div>',
                'css_content'     => '.fwt-media-design { display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px; }
.fwt-media-meta-card { display: flex; gap: 16px; align-items: center; padding: 14px 18px; background: var(--fwt-bg, #f8fafc); border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 10px; }
.fwt-media-thumb { width: 88px; height: 56px; object-fit: cover; border-radius: 6px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
.fwt-media-info { display: flex; flex-direction: column; gap: 4px; min-width: 0; overflow: hidden; }
.fwt-media-title { font-size: 15px; font-weight: 700; color: var(--fwt-text, #1e293b); line-height: 1.3; overflow: hidden; text-overflow: ellipsis; }
.fwt-media-duration { font-size: 12px; color: var(--fwt-muted, #64748b); font-weight: 500; }
.fwt-media-heading { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--fwt-muted, #64748b); margin: 4px 0 0 0; }
.fwt-media-list { display: flex; flex-direction: column; gap: 10px; }
.fwt-media-card { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; background: var(--fwt-card-bg, #ffffff); border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 8px; gap: 16px; }
.fwt-media-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; min-width: 0; }
.fwt-media-quality { font-size: 14px; font-weight: 700; color: var(--fwt-text, #1e293b); }
.fwt-media-ext { font-size: 12px; font-weight: 600; color: var(--fwt-muted, #64748b); background: rgba(0,0,0,0.06); padding: 2px 7px; border-radius: 4px; }
.fwt-media-codec { font-size: 12px; color: var(--fwt-muted, #64748b); background: rgba(0,0,0,0.04); padding: 2px 6px; border-radius: 3px; }
.fwt-media-size { font-size: 12px; font-weight: 600; color: var(--fwt-text, #1e293b); }
.fwt-media-label { font-size: 12px; color: var(--fwt-muted, #64748b); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fwt-media-right { flex-shrink: 0; }
.fwt-btn-download { display: inline-flex; align-items: center; padding: 8px 18px; background: var(--fwt-primary, #2563eb); color: #fff !important; text-decoration: none !important; border-radius: 6px; font-size: 13px; font-weight: 600; }
.fwt-btn-download:hover { background: var(--fwt-primary-hover, #1d4ed8); }
.fwt-btn-disabled { display: inline-flex; align-items: center; padding: 8px 14px; background: rgba(0,0,0,0.06); color: var(--fwt-muted, #64748b); border-radius: 6px; font-size: 12px; font-weight: 500; cursor: not-allowed; }
.fwt-media-raw { margin-top: 14px; border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 8px; padding: 10px 14px; background: var(--fwt-bg, #f8fafc); }
.fwt-media-raw summary { font-size: 13px; font-weight: 600; color: var(--fwt-muted, #64748b); cursor: pointer; }
.fwt-media-raw pre { margin-top: 10px; margin-bottom: 0; font-family: monospace; font-size: 13px; }
.fwt-badge-video-audio { background: #0284c7; color: #fff; }
.fwt-badge-video-only { background: #2563eb; color: #fff; }
.fwt-badge-audio-only { background: #059669; color: #fff; }
.fwt-badge-hls { background: #7c3aed; color: #fff; }
@media (max-width: 640px) { .fwt-media-card { flex-direction: column; align-items: flex-start; gap: 12px; } .fwt-media-right { width: 100%; } .fwt-btn-download, .fwt-btn-disabled { width: 100%; justify-content: center; } }',
                'js_content'      => null,
            ],
            [
                'name'            => 'Card Grid',
                'slug'            => 'card-grid',
                'description'     => 'Responsive card grid display for array items with title, description, and status tags.',
                'version'         => '1.0.0',
                'status'          => 'active',
                'normalizer_key'  => 'default',
                'is_builtin'      => 1,
                'bindings_schema' => ['items' => 'array'],
                'preview_data'    => [
                    'items' => [
                        ['title' => 'Item Alpha', 'desc' => 'Primary processing result item.', 'badge' => 'Active'],
                        ['title' => 'Item Beta', 'desc' => 'Secondary analytical metric item.', 'badge' => 'Verified'],
                    ],
                ],
                'template_markup' => '<div class="fwt-card-grid">
{{#items}}
  <div class="fwt-grid-card">
    <div class="fwt-grid-card-header">
      <h4 class="fwt-grid-title">{{title}}</h4>
      {{#if badge}}<span class="fwt-grid-badge">{{badge}}</span>{{/if}}
    </div>
    <p class="fwt-grid-desc">{{desc}}</p>
  </div>
{{/items}}
</div>',
                'css_content'     => '.fwt-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; margin: 16px 0; }
.fwt-grid-card { padding: 16px; background: var(--fwt-card-bg, #ffffff); border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 8px; }
.fwt-grid-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.fwt-grid-title { font-size: 15px; font-weight: 700; margin: 0; color: var(--fwt-text, #1e293b); }
.fwt-grid-badge { font-size: 11px; font-weight: 700; padding: 2px 7px; background: #e0f2fe; color: #0369a1; border-radius: 4px; }
.fwt-grid-desc { font-size: 13px; color: var(--fwt-muted, #64748b); margin: 0; line-height: 1.4; }',
                'js_content'      => null,
            ],
            [
                'name'            => 'Table View',
                'slug'            => 'table-view',
                'description'     => 'Structured tabular layout for list of rows or records.',
                'version'         => '1.0.0',
                'status'          => 'active',
                'normalizer_key'  => 'default',
                'is_builtin'      => 1,
                'bindings_schema' => ['rows' => 'array'],
                'preview_data'    => [
                    'rows' => [
                        ['col1' => 'Row 1', 'col2' => 'Alpha Value', 'col3' => 'Completed'],
                        ['col1' => 'Row 2', 'col2' => 'Beta Value', 'col3' => 'Pending'],
                    ],
                ],
                'template_markup' => '<div class="fwt-table-wrap">
  <table class="fwt-table">
    <thead>
      <tr>
        <th>Key / Identifier</th>
        <th>Value</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
{{#rows}}
      <tr>
        <td><strong>{{col1}}</strong></td>
        <td>{{col2}}</td>
        <td><span class="fwt-pill">{{col3}}</span></td>
      </tr>
{{/rows}}
    </tbody>
  </table>
</div>',
                'css_content'     => '.fwt-table-wrap { overflow-x: auto; margin: 16px 0; border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 8px; }
.fwt-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
.fwt-table th { background: var(--fwt-bg, #f8fafc); padding: 10px 14px; font-weight: 700; color: var(--fwt-muted, #64748b); border-bottom: 1px solid var(--fwt-border, #e2e8f0); }
.fwt-table td { padding: 10px 14px; border-bottom: 1px solid var(--fwt-border, #e2e8f0); color: var(--fwt-text, #1e293b); }
.fwt-pill { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: rgba(0,0,0,0.06); }',
                'js_content'      => null,
            ],
            [
                'name'            => 'API Result Inspector',
                'slug'            => 'api-result-view',
                'description'     => 'Technical inspector view with formatted payload summary and raw response details.',
                'version'         => '1.0.0',
                'status'          => 'active',
                'normalizer_key'  => 'default',
                'is_builtin'      => 1,
                'bindings_schema' => ['raw_json' => 'string'],
                'preview_data'    => [
                    'raw_json' => "{\n  \"status\": 200,\n  \"message\": \"Service response ok\"\n}",
                ],
                'template_markup' => '<div class="fwt-api-inspector">
  <div class="fwt-inspector-header">
    <span class="fwt-inspector-badge">API Output</span>
  </div>
  <pre class="fwt-result-code">{{raw_json}}</pre>
</div>',
                'css_content'     => '.fwt-api-inspector { margin: 16px 0; border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 8px; overflow: hidden; }
.fwt-inspector-header { padding: 10px 14px; background: var(--fwt-bg, #f8fafc); border-bottom: 1px solid var(--fwt-border, #e2e8f0); }
.fwt-inspector-badge { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #2563eb; }
.fwt-api-inspector pre { margin: 0; padding: 16px; font-family: monospace; font-size: 13px; background: var(--fwt-code-bg, #0f172a); color: var(--fwt-code-fg, #f8fafc); overflow-x: auto; }',
                'js_content'      => null,
            ],
        ];

        foreach ($builtins as $b) {
            if ($this->findBySlug($b['slug']) === null) {
                try {
                    $this->create($b);
                } catch (Throwable) {
                }
            }
        }
    }
}
