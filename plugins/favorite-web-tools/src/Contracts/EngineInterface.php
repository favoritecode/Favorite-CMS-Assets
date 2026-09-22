<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Contracts;

use FavoriteCMS\Tools\Models\Tool;

interface EngineInterface
{
    /**
     * Check if this engine can handle the given tool.
     */
    public function canHandle(Tool $tool): bool;

    /**
     * Validate the given inputs against tool requirements.
     * Returns a list of error messages (empty if valid).
     *
     * @return string[]
     */
    public function validate(Tool $tool, array $inputs): array;

    /**
     * Execute the tool processing and return standard result data.
     *
     * @return array{type: string, value: mixed, meta?: array}
     */
    public function execute(Tool $tool, array $inputs): array;
}

