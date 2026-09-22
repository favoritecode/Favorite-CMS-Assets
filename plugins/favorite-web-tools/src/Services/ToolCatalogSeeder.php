<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Core\Database;
use FavoriteCMS\Tools\Models\PythonService;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Support\AccessMode;
use FavoriteCMS\Tools\Support\EngineType;
use FavoriteCMS\Tools\Support\ToolStatus;

class ToolCatalogSeeder
{
    protected Database $db;
    protected CategoryRepository $catRepo;
    protected ToolRepository $toolRepo;
    protected PythonServiceRepository $pythonRepo;

    public function __construct(
        Database $db,
        ?CategoryRepository $catRepo = null,
        ?ToolRepository $toolRepo = null,
        ?PythonServiceRepository $pythonRepo = null
    ) {
        $this->db = $db;
        $this->catRepo = $catRepo ?? new CategoryRepository($db);
        $this->toolRepo = $toolRepo ?? new ToolRepository($db);
        $this->pythonRepo = $pythonRepo ?? new PythonServiceRepository($db);
    }

    public function seedIfEmpty(): void
    {
        $this->seedCategories();
        $this->seedDefaultPythonServices();

        if ($this->toolRepo->countByStatus()['total'] === 0) {
            $this->seedTools();
        }

        $this->seedMediaDownloaderTool();
    }

    public function seedDefaultPythonServices(): ?PythonService
    {
        $existing = $this->pythonRepo->findBySlug('favorite-media-downloader-api');
        if ($existing) {
            return $existing;
        }

        foreach ($this->pythonRepo->all() as $srv) {
            if ($srv->name === 'Favorite Media Downloader API') {
                return $srv;
            }
        }

        return $this->pythonRepo->create([
            'name'                  => 'Favorite Media Downloader API',
            'slug'                  => 'favorite-media-downloader-api',
            'description'           => 'External Media Downloader API for multi-platform video and audio streams.',
            'base_url'              => 'https://server.favoriteweb.net',
            'default_endpoint_path' => '/download/api',
            'http_method'           => 'GET',
            'auth_type'             => 'none',
            'timeout'               => 30,
            'status'                => 'active',
        ]);
    }

    public function seedMediaDownloaderTool(): ?Tool
    {
        $existing = $this->toolRepo->findBySlug('favorite-media-downloader');
        $service = $this->seedDefaultPythonServices();
        $catIds = $this->seedCategories();

        $toolData = [
            'name'          => 'Favorite Media Downloader',
            'slug'          => 'favorite-media-downloader',
            'description'   => 'Download video and audio streams from supported media and social platforms.',
            'category_id'   => $catIds['python'] ?? null,
            'engine'        => EngineType::PYTHON_API,
            'access_mode'   => AccessMode::FREE,
            'status'        => ToolStatus::ACTIVE,
            'input_schema'  => [
                'type'       => 'object',
                'required'   => ['video_url'],
                'properties' => [
                    'video_url' => [
                        'type'        => 'string',
                        'format'      => 'uri',
                        'title'       => 'Video / Post URL',
                        'placeholder' => 'https://...',
                    ],
                ],
            ],
            'output_schema' => [
                'type'  => 'JSON',
                'label' => 'Extracted Media Formats & Stream URLs',
            ],
            'configuration' => [
                'python_service_id' => $service ? $service->id : null,
                'python_endpoint'   => '/download/api',
                'http_method'       => 'GET',
                'param_mapping'     => ['video_url' => 'url'],
                'endpoints'         => [
                    'api'      => '/download/api',
                    'job_file' => '/download/job-file/{JOB_ID}',
                ],
            ],
            'display_order' => 10,
            'icon'          => 'download',
        ];

        if ($existing) {
            $needsUpdate = false;
            $updates = [];
            if (empty($existing->input_schema)) {
                $updates['input_schema'] = $toolData['input_schema'];
                $needsUpdate = true;
            }
            if (empty($existing->output_schema)) {
                $updates['output_schema'] = $toolData['output_schema'];
                $needsUpdate = true;
            }
            if (empty($existing->configuration['param_mapping']) || empty($existing->configuration['python_service_id'])) {
                $updates['configuration'] = array_merge(
                    $existing->configuration,
                    $toolData['configuration'],
                    ['python_service_id' => $service ? $service->id : ($existing->python_service_id ?: null)]
                );
                $needsUpdate = true;
            }
            if ($needsUpdate && $existing->id) {
                $this->toolRepo->update($existing->id, $updates);
                return $this->toolRepo->findById($existing->id);
            }
            return $existing;
        }

        return $this->toolRepo->create($toolData);
    }

