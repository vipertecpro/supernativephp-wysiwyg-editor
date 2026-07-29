<?php

namespace App\Support;

/**
 * Turns the editor's saved JSON into the parts a timeline row draws.
 *
 * Reads the JSON rather than the HTML because it is the canonical form: a
 * poll's options and an image's local path survive there and do not survive in
 * HTML. See the plugin's docs/DOCUMENT-MODEL.md.
 *
 * A timeline row is not a document viewer — it does not render headings or
 * lists inline. It shows the words, the pictures, the video and the poll,
 * which is what these posts are made of.
 */
class PostContent
{
    /**
     * @return array{text: string, images: list<array<string, string>>,
     *               video: ?array<string, string>, poll: ?array<string, mixed>}
     */
    public static function parse(string $json): array
    {
        $document = json_decode($json, true);

        $out = ['text' => '', 'images' => [], 'video' => null, 'poll' => null];

        if (! is_array($document) || ! is_array($document['blocks'] ?? null)) {
            return $out;
        }

        $paragraphs = [];

        foreach ($document['blocks'] as $block) {
            if (! is_array($block)) {
                continue;
            }

            match ((string) ($block['type'] ?? '')) {
                'image' => $out['images'][] = self::media($block),
                // One video per post, the way these platforms do it — a second
                // is ignored rather than stacked.
                'video' => $out['video'] ??= self::media($block),
                'poll' => $out['poll'] ??= self::poll($block),
                default => $paragraphs[] = self::runs($block),
            };
        }

        $out['text'] = trim(implode("\n", array_filter($paragraphs, fn (string $p) => $p !== '')));

        return $out;
    }

    /**
     * How a set of photos is laid out. X, Facebook and Instagram all use the
     * same shapes, so the row only has to know which one it is in.
     */
    public static function grid(int $count): string
    {
        return match (true) {
            $count <= 1 => 'single',
            $count === 2 => 'pair',
            $count === 3 => 'feature',
            default => 'quad',
        };
    }

    /** @param  array<string, mixed>  $block */
    private static function media(array $block): array
    {
        return [
            // The remote url once uploaded, the local file before then — a
            // freshly written post has only the latter.
            'src' => self::attr($block, 'src') ?: self::attr($block, 'localPath'),
            'alt' => self::attr($block, 'alt'),
            'caption' => self::attr($block, 'caption'),
            'poster' => self::attr($block, 'poster'),
        ];
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    private static function poll(array $block): array
    {
        $options = [];

        foreach ($block['options'] ?? [] as $option) {
            if (is_array($option) && isset($option['label'])) {
                $options[] = (string) $option['label'];
            }
        }

        return [
            'question' => self::attr($block, 'question'),
            'options' => $options,
        ];
    }

    /** @param  array<string, mixed>  $block */
    private static function runs(array $block): string
    {
        $text = '';

        foreach ($block['runs'] ?? [] as $run) {
            if (is_array($run)) {
                $text .= (string) ($run['text'] ?? '');
            }
        }

        return $text;
    }

    /** @param  array<string, mixed>  $block */
    private static function attr(array $block, string $key): string
    {
        $value = $block[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
