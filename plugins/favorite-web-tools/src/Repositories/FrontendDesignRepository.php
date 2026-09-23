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
                'css_content'     => '.fwt-design-default { max-width: 100%; min-width: 0; box-sizing: border-box; } .fwt-design-default pre { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; line-height: 1.5; padding: 14px; border-radius: 8px; white-space: pre; overflow-x: auto; overflow-y: visible; word-break: normal; overflow-wrap: normal; max-width: 100%; min-width: 0; box-sizing: border-box; }',
                'js_content'      => null,
            ],
            [
                'name'            => 'Media Downloader Cards',
                'slug'            => 'media-downloader-cards',
                'description'     => 'Universal media format cards displaying video/audio resolutions, codecs, file sizes, and secure download buttons.',
                'version'         => '1.2.1',
                'status'          => 'active',
                'normalizer_key'  => 'media',
                'is_builtin'      => 1,
                'bindings_schema' => [
                    'title'     => 'string',
                    'thumbnail' => 'url',
                    'duration'  => 'string',
                    'formats'   => 'array',
                    'items'     => 'array',
                ],
                'preview_data'    => [
                    'title'         => 'Sample High Definition Video Stream',
                    'thumbnail'     => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=400&q=80',
                    'duration'      => '1:45',
                    'has_thumbnail' => true,
                    'total_items'   => 1,
                    'is_bulk'       => false,
                    'formats'       => [
                        [
                            'format_id'          => '18',
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
                            'format_id'          => '137',
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
                            'format_id'          => '140',
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
                'template_markup' => '<div class="fwt-media-design" data-fwt-container="media-downloader">
  <div class="fwt-media-input-card">
    <label for="fwt-bulk-urls" class="fwt-media-input-label">Enter Video / Media URLs (one per line):</label>
    <textarea id="fwt-bulk-urls" data-fwt-input="urls" rows="4" class="fwt-media-textarea" placeholder="Paste YouTube, TikTok, Instagram, Twitter/X, Facebook URLs here... (one per line)"></textarea>
    <div class="fwt-media-input-actions">
      <button type="button" data-fwt-action="get-formats" class="fwt-btn fwt-btn-primary">
        <span>Get Formats</span>
        <span class="fwt-spinner" style="display:none;"></span>
      </button>
      <button type="button" data-fwt-action="clear-all" class="fwt-btn fwt-btn-secondary">Clear</button>
    </div>
    <div data-fwt-bind="input-status" class="fwt-media-input-status" style="display:none;"></div>
  </div>

  <div class="fwt-media-batch-bar" data-fwt-section="batch-bar" style="{{#if is_bulk}}{{else}}display:none;{{/if}}">
    <div class="fwt-batch-left">
      <span class="fwt-batch-count" data-fwt-bind="items-count">{{total_items}} item(s)</span>
      <label class="fwt-batch-label">Quality:</label>
      <select class="fwt-batch-select" data-fwt-action="set-global-quality">
        <option value="best">Best (1080p+)</option>
        <option value="720p">720p HD</option>
        <option value="480p">480p SD</option>
        <option value="360p">360p Mobile</option>
        <option value="audio">Audio Only</option>
      </select>
    </div>
    <div class="fwt-batch-right">
      <button type="button" data-fwt-action="fast-download-all" class="fwt-btn fwt-btn-fast-all">⚡ Fast Download All</button>
      <button type="button" data-fwt-action="convert-download-all" class="fwt-btn fwt-btn-convert-all">🔄 Convert & Download All</button>
      <button type="button" data-fwt-action="cancel-all" class="fwt-btn fwt-btn-cancel-all">✕ Cancel All</button>
    </div>
  </div>

  <div class="fwt-media-cards-list" data-fwt-cards-container>
{{#items}}
    <div class="fwt-media-item-card" data-fwt-item-id="{{@index}}" data-fwt-url="{{url}}" data-fwt-status="{{status}}">
      <div class="fwt-item-top">
        {{#if has_thumbnail}}
        <img src="{{thumbnail}}" alt="{{title}}" class="fwt-item-thumb">
        {{/if}}
        <div class="fwt-item-info">
          <div class="fwt-item-title">{{title}}</div>
          <div class="fwt-item-meta">
            {{#if duration}}<span class="fwt-item-duration">⏱ {{duration}}</span>{{/if}}
            {{#if channel}}<span class="fwt-item-channel">👤 {{channel}}</span>{{/if}}
            <span class="fwt-item-url" title="{{url}}">{{url}}</span>
          </div>
        </div>
        <div class="fwt-item-status-badge">
          <span class="fwt-status-pill" data-fwt-bind="status-pill">{{status_label}}</span>
        </div>
      </div>

      <div class="fwt-item-controls">
        <div class="fwt-item-format-select-wrap">
          <label class="fwt-format-label">Select Quality:</label>
          <select class="fwt-item-format-select" data-fwt-select="format">
            {{#formats}}
            <option value="{{format_id}}" data-url="{{download_url}}" data-stream="{{stream_type}}" data-quality="{{quality}}" {{#if @first}}selected{{/if}}>
              {{quality}} • {{ext}}{{#if filesize_formatted}} ({{filesize_formatted}}){{/if}} [{{stream_type}}]
            </option>
            {{/formats}}
          </select>
        </div>

        <div class="fwt-item-btn-group">
          <button type="button" data-fwt-action="fast-download" class="fwt-btn fwt-btn-fast">⚡ Fast Download</button>
          <button type="button" data-fwt-action="convert-download" class="fwt-btn fwt-btn-convert">🔄 Convert & Download</button>
          <button type="button" data-fwt-action="cancel" class="fwt-btn fwt-btn-cancel" style="display:none;">✕ Cancel</button>
          <button type="button" data-fwt-action="retry" class="fwt-btn fwt-btn-retry" style="display:none;">↻ Retry</button>
          <a data-fwt-action="download-file" href="{{download_url}}" download class="fwt-btn fwt-btn-save" style="display:none;">⬇ Save File</a>
        </div>
      </div>

      <div class="fwt-item-progress-wrap" data-fwt-section="progress" style="display:none;">
        <div class="fwt-item-progress-bar-track">
          <div class="fwt-item-progress-fill" data-fwt-progress-bar style="width: 0%;"></div>
        </div>
        <div class="fwt-item-progress-stats">
          <span data-fwt-bind="phase" class="fwt-progress-phase">Starting download...</span>
          <span data-fwt-bind="progress-pct" class="fwt-progress-pct">0%</span>
        </div>
      </div>

      <div data-fwt-bind="error-message" class="fwt-item-error" style="display:none;"></div>
    </div>
{{/items}}
  </div>

  <template data-fwt-template="card">
    <div class="fwt-media-item-card" data-fwt-item-id="{{@index}}" data-fwt-url="{{url}}" data-fwt-status="ready">
      <div class="fwt-item-top">
        {{#if has_thumbnail}}
        <img src="{{thumbnail}}" alt="{{title}}" class="fwt-item-thumb">
        {{/if}}
        <div class="fwt-item-info">
          <div class="fwt-item-title">{{title}}</div>
          <div class="fwt-item-meta">
            {{#if duration}}<span class="fwt-item-duration">⏱ {{duration}}</span>{{/if}}
            {{#if channel}}<span class="fwt-item-channel">👤 {{channel}}</span>{{/if}}
            <span class="fwt-item-url" title="{{url}}">{{url}}</span>
          </div>
        </div>
        <div class="fwt-item-status-badge">
          <span class="fwt-status-pill" data-fwt-bind="status-pill">Ready</span>
        </div>
      </div>

      <div class="fwt-item-controls">
        <div class="fwt-item-format-select-wrap">
          <label class="fwt-format-label">Select Quality:</label>
          <select class="fwt-item-format-select" data-fwt-select="format">
            {{#formats}}
            <option value="{{format_id}}" data-url="{{download_url}}" data-stream="{{stream_type}}" data-quality="{{quality}}" {{#if @first}}selected{{/if}}>
              {{quality}} • {{ext}}{{#if filesize_formatted}} ({{filesize_formatted}}){{/if}} [{{stream_type}}]
            </option>
            {{/formats}}
          </select>
        </div>

        <div class="fwt-item-btn-group">
          <button type="button" data-fwt-action="fast-download" class="fwt-btn fwt-btn-fast">⚡ Fast Download</button>
          <button type="button" data-fwt-action="convert-download" class="fwt-btn fwt-btn-convert">🔄 Convert & Download</button>
          <button type="button" data-fwt-action="cancel" class="fwt-btn fwt-btn-cancel" style="display:none;">✕ Cancel</button>
          <button type="button" data-fwt-action="retry" class="fwt-btn fwt-btn-retry" style="display:none;">↻ Retry</button>
          <a data-fwt-action="download-file" href="{{download_url}}" download class="fwt-btn fwt-btn-save" style="display:none;">⬇ Save File</a>
        </div>
      </div>

      <div class="fwt-item-progress-wrap" data-fwt-section="progress" style="display:none;">
        <div class="fwt-item-progress-bar-track">
          <div class="fwt-item-progress-fill" data-fwt-progress-bar style="width: 0%;"></div>
        </div>
        <div class="fwt-item-progress-stats">
          <span data-fwt-bind="phase" class="fwt-progress-phase">Starting download...</span>
          <span data-fwt-bind="progress-pct" class="fwt-progress-pct">0%</span>
        </div>
      </div>

      <div data-fwt-bind="error-message" class="fwt-item-error" style="display:none;"></div>
    </div>
  </template>

{{#if raw_json}}
  <details class="fwt-media-raw">
    <summary>View Technical Details (Raw JSON)</summary>
    <pre class="fwt-result-code">{{raw_json}}</pre>
  </details>
{{/if}}
</div>',
                'css_content'     => '.fwt-media-design { display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px; max-width: 100%; min-width: 0; box-sizing: border-box; }
.fwt-media-input-card { background: var(--fwt-card-bg, #ffffff); border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 10px; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; }
.fwt-media-input-label { font-size: 14px; font-weight: 700; color: var(--fwt-text, #1e293b); }
.fwt-media-textarea { width: 100%; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; line-height: 1.5; padding: 12px 14px; border: 1px solid var(--fwt-border, #cbd5e1); border-radius: 8px; box-sizing: border-box; resize: vertical; min-height: 90px; }
.fwt-media-textarea:focus { outline: none; border-color: var(--fwt-primary, #2563eb); box-shadow: 0 0 0 3px rgba(37,99,235,0.15); }
.fwt-media-input-actions { display: flex; gap: 10px; align-items: center; }
.fwt-media-input-status { font-size: 13px; padding: 8px 12px; border-radius: 6px; }
.fwt-media-batch-bar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; background: var(--fwt-bg, #f8fafc); border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 10px; padding: 12px 18px; }
.fwt-batch-left { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.fwt-batch-count { font-size: 13px; font-weight: 700; color: var(--fwt-primary, #2563eb); background: rgba(37,99,235,0.08); padding: 4px 10px; border-radius: 20px; }
.fwt-batch-label { font-size: 13px; font-weight: 600; color: var(--fwt-muted, #64748b); }
.fwt-batch-select { padding: 6px 12px; font-size: 13px; border-radius: 6px; border: 1px solid var(--fwt-border, #cbd5e1); background: #fff; }
.fwt-batch-right { display: flex; gap: 8px; flex-wrap: wrap; }
.fwt-btn-fast-all { background: #059669; color: #fff; }
.fwt-btn-fast-all:hover { background: #047857; }
.fwt-btn-convert-all { background: #d97706; color: #fff; }
.fwt-btn-convert-all:hover { background: #b45309; }
.fwt-btn-cancel-all { background: #64748b; color: #fff; }
.fwt-btn-cancel-all:hover { background: #475569; }
.fwt-media-cards-list { display: flex; flex-direction: column; gap: 14px; }
.fwt-media-item-card { background: var(--fwt-card-bg, #ffffff); border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 10px; padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; transition: border-color 0.2s, box-shadow 0.2s; }
.fwt-media-item-card:hover { border-color: #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.fwt-item-top { display: flex; align-items: center; gap: 16px; min-width: 0; }
.fwt-item-thumb { width: 96px; height: 60px; object-fit: cover; border-radius: 6px; flex-shrink: 0; background: #e2e8f0; }
.fwt-item-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.fwt-item-title { font-size: 15px; font-weight: 700; color: var(--fwt-text, #1e293b); line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fwt-item-meta { display: flex; align-items: center; gap: 12px; font-size: 12px; color: var(--fwt-muted, #64748b); flex-wrap: wrap; }
.fwt-item-url { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; opacity: 0.75; }
.fwt-item-status-badge { flex-shrink: 0; }
.fwt-status-pill { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 4px; background: #e2e8f0; color: #475569; }
.fwt-item-controls { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; padding-top: 6px; border-top: 1px solid var(--fwt-border, #f1f5f9); }
.fwt-item-format-select-wrap { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 240px; }
.fwt-format-label { font-size: 12px; font-weight: 600; color: var(--fwt-muted, #64748b); flex-shrink: 0; }
.fwt-item-format-select { flex: 1; padding: 7px 10px; font-size: 13px; border-radius: 6px; border: 1px solid var(--fwt-border, #cbd5e1); background: #fff; min-width: 180px; }
.fwt-item-btn-group { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.fwt-btn-fast { background: var(--fwt-primary, #2563eb); color: #fff; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 6px; }
.fwt-btn-fast:hover { background: #1d4ed8; }
.fwt-btn-convert { background: #d97706; color: #fff; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 6px; }
.fwt-btn-convert:hover { background: #b45309; }
.fwt-btn-cancel { background: #ef4444; color: #fff; font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 6px; }
.fwt-btn-retry { background: #64748b; color: #fff; font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 6px; }
.fwt-btn-save { background: #10b981; color: #fff !important; text-decoration: none !important; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 6px; display: inline-flex; align-items: center; }
.fwt-item-progress-wrap { display: flex; flex-direction: column; gap: 6px; }
.fwt-item-progress-bar-track { width: 100%; height: 8px; background: #e2e8f0; border-radius: 4px; overflow: hidden; }
.fwt-item-progress-fill { height: 100%; background: #2563eb; width: 0%; transition: width 0.25s ease; }
.fwt-item-progress-stats { display: flex; justify-content: space-between; font-size: 12px; color: var(--fwt-muted, #64748b); font-weight: 500; }
.fwt-item-error { font-size: 12px; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 8px 12px; }
.fwt-media-raw { margin-top: 14px; border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 8px; padding: 12px 16px; background: var(--fwt-bg, #f8fafc); max-width: 100%; min-width: 0; box-sizing: border-box; }
.fwt-media-raw summary { font-size: 13px; font-weight: 600; color: var(--fwt-muted, #64748b); cursor: pointer; user-select: none; }
.fwt-media-raw pre { margin-top: 10px; margin-bottom: 0; padding: 14px; background: var(--fwt-code-bg, #0f172a); color: var(--fwt-code-fg, #f8fafc); border-radius: 6px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; line-height: 1.5; white-space: pre; overflow-x: auto; overflow-y: visible; word-break: normal; overflow-wrap: normal; }
[data-fwt-status="working"] .fwt-status-pill { background: #3b82f6; color: #fff; }
[data-fwt-status="completed"] .fwt-status-pill { background: #10b981; color: #fff; }
[data-fwt-status="error"] .fwt-status-pill { background: #ef4444; color: #fff; }
@media (max-width: 640px) { .fwt-item-top { flex-direction: column; align-items: flex-start; } .fwt-item-controls { flex-direction: column; align-items: flex-start; } .fwt-item-format-select-wrap { width: 100%; } .fwt-item-btn-group { width: 100%; } .fwt-item-btn-group .fwt-btn { flex: 1; text-align: center; justify-content: center; } }
/* Built-in design dark-mode contract */
html.dark .fwt-media-design,body.dark .fwt-media-design,[data-theme="dark"] .fwt-media-design,.dark .fwt-media-design{color-scheme:dark;color:var(--fwt-text,#e2e8f0)}
html.dark .fwt-media-input-card,body.dark .fwt-media-input-card,[data-theme="dark"] .fwt-media-input-card,.dark .fwt-media-input-card,html.dark .fwt-media-item-card,body.dark .fwt-media-item-card,[data-theme="dark"] .fwt-media-item-card,.dark .fwt-media-item-card,html.dark .fwt-media-header-card,body.dark .fwt-media-header-card,[data-theme="dark"] .fwt-media-header-card,.dark .fwt-media-header-card{background:var(--fwt-card-bg,#111827)!important;color:var(--fwt-text,#e2e8f0)!important;border-color:var(--fwt-border,#334155)!important}
html.dark .fwt-media-textarea,body.dark .fwt-media-textarea,[data-theme="dark"] .fwt-media-textarea,.dark .fwt-media-textarea,html.dark .fwt-batch-select,body.dark .fwt-batch-select,[data-theme="dark"] .fwt-batch-select,.dark .fwt-batch-select,html.dark .fwt-item-format-select,body.dark .fwt-item-format-select,[data-theme="dark"] .fwt-item-format-select,.dark .fwt-item-format-select{background:var(--fwt-input-bg,#1e293b)!important;color:var(--fwt-text,#e2e8f0)!important;border-color:var(--fwt-border,#475569)!important;color-scheme:dark}
html.dark .fwt-media-input-label,body.dark .fwt-media-input-label,[data-theme="dark"] .fwt-media-input-label,.dark .fwt-media-input-label,html.dark .fwt-item-title,body.dark .fwt-item-title,[data-theme="dark"] .fwt-item-title,.dark .fwt-item-title{color:var(--fwt-text,#e2e8f0)!important}
html.dark .fwt-item-meta,body.dark .fwt-item-meta,[data-theme="dark"] .fwt-item-meta,.dark .fwt-item-meta,html.dark .fwt-format-label,body.dark .fwt-format-label,[data-theme="dark"] .fwt-format-label,.dark .fwt-format-label{color:var(--fwt-muted,#94a3b8)!important}
html.dark .fwt-status-pill,body.dark .fwt-status-pill,[data-theme="dark"] .fwt-status-pill,.dark .fwt-status-pill{background:#334155!important;color:#e2e8f0!important}
html.dark .fwt-item-controls,body.dark .fwt-item-controls,[data-theme="dark"] .fwt-item-controls,.dark .fwt-item-controls{border-top-color:var(--fwt-border,#334155)!important}
',
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
                'css_content'     => '.fwt-api-inspector { margin: 16px 0; border: 1px solid var(--fwt-border, #e2e8f0); border-radius: 8px; max-width: 100%; min-width: 0; box-sizing: border-box; }
.fwt-inspector-header { padding: 10px 14px; background: var(--fwt-bg, #f8fafc); border-bottom: 1px solid var(--fwt-border, #e2e8f0); }
.fwt-inspector-badge { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #2563eb; }
.fwt-api-inspector pre { margin: 0; padding: 16px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size: 13px; line-height: 1.5; background: var(--fwt-code-bg, #0f172a); color: var(--fwt-code-fg, #f8fafc); white-space: pre; overflow-x: auto; overflow-y: visible; word-break: normal; overflow-wrap: normal; max-width: 100%; min-width: 0; box-sizing: border-box; }',
                'js_content'      => null,
            ],
        ];

        foreach ($builtins as $b) {
            $existing = $this->findBySlug($b['slug']);
            if ($existing === null) {
                try {
                    $this->create($b);
                } catch (Throwable) {
                }
            } elseif ($existing->isBuiltin()) {
                try {
                    $this->update($existing->getId(), [
                        'template_markup' => $b['template_markup'],
                        'css_content'     => $b['css_content'],
                        'version'         => $b['version'],
                    ]);
                } catch (Throwable) {
                }
            }
        }
    }
}
