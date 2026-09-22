<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Normalizers;

interface ResultNormalizerInterface
{
    /**
     * Determines whether this normalizer can handle the given result data / hint.
     */
    public function supports(string $type, array $data): bool;

    /**
     * Normalize the raw execution result into a predictable, safe data structure for templates.
     */
    public function normalize(array $data): array;
}