    public function seedCategories(): array
    {
        $cats = [
            'html' => [
                'name'          => 'HTML',
                'slug'          => 'html',
                'description'   => 'HTML formatting, minification, validation, encoding, and preview utilities.',
                'icon'          => 'code',
                'display_order' => 10,
            ],
            'css' => [
                'name'          => 'CSS',
                'slug'          => 'css',
                'description'   => 'CSS formatting, minification, vendor prefixing, color conversion, and styling tools.',
                'icon'          => 'palette',
                'display_order' => 20,
            ],
            'javascript' => [
                'name'          => 'JavaScript',
                'slug'          => 'javascript',
                'description'   => 'JavaScript code formatting, validation, and minification tools.',
                'icon'          => 'terminal',
                'display_order' => 30,
            ],
            'developer' => [
                'name'          => 'Developer',
                'slug'          => 'developer',
                'description'   => 'Essential developer utilities: JSON, Base64, URLs, UUIDs, Hashes, Timestamps, and Regex.',
                'icon'          => 'cpu',
                'display_order' => 40,
            ],
            'text' => [
                'name'          => 'Text',
                'slug'          => 'text',
                'description'   => 'Text manipulation tools: case conversion, counting, line sorting, and deduplication.',
                'icon'          => 'file-text',
                'display_order' => 50,
            ],
            'php' => [
                'name'          => 'PHP',
                'slug'          => 'php',
                'description'   => 'Controlled server-side PHP formatting, validation, and minification utilities.',
                'icon'          => 'server',
                'display_order' => 60,
            ],
            'python' => [
                'name'          => 'Python',
                'slug'          => 'python',
                'description'   => 'Extensible external Python API service tools.',
                'icon'          => 'activity',
                'display_order' => 70,
            ],
        ];

        $catIds = [];
        foreach ($cats as $key => $data) {
            $existing = $this->catRepo->findBySlug($data['slug']);
            if ($existing) {
                $catIds[$key] = $existing->id;
            } else {
                $catIds[$key] = $this->catRepo->create($data);
            }
        }

        return $catIds;
    }

    public function seedTools(): void
    {
        $catIds = $this->seedCategories();

        $tools = [
            // HTML Category
            [
                'name'          => 'HTML Formatter',
                'slug'          => 'html-formatter',
                'description'   => 'Format and beautify HTML source code with clean indentation.',
                'category_id'   => $catIds['html'] ?? null,
                'engine'        => EngineType::HTML,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'HTML Code', 'type' => 'textarea', 'required' => true, 'placeholder' => '<div class="example"><h1>Hello</h1></div>'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Formatted HTML'],
                'configuration' => ['action' => 'format'],
                'display_order' => 10,
                'icon'          => 'code',
            ],
            [
                'name'          => 'HTML Minifier',
                'slug'          => 'html-minifier',
                'description'   => 'Minify HTML source code to reduce page size and bandwidth.',
                'category_id'   => $catIds['html'] ?? null,
                'engine'        => EngineType::HTML,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'HTML Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Minified HTML'],
                'configuration' => ['action' => 'minify'],
                'display_order' => 20,
                'icon'          => 'minimize',
            ],
            [
                'name'          => 'HTML Validator',
                'slug'          => 'html-validator',
                'description'   => 'Validate HTML structure and identify syntax or malformed tag errors.',
                'category_id'   => $catIds['html'] ?? null,
                'engine'        => EngineType::HTML,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'HTML Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'JSON', 'label' => 'Validation Results'],
                'configuration' => ['action' => 'validate'],
                'display_order' => 30,
                'icon'          => 'check-circle',
            ],
            [
                'name'          => 'HTML Entity Encoder',
                'slug'          => 'html-encoder',
                'description'   => 'Convert special characters to their corresponding HTML entities.',
                'category_id'   => $catIds['html'] ?? null,
                'engine'        => EngineType::HTML,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Text or Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Encoded HTML Entities'],
                'configuration' => ['action' => 'encode'],
                'display_order' => 40,
                'icon'          => 'lock',
            ],
            [
                'name'          => 'HTML Entity Decoder',
                'slug'          => 'html-decoder',
                'description'   => 'Decode HTML entities back to readable text and characters.',
                'category_id'   => $catIds['html'] ?? null,
                'engine'        => EngineType::HTML,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'HTML Entities', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Decoded Text'],
                'configuration' => ['action' => 'decode'],
                'display_order' => 50,
                'icon'          => 'unlock',
            ],
            [
                'name'          => 'HTML Live Preview',
                'slug'          => 'html-preview',
                'description'   => 'Safely preview rendered HTML in an isolated sandboxed container.',
                'category_id'   => $catIds['html'] ?? null,
                'engine'        => EngineType::HTML,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'HTML Markup', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'HTML', 'label' => 'Live Preview'],
                'configuration' => ['action' => 'preview'],
                'display_order' => 60,
                'icon'          => 'eye',
            ],

