<?php

namespace App\Support;

/**
 * A colour ramp, as a list of flat steps.
 *
 * The renderer has no gradient — a view takes one background colour and that
 * is all. A card that fell back to a single flat colour looked noticeably
 * unlike the composer, which draws the real thing, so the row stacks thin
 * strips of interpolated colour instead and gets close enough that the two
 * read as the same post.
 *
 * Enough steps that the banding disappears, few enough that a feed of them
 * costs nothing: twenty-four strips is one view per ~8 points of card.
 */
class Gradient
{
    public const STEPS = 24;

    /**
     * Hex colours from `$from` to `$to`, inclusive of both ends.
     *
     * A malformed colour yields nothing rather than a black card — the caller
     * falls back to a flat fill, which is wrong but not alarming.
     *
     * @return list<string>
     */
    public static function steps(string $from, string $to, int $steps = self::STEPS): array
    {
        $start = self::rgb($from);
        $end = self::rgb($to === '' ? $from : $to);

        if ($start === null || $end === null) {
            return [];
        }

        $steps = max(2, $steps);
        $out = [];

        for ($i = 0; $i < $steps; $i++) {
            $ratio = $i / ($steps - 1);

            $out[] = sprintf(
                '#%02X%02X%02X',
                (int) round($start[0] + ($end[0] - $start[0]) * $ratio),
                (int) round($start[1] + ($end[1] - $start[1]) * $ratio),
                (int) round($start[2] + ($end[2] - $start[2]) * $ratio),
            );
        }

        return $out;
    }

    /**
     * @return ?array{int, int, int}
     */
    private static function rgb(string $hex): ?array
    {
        $hex = ltrim(trim($hex), '#');

        // #RGB is shorthand for #RRGGBB, which is worth accepting because a
        // host writing colours by hand will use it.
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
