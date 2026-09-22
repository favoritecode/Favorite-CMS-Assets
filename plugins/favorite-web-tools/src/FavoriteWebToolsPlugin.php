<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Database;
use FavoriteCMS\Core\Migrator;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Tools\Controllers\Admin\AdminCategoryController;
use FavoriteCMS\Tools\Controllers\Admin\AdminDashboardController;
use FavoriteCMS\Tools\Controllers\Admin\AdminPythonServiceController;
use FavoriteCMS\Tools\Controllers\Admin\AdminToolController;
use FavoriteCMS\Tools\Controllers\Api\DownloadApiController;
use FavoriteCMS\Tools\Controllers\Api\ToolApiController;
use FavoriteCMS\Tools\Controllers\Api\ToolExecutionApiController;
use FavoriteCMS\Tools\Controllers\Frontend\CatalogController;
use FavoriteCMS\Tools\Controllers\Frontend\ToolController;
use FavoriteCMS\Tools\Engines\EngineResolver;
use FavoriteCMS\Tools\Engines\PythonApiEngine;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Services\AccessControlService;
use FavoriteCMS\Tools\Services\DownloadManagerService;
use FavoriteCMS\Tools\Services\PythonClientService;
use FavoriteCMS\Tools\Services\ToolCatalogSeeder;
use FavoriteCMS\Tools\Services\ToolExecutionService;
use FavoriteCMS\Tools\Services\ToolRegistryService;
use Throwable;

final class FavoriteWebToolsPlugin
{
    public const TABLES = [
        'favorite_web_tool_categories',
        'favorite_web_tool_python_services',
        'favorite_web_tools',
    ];

    private static ?self $instance = null;
    private Application $app;
    private bool $booted = false;
    private static bool $migrationsEnsured = false;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getInstance(?Application $app = null): ?self
    {
        if (self::$instance === null && $app !== null) {
            self::$instance = new self($app);
        }
        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
        self::$migrationsEnsured = false;
    }

