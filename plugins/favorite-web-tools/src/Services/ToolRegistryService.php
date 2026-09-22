<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Services;

use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Models\ToolCategory;
use FavoriteCMS\Tools\Models\PythonService;
use FavoriteCMS\Tools\Repositories\ToolRepository;
use FavoriteCMS\Tools\Repositories\CategoryRepository;
use FavoriteCMS\Tools\Repositories\PythonServiceRepository;
use FavoriteCMS\Tools\Support\ToolStatus;

class ToolRegistryService
{
    protected ToolRepository $toolRepo;
    protected ?CategoryRepository $categoryRepo;
    protected ?PythonServiceRepository $pythonRepo;

    public function __construct(
        ToolRepository $toolRepo,
        ?CategoryRepository $categoryRepo = null,
        ?PythonServiceRepository $pythonRepo = null
    ) {
        $this->toolRepo = $toolRepo;
        $this->categoryRepo = $categoryRepo;
        $this->pythonRepo = $pythonRepo;
    }

    public function findBySlug(string $slug): ?Tool
    {
        return $this->toolRepo->findBySlug($slug);
    }

    public function getPublicTool(string $slug): ?Tool
    {
        $tool = $this->toolRepo->findBySlug($slug);
        return ($tool && $tool->isActive()) ? $tool : null;
    }

    public function getToolBySlug(string $slug): ?Tool
    {
        return $this->toolRepo->findBySlug($slug);
    }

    public function getToolById(int $id): ?Tool
    {
        return $this->toolRepo->findById($id);
    }

    public function getActiveTools(array $filters = []): array
    {
        return $this->toolRepo->getPublicTools($filters);
    }

    public function getToolsByCategory(int $categoryId): array
    {
        return $this->getActiveToolsByCategory($categoryId);
    }

    public function getActiveToolsByCategory(int $categoryId): array
    {
        $all = $this->toolRepo->getAllAdminTools([
            'category' => $categoryId,
            'status'   => 'ACTIVE',
        ]);
        return array_values($all);
    }

    public function searchTools(array|string $filters = [], int $page = 1, int $perPage = 20): array
    {
        if (is_string($filters)) {
            $filters = ['search' => $filters];
        }
        return $this->toolRepo->getPublicTools($filters, $page, $perPage);
    }

    public function getCatalog(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        return $this->toolRepo->getPublicTools($filters, $page, $perPage);
    }

    public function getAllAdminTools(array $filters = []): array
    {
        return $this->toolRepo->getAllAdminTools($filters);
    }

    /**
     * @return ToolCategory[]
     */
    public function getCategories(bool $activeOnly = true): array
    {
        if ($this->categoryRepo === null) {
            return [];
        }
        return $this->categoryRepo->getAll($activeOnly);
    }

    public function getCategoryBySlug(string $slug): ?ToolCategory
    {
        if ($this->categoryRepo === null) {
            return null;
        }
        return $this->categoryRepo->findBySlug($slug);
    }

    /**
     * @return PythonService[]
     */
    public function getPythonServices(bool $activeOnly = false): array
    {
        if ($this->pythonRepo === null) {
            return [];
        }
        return $this->pythonRepo->getAll($activeOnly);
    }

    public function createTool(array $data): Tool
    {
        return $this->toolRepo->create($data);
    }

    public function updateTool(int $id, array $data): bool
    {
        return $this->toolRepo->update($id, $data);
    }

    public function activateTool(int $id): bool
    {
        return $this->toolRepo->setStatus($id, ToolStatus::ACTIVE);
    }

    public function disableTool(int $id): bool
    {
        return $this->toolRepo->setStatus($id, ToolStatus::DISABLED);
    }

    public function deleteTool(int $id): bool
    {
        return $this->toolRepo->delete($id);
    }

    public function getDashboardStats(): array
    {
        $statusCounts = $this->toolRepo->countByStatus();
        $catCount = $this->categoryRepo ? $this->categoryRepo->count() : 0;
        $psCount = $this->pythonRepo ? $this->pythonRepo->count() : 0;

        return [
            'total_tools'    => $statusCounts['total'],
            'active_tools'   => $statusCounts['active'],
            'draft_tools'    => $statusCounts['draft'],
            'disabled_tools' => $statusCounts['disabled'],
            'categories'     => $catCount,
            'python_services'=> $psCount,
        ];
    }
}
