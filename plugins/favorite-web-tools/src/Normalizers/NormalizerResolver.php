<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Normalizers;

class NormalizerResolver
{
    /** @var ResultNormalizerInterface[] */
    private array $normalizers = [];

    private ResultNormalizerInterface $defaultNormalizer;

    public function __construct()
    {
        $this->defaultNormalizer = new DefaultResultNormalizer();
        $this->register(new MediaResultNormalizer());
    }

    public function register(ResultNormalizerInterface $normalizer): self
    {
        $this->normalizers[] = $normalizer;
        return $this;
    }

    public function resolve(string $hint, array $data): ResultNormalizerInterface
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($hint, $data)) {
                return $normalizer;
            }
        }

        return $this->defaultNormalizer;
    }

    public function normalize(array $data, string $hint = ''): array
    {
        return $this->resolve($hint, $data)->normalize($data);
    }
}