    public static function bootstrap(Application $app): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $plugin = new self($app);
        $plugin->register();
        $plugin->boot();
        self::$instance = $plugin;
        return $plugin;
    }

    public function register(): void
    {
        // 1. Register prefixable tables with Database
        if ($this->app->has(Database::class)) {
            $db = $this->app->make(Database::class);
            if (method_exists($db, 'registerPrefixableTables')) {
                $db->registerPrefixableTables(self::TABLES);
            }
        }

        // 2. Bind Repositories
        $this->app->singleton(CategoryRepository::class, function ($app): CategoryRepository {
            return new CategoryRepository($app->make(Database::class));
        });

        $this->app->singleton(PythonServiceRepository::class, function ($app): PythonServiceRepository {
            return new PythonServiceRepository($app->make(Database::class));
        });

        $this->app->singleton(ToolRepository::class, function ($app): ToolRepository {
            return new ToolRepository($app->make(Database::class));
        });

        // 3. Bind Services
        $this->app->singleton(PythonClientService::class, function (): PythonClientService {
            return new PythonClientService();
        });

        $this->app->singleton(AccessControlService::class, function ($app): AccessControlService {
            return new AccessControlService();
        });

        $this->app->singleton(DownloadManagerService::class, function (): DownloadManagerService {
            return new DownloadManagerService();
        });

        $this->app->singleton(ToolRegistryService::class, function ($app): ToolRegistryService {
            return new ToolRegistryService(
                $app->make(ToolRepository::class),
                $app->make(CategoryRepository::class),
                $app->make(PythonServiceRepository::class)
            );
        });

        $this->app->singleton(EngineResolver::class, function ($app): EngineResolver {
            $pythonEngine = new PythonApiEngine(
                $app->make(PythonServiceRepository::class),
                $app->make(PythonClientService::class)
            );
            return new EngineResolver(
                null,
                null,
                null,
                null,
                $pythonEngine
            );
        });

        $this->app->singleton(ToolExecutionService::class, function ($app): ToolExecutionService {
            return new ToolExecutionService(
                $app->make(ToolRepository::class),
                $app->make(AccessControlService::class),
                $app->make(EngineResolver::class),
                $app->make(DownloadManagerService::class)
            );
        });

        $this->app->singleton(ToolCatalogSeeder::class, function ($app): ToolCatalogSeeder {
            return new ToolCatalogSeeder(
                $app->make(Database::class),
                $app->make(CategoryRepository::class),
                $app->make(ToolRepository::class),
                $app->make(PythonServiceRepository::class)
            );
        });

        // 4. Bind Controllers
        $this->app->singleton(CatalogController::class, function ($app): CatalogController {
            return new CatalogController(
                $app,
                $app->make(ToolRegistryService::class),
                $app->make(CategoryRepository::class),
                $app->make(AccessControlService::class)
            );
        });

        $this->app->singleton(ToolController::class, function ($app): ToolController {
            return new ToolController(
                $app,
                $app->make(ToolRegistryService::class),
                $app->make(CategoryRepository::class),
                $app->make(AccessControlService::class)
            );
        });

        $this->app->singleton(ToolApiController::class, function ($app): ToolApiController {
            return new ToolApiController(
                $app,
                $app->make(ToolRegistryService::class),
                $app->make(CategoryRepository::class)
            );
        });

        $this->app->singleton(ToolExecutionApiController::class, function ($app): ToolExecutionApiController {
            return new ToolExecutionApiController(
                $app,
                $app->make(ToolExecutionService::class)
            );
        });

        $this->app->singleton(DownloadApiController::class, function ($app): DownloadApiController {
            return new DownloadApiController(
                $app,
                $app->make(DownloadManagerService::class)
            );
        });

        $this->app->singleton(AdminDashboardController::class, function ($app): AdminDashboardController {
            return new AdminDashboardController(
                $app,
                $app->make(ToolRepository::class),
                $app->make(CategoryRepository::class),
                $app->make(PythonServiceRepository::class)
            );
        });

        $this->app->singleton(AdminToolController::class, function ($app): AdminToolController {
            return new AdminToolController(
                $app,
                $app->make(ToolRepository::class),
                $app->make(CategoryRepository::class),
                $app->make(PythonServiceRepository::class),
                $app->make(ToolExecutionService::class)
            );
        });

        $this->app->singleton(AdminCategoryController::class, function ($app): AdminCategoryController {
            return new AdminCategoryController(
                $app,
                $app->make(CategoryRepository::class),
                $app->make(ToolRepository::class)
            );
        });

        $this->app->singleton(AdminPythonServiceController::class, function ($app): AdminPythonServiceController {
            return new AdminPythonServiceController(
                $app,
                $app->make(PythonServiceRepository::class),
                $app->make(ToolRepository::class),
                $app->make(PythonClientService::class)
            );
        });
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        $this->ensureMigrations();
        $this->seedIfEmpty();

        // 1. Register Admin Menus & Submenus
        if (function_exists('add_admin_menu')) {
            $toolsHandler = function (Request $request) {
                $controller = $this->app->make(AdminToolController::class);
                return $controller->handle($request);
            };

            $categoryHandler = function (Request $request) {
                $controller = $this->app->make(AdminCategoryController::class);
                return $controller->handle($request);
            };

            $pythonHandler = function (Request $request) {
                $controller = $this->app->make(AdminPythonServiceController::class);
                return $controller->handle($request);
            };

            $dashboardHandler = function (Request $request) {
                $controller = $this->app->make(AdminDashboardController::class);
                return $controller->handle($request);
            };

            add_admin_menu(
                'favorite-web-tools',
                'Web Tools',
                '🛠️',
                $toolsHandler,
                'manage_options',
                57
            );

            if (function_exists('add_admin_submenu')) {
                add_admin_submenu(
                    'favorite-web-tools',
                    'favorite-web-tools',
                    'All Tools',
                    $toolsHandler,
                    'manage_options'
                );

                add_admin_submenu(
                    'favorite-web-tools',
                    'favorite-web-tools-categories',
                    'Categories',
                    $categoryHandler,
                    'manage_options'
                );

                add_admin_submenu(
                    'favorite-web-tools',
                    'favorite-web-tools-python-services',
                    'Python Services',
                    $pythonHandler,
                    'manage_options'
                );

                add_admin_submenu(
                    'favorite-web-tools',
                    'favorite-web-tools-dashboard',
                    'Dashboard',
                    $dashboardHandler,
                    'manage_options'
                );
            }
        }

        // 2. Register Dynamic Frontend & API Routes
        if (function_exists('add_route')) {
            // Frontend Catalog & Category
            add_route('GET', '/tools', function (Request $request) {
                $controller = $this->app->make(CatalogController::class);
                return $controller->index($request);
            });

            add_route('GET', '/tools/category/{slug}', function (Request $request, string $slug) {
                $controller = $this->app->make(CatalogController::class);
                return $controller->category($request, $slug);
            });

            // Frontend Individual Tool Page
            add_route('GET', '/tools/{slug}', function (Request $request, string $slug) {
                $controller = $this->app->make(ToolController::class);
                return $controller->show($request, $slug);
            });

            // Public API
            add_route('GET', '/api/tools', function (Request $request) {
                $controller = $this->app->make(ToolApiController::class);
                return $controller->index($request);
            });

            add_route('GET', '/api/tools/categories', function (Request $request) {
                $controller = $this->app->make(ToolApiController::class);
                return $controller->categories($request);
            });

            add_route('GET', '/api/tools/categories/{slug}', function (Request $request, string $slug) {
                $controller = $this->app->make(ToolApiController::class);
                return $controller->categoryDetail($request, $slug);
            });

            add_route('GET', '/api/tools/{slug}', function (Request $request, string $slug) {
                $controller = $this->app->make(ToolApiController::class);
                return $controller->show($request, $slug);
            });

            // Execution API
            add_route('POST', '/api/tools/{slug}/execute', function (Request $request, string $slug) {
                $controller = $this->app->make(ToolExecutionApiController::class);
                return $controller->execute($request, $slug);
            });

            // Download API
            add_route('GET', '/api/tools/download/{reference}', function (Request $request, string $reference) {
                $controller = $this->app->make(DownloadApiController::class);
                return $controller->download($request, $reference);
            });

            // Direct admin routes
            add_route(['GET', 'POST'], '/admin/web-tools', function (Request $request) {
                $controller = $this->app->make(AdminToolController::class);
                return $controller->handle($request);
            });

            add_route(['GET', 'POST'], '/admin/web-tools/categories', function (Request $request) {
                $controller = $this->app->make(AdminCategoryController::class);
                return $controller->handle($request);
            });

            add_route(['GET', 'POST'], '/admin/web-tools/python-services', function (Request $request) {
                $controller = $this->app->make(AdminPythonServiceController::class);
                return $controller->handle($request);
            });

            add_route(['GET', 'POST'], '/admin/web-tools/dashboard', function (Request $request) {
                $controller = $this->app->make(AdminDashboardController::class);
                return $controller->handle($request);
            });
        }

        // Handle incoming JSON payloads early on 'init' before Kernel checks post_max_size overflow
        if (function_exists('add_action')) {
            add_action('init', function (): void {
                if (
                    isset($_SERVER['REQUEST_METHOD']) &&
                    strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' &&
                    isset($_SERVER['CONTENT_TYPE']) &&
                    str_contains(strtolower($_SERVER['CONTENT_TYPE']), 'application/json')
                ) {
                    $raw = (string)@file_get_contents('php://input');
                    if ($raw !== '') {
                        $decoded = json_decode($raw, true);
                        if (is_array($decoded)) {
                            if (isset($decoded['inputs']) && is_array($decoded['inputs'])) {
                                $_POST['inputs'] = $decoded['inputs'];
                                foreach ($decoded['inputs'] as $k => $v) {
                                    if (!isset($_POST[$k])) {
                                        $_POST[$k] = $v;
                                    }
                                }
                            } else {
                                foreach ($decoded as $k => $v) {
                                    $_POST[$k] = $v;
                                }
                            }
                            if (empty($_POST)) {
                                $_POST['_fcms_json_payload'] = true;
                            }
                        }
                    }
                }
            });

            add_action('plugin.activated', function (string $pluginId): void {
                if ($pluginId === 'favorite-web-tools') {
                    $this->onActivate();
                }
            });

            add_action('plugin.deactivated', function (string $pluginId): void {
                if ($pluginId === 'favorite-web-tools') {
                    $this->onDeactivate();
                }
            });
        }
    }

    public function onActivate(): void
    {
        try {
            $this->runMigrations();
            $this->seedIfEmpty();
        } catch (Throwable $e) {
            if (function_exists('cms_log')) {
                cms_log("Favorite Web Tools migration failed on activation: " . $e->getMessage(), 'error', ['plugin' => 'favorite-web-tools']);
            }
        }
    }

    public function onDeactivate(): void
    {
        if (function_exists('cms_log')) {
            cms_log('Favorite Web Tools plugin deactivated.', 'info', ['plugin' => 'favorite-web-tools']);
        }
    }

    public function ensureMigrations(): void
    {
        if (self::$migrationsEnsured || !$this->app->has(Database::class)) {
            return;
        }

        self::$migrationsEnsured = true;

        try {
            $db = $this->app->make(Database::class);
            if (method_exists($db, 'registerPrefixableTables')) {
                $db->registerPrefixableTables(self::TABLES);
            }

            $this->runMigrations();
        } catch (Throwable $e) {
            if (function_exists('cms_log')) {
                cms_log("Favorite Web Tools schema check failed: " . $e->getMessage(), 'error', ['plugin' => 'favorite-web-tools']);
            }
        }
    }

    public function runMigrations(): array
    {
        if (!$this->app->has(Database::class)) {
            return [];
        }

        $db = $this->app->make(Database::class);
        if (method_exists($db, 'registerPrefixableTables')) {
            $db->registerPrefixableTables(self::TABLES);
        }

        $migrator = new Migrator($db);
        $migrationsPath = __DIR__ . '/../database/migrations';
        return $migrator->migrate($migrationsPath);
    }

    public function seedIfEmpty(): void
    {
        try {
            if ($this->app->has(ToolCatalogSeeder::class)) {
                $seeder = $this->app->make(ToolCatalogSeeder::class);
                $seeder->seedIfEmpty();
            }
        } catch (Throwable) {
        }
    }
}
