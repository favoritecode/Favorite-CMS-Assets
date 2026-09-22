<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class TextCounterHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'text_counter';
    }

    public function getName(): string
    {
        return 'Text & Word Counter';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $text = (string)($inputs['input'] ?? $inputs['text'] ?? '');

        $charCount = mb_strlen($text, 'UTF-8');
        $charNoSpaces = mb_strlen(preg_replace('/\s+/u', '', $text), 'UTF-8');

        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        $sentences = preg_split('/[.?!]+(?:\s+|$)/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = trim($text) === '' ? 0 : count($sentences);

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $lineCount = $text === '' ? 0 : count($lines);

        $paragraphs = preg_split('/(\r\n|\r|\n){2,}/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $paraCount = count($paragraphs);

        // Estimated reading time (average 200 words per minute)
        $readingMinutes = ceil($wordCount / 200);
        // Estimated speaking time (average 130 words per minute)
        $speakingMinutes = ceil($wordCount / 130);

        $linesOut = [
            "Words:                    " . number_format($wordCount),
            "Sentences:                " . number_format($sentenceCount),
            "Characters (with spaces): " . number_format($charCount),
            "Characters (no spaces):   " . number_format($charNoSpaces),
            "Lines:                    " . number_format($lineCount),
            "Paragraphs:               " . number_format($paraCount),
            "Est. Reading Time:        ~" . $readingMinutes . " min",
            "Est. Speaking Time:       ~" . $speakingMinutes . " min",
        ];

        return [
            'success' => true,
            'type'    => ResultType::TEXT,
            'value'   => implode("\n", $linesOut),
            'data'    => [
                'words'              => $wordCount,
                'sentences'          => $sentenceCount,
                'characters'         => $charCount,
                'characters_nospace' => $charNoSpaces,
                'lines'              => $lineCount,
                'paragraphs'         => $paraCount,
                'reading_minutes'    => $readingMinutes,
                'speaking_minutes'   => $speakingMinutes,
            ],
            'meta'    => [
                'words'              => $wordCount,
                'sentences'          => $sentenceCount,
                'characters'         => $charCount,
                'characters_nospace' => $charNoSpaces,
                'lines'              => $lineCount,
                'paragraphs'         => $paraCount,
                'reading_minutes'    => $readingMinutes,
                'speaking_minutes'   => $speakingMinutes,
            ],
        ];
    }
}