            // CSS Category
            [
                'name'          => 'CSS Formatter',
                'slug'          => 'css-formatter',
                'description'   => 'Format and beautify messy CSS code with standard indentation.',
                'category_id'   => $catIds['css'] ?? null,
                'engine'        => EngineType::CSS,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'CSS Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Formatted CSS'],
                'configuration' => ['action' => 'format'],
                'display_order' => 10,
                'icon'          => 'palette',
            ],
            [
                'name'          => 'CSS Minifier',
                'slug'          => 'css-minifier',
                'description'   => 'Minify and compress CSS stylesheets to improve website load speed.',
                'category_id'   => $catIds['css'] ?? null,
                'engine'        => EngineType::CSS,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'CSS Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Minified CSS'],
                'configuration' => ['action' => 'minify'],
                'display_order' => 20,
                'icon'          => 'minimize',
            ],
            [
                'name'          => 'CSS Validator',
                'slug'          => 'css-validator',
                'description'   => 'Validate CSS stylesheets for syntax errors and unclosed curly braces.',
                'category_id'   => $catIds['css'] ?? null,
                'engine'        => EngineType::CSS,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'CSS Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'JSON', 'label' => 'Validation Results'],
                'configuration' => ['action' => 'validate'],
                'display_order' => 30,
                'icon'          => 'check-circle',
            ],
            [
                'name'          => 'CSS Autoprefixer',
                'slug'          => 'css-prefixer',
                'description'   => 'Automatically add cross-browser vendor prefixes to CSS properties.',
                'category_id'   => $catIds['css'] ?? null,
                'engine'        => EngineType::CSS,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'CSS Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Prefixed CSS'],
                'configuration' => ['action' => 'prefix'],
                'display_order' => 40,
                'icon'          => 'layers',
            ],
            [
                'name'          => 'CSS Color Converter',
                'slug'          => 'css-color-converter',
                'description'   => 'Convert colors between HEX, RGB, RGBA, HSL, and HSLA formats.',
                'category_id'   => $catIds['css'] ?? null,
                'engine'        => EngineType::CSS,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Color Code', 'type' => 'text', 'required' => true, 'placeholder' => '#3b82f6 or rgb(59, 130, 246)'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Converted Color Values'],
                'configuration' => ['action' => 'color'],
                'display_order' => 50,
                'icon'          => 'sun',
            ],
            [
                'name'          => 'CSS Live Preview',
                'slug'          => 'css-preview',
                'description'   => 'Preview CSS styles rendered live on interactive preview elements in an isolated sandbox.',
                'category_id'   => $catIds['css'] ?? null,
                'engine'        => EngineType::CSS,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Custom CSS', 'type' => 'textarea', 'required' => true, 'placeholder' => '.preview-box { background: #2563eb; color: #fff; padding: 20px; }'],
                    ],
                ],
                'output_schema' => ['type' => 'HTML', 'label' => 'Live Styled Preview'],
                'configuration' => ['action' => 'preview'],
                'display_order' => 60,
                'icon'          => 'eye',
            ],

            // JavaScript Category
            [
                'name'          => 'JavaScript Formatter',
                'slug'          => 'javascript-formatter',
                'description'   => 'Format and beautify JavaScript code with consistent indentation.',
                'category_id'   => $catIds['javascript'] ?? null,
                'engine'        => EngineType::JAVASCRIPT,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'JavaScript Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Formatted JavaScript'],
                'configuration' => ['action' => 'format'],
                'display_order' => 10,
                'icon'          => 'terminal',
            ],
            [
                'name'          => 'JavaScript Minifier',
                'slug'          => 'javascript-minifier',
                'description'   => 'Minify JavaScript by stripping comments, spaces, and formatting.',
                'category_id'   => $catIds['javascript'] ?? null,
                'engine'        => EngineType::JAVASCRIPT,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'JavaScript Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Minified JavaScript'],
                'configuration' => ['action' => 'minify'],
                'display_order' => 20,
                'icon'          => 'minimize',
            ],
            [
                'name'          => 'JavaScript Validator',
                'slug'          => 'javascript-validator',
                'description'   => 'Validate JavaScript code for matching braces, parentheses, and syntax balance.',
                'category_id'   => $catIds['javascript'] ?? null,
                'engine'        => EngineType::JAVASCRIPT,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'JavaScript Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'JSON', 'label' => 'Syntax Check Results'],
                'configuration' => ['action' => 'validate'],
                'display_order' => 30,
                'icon'          => 'check-circle',
            ],

            // Developer Category
            [
                'name'          => 'JSON Formatter',
                'slug'          => 'json-formatter',
                'description'   => 'Format, indent, and validate JSON data structures.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Raw JSON', 'type' => 'textarea', 'required' => true, 'placeholder' => '{"id":1,"name":"Favorite CMS"}'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Formatted JSON'],
                'configuration' => ['php_handler' => 'json_formatter'],
                'display_order' => 10,
                'icon'          => 'cpu',
            ],
            [
                'name'          => 'JSON Validator',
                'slug'          => 'json-validator',
                'description'   => 'Validate JSON syntax and inspect root types and element counts.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'JSON Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'JSON', 'label' => 'Validation Analysis'],
                'configuration' => ['php_handler' => 'json_validator'],
                'display_order' => 20,
                'icon'          => 'check-circle',
            ],
            [
                'name'          => 'JSON Minifier',
                'slug'          => 'json-minifier',
                'description'   => 'Minify and compact JSON data by removing whitespace and line breaks.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'JSON Data', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Minified JSON'],
                'configuration' => ['php_handler' => 'json_minifier'],
                'display_order' => 30,
                'icon'          => 'minimize',
            ],
            [
                'name'          => 'Base64 Encoder',
                'slug'          => 'base64-encoder',
                'description'   => 'Encode strings and data to Base64 format with optional URL-safe encoding.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Text to Encode', 'type' => 'textarea', 'required' => true],
                        ['name' => 'url_safe', 'label' => 'URL-Safe Base64', 'type' => 'checkbox', 'required' => false],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Base64 Encoded String'],
                'configuration' => ['php_handler' => 'base64_encoder'],
                'display_order' => 40,
                'icon'          => 'lock',
            ],
            [
                'name'          => 'Base64 Decoder',
                'slug'          => 'base64-decoder',
                'description'   => 'Decode Base64 encoded strings back into original text.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Base64 Text', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Decoded Plaintext'],
                'configuration' => ['php_handler' => 'base64_decoder'],
                'display_order' => 50,
                'icon'          => 'unlock',
            ],
            [
                'name'          => 'URL Encoder',
                'slug'          => 'url-encoder',
                'description'   => 'Encode query parameters and strings safely for URLs.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Text / URL', 'type' => 'textarea', 'required' => true],
                        ['name' => 'rfc3986', 'label' => 'RFC 3986 encoding (rawurlencode)', 'type' => 'checkbox', 'required' => false],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'URL Encoded String'],
                'configuration' => ['php_handler' => 'url_encoder'],
                'display_order' => 60,
                'icon'          => 'link',
            ],
            [
                'name'          => 'URL Decoder',
                'slug'          => 'url-decoder',
                'description'   => 'Decode percent-encoded URL query parameters back to plain characters.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Encoded URL / Query', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Decoded URL String'],
                'configuration' => ['php_handler' => 'url_decoder'],
                'display_order' => 70,
                'icon'          => 'link-2',
            ],
            [
                'name'          => 'UUID Generator',
                'slug'          => 'uuid-generator',
                'description'   => 'Generate cryptographically random RFC 4122 compliant UUID v4 identifiers.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'count', 'label' => 'Quantity (1 - 100)', 'type' => 'number', 'required' => false, 'default' => 5],
                        ['name' => 'uppercase', 'label' => 'Uppercase', 'type' => 'checkbox', 'required' => false],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Generated UUIDs'],
                'configuration' => ['php_handler' => 'uuid_generator'],
                'display_order' => 80,
                'icon'          => 'hash',
            ],
            [
                'name'          => 'Hash Generator',
                'slug'          => 'hash-generator',
                'description'   => 'Compute cryptographic hashes (MD5, SHA-1, SHA-256, SHA-512) for text.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Text to Hash', 'type' => 'textarea', 'required' => true],
                        ['name' => 'algorithm', 'label' => 'Algorithm (all, md5, sha1, sha256, sha512)', 'type' => 'text', 'required' => false, 'default' => 'all'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Cryptographic Hashes'],
                'configuration' => ['php_handler' => 'hash_generator'],
                'display_order' => 90,
                'icon'          => 'shield',
            ],
            [
                'name'          => 'Unix Timestamp Converter',
                'slug'          => 'timestamp-converter',
                'description'   => 'Convert between Unix epoch timestamps and human-readable dates across timezones.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Timestamp or Date String', 'type' => 'text', 'required' => false, 'placeholder' => 'Leave blank for current time, or e.g. 1727000000'],
                        ['name' => 'timezone', 'label' => 'Timezone', 'type' => 'text', 'required' => false, 'default' => 'UTC'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Converted Date & Time'],
                'configuration' => ['php_handler' => 'timestamp_converter'],
                'display_order' => 100,
                'icon'          => 'clock',
            ],
            [
                'name'          => 'Regex Tester',
                'slug'          => 'regex-tester',
                'description'   => 'Test regular expressions against sample text and inspect matches and captured groups.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'pattern', 'label' => 'Regular Expression', 'type' => 'text', 'required' => true, 'placeholder' => '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i'],
                        ['name' => 'input', 'label' => 'Test Subject String', 'type' => 'textarea', 'required' => true, 'placeholder' => 'Contact us at support@favoriteweb.net or info@example.com'],
                    ],
                ],
                'output_schema' => ['type' => 'JSON', 'label' => 'Match Results'],
                'configuration' => ['php_handler' => 'regex_tester'],
                'display_order' => 110,
                'icon'          => 'search',
            ],
            [
                'name'          => 'Lorem Ipsum Generator',
                'slug'          => 'lorem-ipsum-generator',
                'description'   => 'Generate placeholder lorem ipsum text by paragraphs, sentences, or word counts.',
                'category_id'   => $catIds['developer'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'count', 'label' => 'Count', 'type' => 'number', 'required' => false, 'default' => 3],
                        ['name' => 'type', 'label' => 'Unit (paragraphs, sentences, words)', 'type' => 'text', 'required' => false, 'default' => 'paragraphs'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Generated Dummy Text'],
                'configuration' => ['php_handler' => 'lorem_ipsum_generator'],
                'display_order' => 120,
                'icon'          => 'file-text',
            ],

            // Text Category
            [
                'name'          => 'Text Case Converter',
                'slug'          => 'text-case-converter',
                'description'   => 'Convert text between UPPERCASE, lowercase, Title Case, camelCase, snake_case, and kebab-case.',
                'category_id'   => $catIds['text'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Source Text', 'type' => 'textarea', 'required' => true],
                        ['name' => 'case', 'label' => 'Target Case (upper, lower, title, camel, snake, kebab)', 'type' => 'text', 'required' => false, 'default' => 'title'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Converted Text'],
                'configuration' => ['php_handler' => 'text_case_converter'],
                'display_order' => 10,
                'icon'          => 'type',
            ],
            [
                'name'          => 'Text & Word Counter',
                'slug'          => 'text-counter',
                'description'   => 'Count words, characters, lines, paragraphs, and estimate reading/speaking times.',
                'category_id'   => $catIds['text'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Text Content', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Word and Character Statistics'],
                'configuration' => ['php_handler' => 'text_counter'],
                'display_order' => 20,
                'icon'          => 'bar-chart-2',
            ],
            [
                'name'          => 'Duplicate Lines Remover',
                'slug'          => 'duplicate-lines-remover',
                'description'   => 'Remove duplicate lines from lists or text while preserving natural order.',
                'category_id'   => $catIds['text'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'List of Lines', 'type' => 'textarea', 'required' => true],
                        ['name' => 'case_sensitive', 'label' => 'Case Sensitive', 'type' => 'checkbox', 'required' => false],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Deduplicated Lines'],
                'configuration' => ['php_handler' => 'duplicate_lines_remover'],
                'display_order' => 30,
                'icon'          => 'filter',
            ],
            [
                'name'          => 'Line Sorter',
                'slug'          => 'line-sorter',
                'description'   => 'Sort lines alphabetically, in reverse, by length, or using natural sorting.',
                'category_id'   => $catIds['text'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Lines to Sort', 'type' => 'textarea', 'required' => true],
                        ['name' => 'order', 'label' => 'Order (asc, desc, natural, length)', 'type' => 'text', 'required' => false, 'default' => 'asc'],
                        ['name' => 'ignore_case', 'label' => 'Case Insensitive', 'type' => 'checkbox', 'required' => false],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Sorted Lines'],
                'configuration' => ['php_handler' => 'line_sorter'],
                'display_order' => 40,
                'icon'          => 'align-left',
            ],
            [
                'name'          => 'Find and Replace',
                'slug'          => 'find-and-replace',
                'description'   => 'Find and replace occurrences of text with support for case matching and regex.',
                'category_id'   => $catIds['text'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Source Text', 'type' => 'textarea', 'required' => true],
                        ['name' => 'find', 'label' => 'Find String / Pattern', 'type' => 'text', 'required' => true],
                        ['name' => 'replace', 'label' => 'Replace With', 'type' => 'text', 'required' => false],
                        ['name' => 'case_sensitive', 'label' => 'Case Sensitive', 'type' => 'checkbox', 'required' => false],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Processed Output'],
                'configuration' => ['php_handler' => 'find_and_replace'],
                'display_order' => 50,
                'icon'          => 'refresh-cw',
            ],

            // PHP Category
            [
                'name'          => 'PHP Code Formatter',
                'slug'          => 'php-formatter',
                'description'   => 'Format and beautify PHP code cleanly using the tokenizer.',
                'category_id'   => $catIds['php'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'PHP Code', 'type' => 'textarea', 'required' => true, 'placeholder' => '<?php function hello(){return "world";} ?>'],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Formatted PHP Code'],
                'configuration' => ['php_handler' => 'php_formatter'],
                'display_order' => 10,
                'icon'          => 'server',
            ],
            [
                'name'          => 'PHP Syntax Validator',
                'slug'          => 'php-validator',
                'description'   => 'Check PHP code for syntax errors and unbalanced structures safely.',
                'category_id'   => $catIds['php'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'PHP Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'JSON', 'label' => 'PHP Syntax Results'],
                'configuration' => ['php_handler' => 'php_validator'],
                'display_order' => 20,
                'icon'          => 'check-circle',
            ],
            [
                'name'          => 'PHP Code Minifier',
                'slug'          => 'php-minifier',
                'description'   => 'Minify PHP scripts by safely stripping comments and unnecessary whitespace.',
                'category_id'   => $catIds['php'] ?? null,
                'engine'        => EngineType::PHP,
                'access_mode'   => AccessMode::FREE,
                'status'        => ToolStatus::ACTIVE,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'PHP Code', 'type' => 'textarea', 'required' => true],
                    ],
                ],
                'output_schema' => ['type' => 'TEXT', 'label' => 'Minified PHP Code'],
                'configuration' => ['php_handler' => 'php_minifier'],
                'display_order' => 30,
                'icon'          => 'minimize',
            ],

            // Python Category (Seeded as DRAFT + LOGIN_REQUIRED per doc 30 section 14.1)
            [
                'name'          => 'Python Service Test',
                'slug'          => 'python-service-test',
                'description'   => 'Validation tool for testing connectivity to configured external Python API services.',
                'category_id'   => $catIds['python'] ?? null,
                'engine'        => EngineType::PYTHON_API,
                'access_mode'   => AccessMode::LOGIN_REQUIRED,
                'status'        => ToolStatus::DRAFT,
                'input_schema'  => [
                    'fields' => [
                        ['name' => 'input', 'label' => 'Payload', 'type' => 'textarea', 'required' => false],
                    ],
                ],
                'output_schema' => ['type' => 'JSON', 'label' => 'Service Response'],
                'configuration' => ['endpoint' => '/health'],
                'display_order' => 10,
                'icon'          => 'activity',
            ],
        ];

        foreach ($tools as $tool) {
            $existing = $this->toolRepo->findBySlug($tool['slug']);
            if (!$existing) {
                $this->toolRepo->create($tool);
            }
        }
    }
}

