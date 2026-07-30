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
     * @return array{text: string, paragraphs: list<list<array{text: string, link: string}>>,
     *               background: string, images: list<array<string, string>>,
     *               video: ?array<string, string>, poll: ?array<string, mixed>}
     */
    public static function parse(string $json): array
    {
        $document = json_decode($json, true);

        $out = [
            'text' => '', 'paragraphs' => [], 'background' => '',
            'images' => [], 'video' => null, 'poll' => null,
        ];

        if (! is_array($document) || ! is_array($document['blocks'] ?? null)) {
            return $out;
        }

        // A property of the document, not of any block in it — which is why it
        // sits at the root rather than on the first paragraph.
        $background = $document['background'] ?? '';
        $out['background'] = is_string($background) ? $background : '';

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

        // A paragraph is kept as its own list of spans rather than folded into
        // one, because a row draws each as its own <text>: the renderer treats
        // whitespace BETWEEN elements as formatting and drops it, so a "\n"
        // span would silently disappear and run the paragraphs together.
        $out['paragraphs'] = array_values(array_filter($paragraphs, fn (array $p) => $p !== []));

        $out['text'] = trim(implode("\n", array_map(
            fn (array $spans) => implode('', array_column($spans, 'text')),
            $out['paragraphs'],
        )));

        return $out;
    }

    /**
     * How much of a post a collapsed row shows before "…see more".
     *
     * LinkedIn clips at roughly three lines. Counting characters rather than
     * measuring lines is an approximation, but the alternative is asking the
     * renderer how tall the text came out, which nothing on either platform
     * will tell a Blade view.
     */
    public const CLIP_LENGTH = 220;

    /**
     * Cut the paragraphs down to what a collapsed row shows.
     *
     * Clips on a word boundary and keeps the spans intact, so a mention that
     * survives the cut still draws as a mention rather than as prose.
     *
     * @param  list<list<array{text: string, link: string}>>  $paragraphs
     * @return array{paragraphs: list<list<array{text: string, link: string}>>, clipped: bool}
     */
    public static function clip(array $paragraphs, int $limit = self::CLIP_LENGTH): array
    {
        $total = array_sum(array_map(
            fn (array $spans) => mb_strlen(implode('', array_column($spans, 'text'))),
            $paragraphs,
        ));

        if ($total <= $limit) {
            return ['paragraphs' => $paragraphs, 'clipped' => false];
        }

        $out = [];
        $budget = $limit;

        foreach ($paragraphs as $spans) {
            $kept = [];

            foreach ($spans as $span) {
                $length = mb_strlen($span['text']);

                if ($length <= $budget) {
                    $kept[] = $span;
                    $budget -= $length;

                    continue;
                }

                $head = self::toWordBoundary(mb_substr($span['text'], 0, $budget));

                if ($head !== '') {
                    $kept[] = ['text' => $head, 'link' => $span['link']];
                }

                $budget = 0;

                break;
            }

            if ($kept !== []) {
                $out[] = $kept;
            }

            if ($budget <= 0) {
                break;
            }
        }

        return ['paragraphs' => $out, 'clipped' => true];
    }

    /**
     * Back up to the last space, so the clip never lands mid-word.
     *
     * A cut with no space to back up to is one long word, which is left as it
     * is — chopping it anywhere would be just as arbitrary.
     */
    private static function toWordBoundary(string $text): string
    {
        $at = mb_strrpos($text, ' ');

        return $at === false ? rtrim($text) : rtrim(mb_substr($text, 0, $at));
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

    /**
     * One paragraph as its runs, keeping the link on each.
     *
     * The link is what a mention IS — `mention:u2` says which person, where the
     * words alone say only that somebody was named. A row that flattened this
     * away could not draw the mention differently from the prose around it.
     *
     * @param  array<string, mixed>  $block
     * @return list<array{text: string, link: string}>
     */
    private static function runs(array $block): array
    {
        $spans = [];

        foreach ($block['runs'] ?? [] as $run) {
            if (! is_array($run)) {
                continue;
            }

            $text = (string) ($run['text'] ?? '');

            if ($text === '') {
                continue;
            }

            $link = $run['marks']['link'] ?? '';
            $link = is_string($link) ? $link : '';

            // Runs that carry the same link (or none) draw identically, so
            // merging them keeps the row from emitting a <text> per keystroke.
            $last = array_key_last($spans);

            if ($last !== null && $spans[$last]['link'] === $link) {
                $spans[$last]['text'] .= $text;

                continue;
            }

            $spans[] = ['text' => $text, 'link' => $link];
        }

        return $spans;
    }

    /** @param  array<string, mixed>  $block */
    private static function attr(array $block, string $key): string
    {
        $value = $block[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
