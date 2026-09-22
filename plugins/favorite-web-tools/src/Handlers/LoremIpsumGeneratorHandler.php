<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class LoremIpsumGeneratorHandler extends AbstractToolHandler
{
    private const WORDS = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
        'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
        'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
        'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
        'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
        'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
        'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
        'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum', 'curabitur', 'pretium',
        'tincidunt', 'lacus', 'nulla', 'gravida', 'orci', 'a', 'odio', 'nullam',
        'varius', 'turpis', 'et', 'commodo', 'pharetra', 'est', 'eros', 'bibendum',
        'elit', 'nec', 'luctus', 'magna', 'felis', 'sollicitudin', 'mauris'
    ];

    public function getId(): string
    {
        return 'lorem_ipsum_generator';
    }

    public function getName(): string
    {
        return 'Lorem Ipsum Generator';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $type = (string)($inputs['type'] ?? 'paragraphs'); // 'paragraphs', 'sentences', 'words'
        $count = max(1, min(100, (int)($inputs['count'] ?? 3)));
        $startWithLorem = !isset($inputs['start_with_lorem']) || !empty($inputs['start_with_lorem']);

        if ($type === 'words') {
            $words = $this->generateWords($count, $startWithLorem);
            $text = implode(' ', $words) . '.';
            $text = ucfirst($text);
        } elseif ($type === 'sentences') {
            $sentences = [];
            for ($i = 0; $i < $count; $i++) {
                $wordCount = rand(8, 16);
                $words = $this->generateWords($wordCount, $i === 0 && $startWithLorem);
                $sentence = ucfirst(implode(' ', $words)) . '.';
                $sentences[] = $sentence;
            }
            $text = implode(' ', $sentences);
        } else {
            // paragraphs
            $paragraphs = [];
            for ($p = 0; $p < $count; $p++) {
                $sentenceCount = rand(4, 7);
                $sentences = [];
                for ($s = 0; $s < $sentenceCount; $s++) {
                    $wordCount = rand(8, 16);
                    $words = $this->generateWords($wordCount, $p === 0 && $s === 0 && $startWithLorem);
                    $sentences[] = ucfirst(implode(' ', $words)) . '.';
                }
                $paragraphs[] = implode(' ', $sentences);
            }
            $text = implode("\n\n", $paragraphs);
        }

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'data'    => $text,
            'value'   => $text,
            'meta'    => [
                'type'  => $type,
                'count' => $count,
            ],
        ];
    }

    private function generateWords(int $count, bool $startWithLorem): array
    {
        $words = [];
        if ($startWithLorem && $count >= 5) {
            $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet'];
            $count -= 5;
        }

        $dict = self::WORDS;
        $dictLen = count($dict);

        for ($i = 0; $i < $count; $i++) {
            $words[] = $dict[rand(0, $dictLen - 1)];
        }

        return $words;
    }
}

