<?php

namespace Core\Support;

/**
 * محوّل Markdown إلى HTML بسيط جدًا: يدعم فقط ما يُستخدم فعليًا في CHANGELOG.md
 * (عناوين #/##/###، قوائم نقطية، فاصل أفقي، فقرات، وتنسيق **عريض** بسيط)
 * دون الاعتماد على أي مكتبة خارجية.
 */
final class Markdown
{
    public static function toHtml(string $markdown): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $markdown);
        $html = [];
        $inList = false;

        $closeList = function () use (&$html, &$inList) {
            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $closeList();
                continue;
            }

            if (preg_match('/^---+$/', $trimmed)) {
                $closeList();
                $html[] = '<hr>';
                continue;
            }

            if (preg_match('/^(#{1,4})\s+(.*)$/', $trimmed, $m)) {
                $closeList();
                $level = min(4, strlen($m[1])) + 2;
                $html[] = '<h' . $level . '>' . self::inline($m[2]) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.*)$/', $trimmed, $m)) {
                if (!$inList) {
                    $html[] = '<ul>';
                    $inList = true;
                }
                $html[] = '<li>' . self::inline($m[1]) . '</li>';
                continue;
            }

            $closeList();
            $html[] = '<p>' . self::inline($trimmed) . '</p>';
        }

        $closeList();

        return implode("\n", $html);
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);

        return $text;
    }
}
