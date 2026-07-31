<?php

namespace App\Support;

/**
 * A running record of everything the editor told this app, newest first.
 *
 * The editor talks to you in events, and events are easy to miss: they arrive
 * while you are typing, several fire per keystroke, and if a handler throws
 * you get a blank screen rather than a stack trace. This keeps the last few
 * so a developer can SEE the conversation instead of guessing at it.
 *
 * Kept in memory on purpose — it is a debugging aid, not data. A real app
 * would send this sort of thing to its own logger, and the shape here is
 * deliberately close to what you would send.
 */
class PayloadLog
{
    /** How many entries to keep. Enough to see a full compose, not a leak. */
    public const LIMIT = 40;

    /** @var list<array{at: string, kind: string, event: string, detail: string}> */
    protected static array $entries = [];

    /** An event arrived from the editor. */
    public static function event(string $event, string $detail = ''): void
    {
        self::push('event', $event, $detail);
    }

    /** Something this app did in response — a call back into the editor. */
    public static function call(string $event, string $detail = ''): void
    {
        self::push('call', $event, $detail);
    }

    /**
     * Something went wrong.
     *
     * The reason this exists at all: a throw inside an event handler leaves no
     * trace on a phone. Catch it, record it, and the screen can show it.
     */
    public static function failure(string $event, string $detail = ''): void
    {
        self::push('failure', $event, $detail);
    }

    /** @return list<array{at: string, kind: string, event: string, detail: string}> */
    public static function entries(): array
    {
        return self::$entries;
    }

    public static function clear(): void
    {
        self::$entries = [];
    }

    public static function count(): int
    {
        return count(self::$entries);
    }

    protected static function push(string $kind, string $event, string $detail): void
    {
        array_unshift(self::$entries, [
            'at' => now()->format('H:i:s.v'),
            'kind' => $kind,
            'event' => $event,
            'detail' => self::clip($detail),
        ]);

        self::$entries = array_slice(self::$entries, 0, self::LIMIT);
    }

    /** A payload can be a whole document; a log line cannot. */
    protected static function clip(string $detail, int $limit = 220): string
    {
        $detail = trim(preg_replace('/\s+/', ' ', $detail) ?? '');

        return mb_strlen($detail) > $limit
            ? mb_substr($detail, 0, $limit).'…'
            : $detail;
    }
}
