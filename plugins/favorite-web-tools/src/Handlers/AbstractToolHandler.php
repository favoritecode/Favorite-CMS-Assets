<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Contracts\ToolHandlerInterface;

abstract class AbstractToolHandler implements ToolHandlerInterface
{
    /**
     * Executes or handles the tool processing logic.
     */
    public function handle(array $inputs, array $config = []): array
    {
        $res = $this->execute($inputs, $config);
        return $this->normalizeResult($res);
    }

    /**
     * Normalize result so it always contains 'success', 'data', 'value', 'type'
     */
    public function normalizeResult(array $res): array
    {
        if (!isset($res['success'])) {
            $res['success'] = true;
        }
        if (isset($res['value']) && !isset($res['data'])) {
            $res['data'] = $res['value'];
        } elseif (isset($res['data']) && !isset($res['value'])) {
            $res['value'] = $res['data'];
        }
        return $res;
    }
}

