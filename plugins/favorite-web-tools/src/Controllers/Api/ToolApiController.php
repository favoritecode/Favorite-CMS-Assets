<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Controllers\Api;

use FavoriteCMS\Core\Application;
use FavoriteCMS\Core\Request;
use FavoriteCMS\Core\Response;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Services\ToolRegistryService;

class ToolApiController
{
    protected Application $app;
    protected ToolRegistryService $toolRegistry;
    protected CategoryRepository $categoryRepo;

    public function __construct(
        Application $app,
        ToolRegistryService $toolRegistry,
        CategoryRepository $categoryRepo
    ) {
        $this->app = $app;
        $this->toolRegistry = $toolRegistry;
        $this->categoryRepo = $categoryRepo;
    }

    public function index(Request $request): Response
    {
        $search = trim((string)($request->get('search') ?? $request->get('q') ?? ''));
        $categorySlug = trim((string)$request->get('category', ''));
        $engine = trim((string)$request->get('engine', ''));
        $accessMode = trim((string)$request->get('access_mode', ''));
        $sort = trim((string)$request->get('sort', 'order'));
        $page = max(1, (int)$request->get('page', 1));
        $perPage = min(50, max(1, (int)$request->get('per_page', 12)));

        $categoryId = null;
        if ($categorySlug !== '') {
            $cat = $this->categoryRepo->findBySlug($categorySlug);
            if ($cat !== null) {
                $categoryId = $cat->id;
            }
        }

        $filters = [
            'search'      => $search,
            'category_id' => $categoryId,
            'engine'      => $engine,
            'access_mode' => $accessMode,
            'sort'        => $sort,
        ];

        $results = $this->toolRegistry->searchTools($filters, $page, $perPage);

        $items = [];
        foreach ($results['items'] as $tool) {
            $cat = $tool->category_id ? $this->categoryRepo->find($tool->category_id) : null;
            $items[] = [
                'name'        => $tool->name,
                'slug'        => $tool->slug,
                'description' => $tool->description,
                'category'    => $cat ? $cat->name : null,
                'category_slug' => $cat ? $cat->slug : null,
                'engine'      => $tool->engine,
                'access_mode' => $tool->access_mode,
                'icon'        => $tool->icon,
                'thumbnail'   => $tool->thumbnail,
                'url'         => '/tools/' . $tool->slug,
            ];
        }

        return Response::json([
            'success' => true,
            'data'    => [
                'tools'       => $items,
                'total'       => $results['total'],
                'page'        => $results['page'],
                'per_page'    => $results['perPage'],
                'total_pages' => $results['totalPages'],
            ],
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $tool = $this->toolRegistry->findBySlug($slug);
        if ($tool === null || !$tool->isActive()) {
            return Response::json([
                'success' => false,
                'error'   => [
                    'code'    => 'TOOL_NOT_FOUND',
                    'message' => 'The requested tool is unavailable or does not exist.',
                ],
            ], 404);
        }

        $cat = $tool->category_id ? $this->categoryRepo->find($tool->category_id) : null;

        return Response::json([
            'success' => true,
            'data'    => [
                'name'          => $tool->name,
                'slug'          => $tool->slug,
                'description'   => $tool->description,
                'category'      => $cat ? $cat->name : null,
                'category_slug' => $cat ? $cat->slug : null,
                'engine'        => $tool->engine,
                'access_mode'   => $tool->access_mode,
                'status'        => $tool->status,
                'icon'          => $tool->icon,
                'thumbnail'     => $tool->thumbnail,
                'input_schema'  => $tool->input_schema,
                'output_schema' => $tool->output_schema,
                'ui_schema'     => $tool->ui_schema,
            ],
        ]);
    }

    public function categories(Request $request): Response
    {
        $categories = $this->categoryRepo->allActive();
        $data = [];
        foreach ($categories as $cat) {
            $tools = $this->toolRegistry->getActiveToolsByCategory($cat->id);
            $data[] = [
                'id'            => $cat->id,
                'name'          => $cat->name,
                'slug'          => $cat->slug,
                'description'   => $cat->description,
                'icon'          => $cat->icon,
                'display_order' => $cat->display_order,
                'tool_count'    => count($tools),
            ];
        }

        return Response::json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function categoryDetail(Request $request, string $slug): Response
    {
        $cat = $this->categoryRepo->findBySlug($slug);
        if ($cat === null || !$cat->isActive()) {
            return Response::json([
                'success' => false,
                'error'   => [
                    'code'    => 'CATEGORY_NOT_FOUND',
                    'message' => 'The requested category does not exist.',
                ],
            ], 404);
        }

        $tools = $this->toolRegistry->getActiveToolsByCategory($cat->id);
        $toolList = [];
        foreach ($tools as $tool) {
            $toolList[] = [
                'name'        => $tool->name,
                'slug'        => $tool->slug,
                'description' => $tool->description,
                'engine'      => $tool->engine,
                'access_mode' => $tool->access_mode,
                'icon'        => $tool->icon,
                'url'         => '/tools/' . $tool->slug,
            ];
        }

        return Response::json([
            'success' => true,
            'data'    => [
                'category' => [
                    'name'        => $cat->name,
                    'slug'        => $cat->slug,
                    'description' => $cat->description,
                    'icon'        => $cat->icon,
                ],
                'tools' => $toolList,
            ],
        ]);
    }
}

