<?php

declare(strict_types=1);

namespace FavoriteCMS\Tools\Handlers;

use FavoriteCMS\Tools\Support\ResultType;

class MarkdownPreviewerHandler extends AbstractToolHandler
{
    public function getId(): string
    {
        return 'markdown_previewer';
    }

    public function getName(): string
    {
        return 'Markdown Previewer';
    }

    public function execute(array $inputs, array $config = []): array
    {
        $raw = (string)($inputs['input'] ?? $inputs['markdown'] ?? $inputs['text'] ?? '');

        $html = $this->renderMarkdown($raw);

        return [
            'success' => true,
            'type'    => ResultType::HTML,
            'data'    => $html,
            'value'   => $html,
            'meta'    => [
                'raw_length'  => strlen($raw),
                'html_length' => strlen($html),
            ],
        ];
    }

    private function renderMarkdown(string $md): string
    {
        // 1. Normalize line breaks
        $md = str_replace(["\r\n", "\r"], "\n", $md);

        // 2. Escape raw HTML tags to prevent XSS
        $md = htmlspecialchars($md, ENT_NOQUOTES, 'UTF-8');

        // 3. Code blocks (```lang ... ```)
        $codeBlocks = [];
        $md = preg_replace_callback('/```([a-zA-Z0-9_\-]*)\n([\s\S]*?)```/m', function ($m) use (&$codeBlocks) {
            $idx = count($codeBlocks);
            $lang = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $code = $m[2];
            $codeBlocks[$idx] = '<pre class="fwt-code-block' . ($lang ? ' language-' . $lang : '') . '"><code>' . $code . '</code></pre>';
            return "%%CODEBLOCK_{$idx}%%";
        }, $md);

        // 4. Inline code (`code`)
        $md = preg_replace('/`([^`]+)`/', '<code class="fwt-inline-code">$1</code>', $md);

        // 5. Headers (# to ######)
        $md = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $md);
        $md = preg_replace('/^#####\s+(.+)$/m', '<h5>$1</h5>', $md);
        $md = preg_replace('/^####\s+(.+)$/m', '<h4>$1</h4>', $md);
        $md = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $md);
        $md = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $md);
        $md = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $md);

        // 6. Horizontal rule
        $md = preg_replace('/^(\-{3,}|\*{3,}|_{3,})$/m', '<hr class="fwt-divider">', $md);

        // 7. Bold and Italic
        $md = preg_replace('/\*\*\*([^*]+)\*\*\*/', '<strong><em>$1</em></strong>', $md);
        $md = preg_replace('/___([^_]+)___/', '<strong><em>$1</em></strong>', $md);
        $md = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $md);
        $md = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $md);
        $md = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $md);
        $md = preg_replace('/_([^_]+)_/', '<em>$1</em>', $md);

        // 8. Strikethrough
        $md = preg_replace('/~~([^~]+)~~/', '<del>$1</del>', $md);

        // 9. Blockquotes
        $md = preg_replace('/^>\s+(.+)$/m', '<blockquote class="fwt-quote">$1</blockquote>', $md);

        // 10. Links and Images
        $md = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($m) {
            $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $url = $this->sanitizeUrl($m[2]);
            return '<img src="' . $url . '" alt="' . $alt . '" class="fwt-preview-image" style="max-width:100%;border-radius:6px;" />';
        }, $md);

        $md = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
            $text = $m[1];
            $url = $this->sanitizeUrl($m[2]);
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="fwt-link">' . $text . '</a>';
        }, $md);

        // 11. Lists (unordered)
        $md = preg_replace_callback('/((?:^[*\-+]\s+.+(?:\n|$))+)/m', function ($m) {
            $items = preg_replace('/^[*\-+]\s+(.+)$/m', '<li>$1</li>', trim($m[1]));
            return "<ul class=\"fwt-list\">\n" . $items . "\n</ul>\n";
        }, $md);

        // 12. Lists (ordered)
        $md = preg_replace_callback('/((?:^\d+\.\s+.+(?:\n|$))+)/m', function ($m) {
            $items = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', trim($m[1]));
            return "<ol class=\"fwt-list-ordered\">\n" . $items . "\n</ol>\n";
        }, $md);

        // 13. Paragraphs for remaining text blocks
        $blocks = explode("\n\n", trim($md));
        $renderedBlocks = [];
        foreach ($blocks as $block) {
            $t = trim($block);
            if ($t === '') continue;

            if (preg_match('/^<(h[1-6]|ul|ol|pre|blockquote|hr|img|div)/i', $t)) {
                $renderedBlocks[] = $t;
            } elseif (str_starts_with($t, '%%CODEBLOCK_')) {
                $renderedBlocks[] = $t;
            } else {
                $renderedBlocks[] = '<p>' . nl2br($t) . '</p>';
            }
        }
        $finalHtml = implode("\n", $renderedBlocks);

        // 14. Restore code blocks
        foreach ($codeBlocks as $idx => $blockHtml) {
            $finalHtml = str_replace("%%CODEBLOCK_{$idx}%%", $blockHtml, $finalHtml);
        }

        return '<div class="fwt-markdown-body" style="line-height:1.6;font-size:15px;">' . $finalHtml . '</div>';
    }

    private function sanitizeUrl(string $url): string
    {
        $url = trim($url);
        // Prevent javascript:, data:, vbscript: protocols
        if (preg_match('/^(javascript|vbscript|data):/i', $url)) {
            return '#';
        }
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

