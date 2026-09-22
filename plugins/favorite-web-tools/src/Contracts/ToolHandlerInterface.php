<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Contracts;

interface ToolHandlerInterface
{
    /**
     * Unique handler identifier (e.g. 'json_formatter', 'base64_encoder').
     */
    public function getId(): string;

    /**
     * Human-readable name of the handler.
     */
    public function getName(): string;

    /**
     * Execute controlled processing logic.
     *
     * @param array $inputs User inputs passed from execution request.
     * @param array $config Tool-specific server-side configuration.
     * @return array{type: string, value: mixed, data?: mixed, success?: bool, meta?: array}
     */
    public function execute(array $inputs, array $config = []): array;

    /**
     * Alias for execute to handle invocation uniformly.
     *
     * @param array $inputs User inputs passed from execution request.
     * @param array $config Tool-specific server-side configuration.
     * @return array{type: string, value: mixed, data?: mixed, success?: bool, meta?: array}
     */
    public function handle(array $inputs, array $config = []): array;
}

