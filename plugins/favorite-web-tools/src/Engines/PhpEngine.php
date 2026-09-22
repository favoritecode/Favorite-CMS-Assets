<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Engines;

use FavoriteCMS\Tools\Contracts\EngineInterface;
use FavoriteCMS\Tools\Handlers\AbstractToolHandler;
use FavoriteCMS\Tools\Handlers\PhpHandlerRegistry;
use FavoriteCMS\Tools\Models\Tool;
use FavoriteCMS\Tools\Support\EngineType;
use Throwable;

class PhpEngine implements EngineInterface
{
    public function canHandle(Tool $tool): bool
    {
        return $tool->engine === EngineType::PHP;
    }

    public function validate(Tool $tool, array $inputs): array
    {
        $handlerId = $this->resolveHandlerId($tool);
        $handler = PhpHandlerRegistry::get($handlerId);

        if (!$handler) {
            return ["Controlled PHP handler '{$handlerId}' is not registered."];
        }

        return [];
    }

    public function execute(Tool $tool, array $inputs): array
    {
        $handlerId = $this->resolveHandlerId($tool);
        $handler = PhpHandlerRegistry::get($handlerId);

        if (!$handler) {
            return [
                'success' => false,
                'error'   => "Controlled PHP handler '{$handlerId}' is not registered.",
            ];
        }

        try {
            $result = $handler->execute($inputs, $tool->configuration ?? []);
            if (!isset($result['success'])) {
                $result['success'] = true;
            }
            if (isset($result['value']) && !isset($result['data'])) {
                $result['data'] = $result['value'];
            } elseif (isset($result['data']) && !isset($result['value'])) {
                $result['value'] = $result['data'];
            }
            return $result;
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error'   => \FavoriteCMS\Tools\Services\ToolExecutionService::sanitizeErrorMessage($e),
            ];
        }
    }

    private function resolveHandlerId(Tool $tool): string
    {
        if (!empty($tool->handler_class)) {
            return (string)$tool->handler_class;
        }

        if (!empty($tool->configuration['handler_class'])) {
            return (string)$tool->configuration['handler_class'];
        }

        if (!empty($tool->configuration['php_handler'])) {
            return (string)$tool->configuration['php_handler'];
        }

        $fallback = str_replace('-', '_', $tool->slug);
        return $fallback;
    }
}

