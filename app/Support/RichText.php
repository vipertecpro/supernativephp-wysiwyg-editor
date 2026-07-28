<?php

namespace App\Support;

/**
 * Turns the WysiwygEditor plugin's normalised HTML into simple block arrays
 * that native views can render with <text> elements.
 *
 * NativePHP native views have no HTML display component, so previews are
 * rendered block-by-block: headings get big bold text, list items get their
 * marker prefix, and inline marks are flattened to plain text. The stored
 * HTML stays the source of truth — this is presentation only.
 */
class RichText
{
    /**
     * Parse normalised plugin HTML into displayable blocks.
     *
     * Understands exactly the plugin's HTML contract: <p>, <h1>–<h3>,
     * <ul>/<ol> with <li>, <blockquote>, and <p><br></p> for blank lines.
     *
     * @return list<array{type: string, text?: string, items?: list<string>}>
     */
    public static function blocks(string $html): array
    {
        $blocks = [];

        preg_match_all(
            '/<(p|h1|h2|h3|blockquote|ul|ol)>(.*?)<\/\1>/s',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as [, $tag, $inner]) {
            if ($tag === 'ul' || $tag === 'ol') {
                preg_match_all('/<li>(.*?)<\/li>/s', $inner, $items);

                $blocks[] = [
                    'type' => $tag,
                    'items' => array_map(self::flatten(...), $items[1]),
                ];

                continue;
            }

            $blocks[] = [
                'type' => $tag,
                'text' => $inner === '<br>' ? '' : self::flatten($inner),
            ];
        }

        return $blocks;
    }

    /** Strip inline tags and decode entities — plain text for a <text> element. */
    public static function flatten(string $inner): string
    {
        return trim(html_entity_decode(strip_tags($inner), ENT_QUOTES | ENT_HTML5));
    }

    /** Shorten to $limit characters on a word boundary with an ellipsis. */
    public static function excerpt(string $text, int $limit): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)).'…';
    }
}
