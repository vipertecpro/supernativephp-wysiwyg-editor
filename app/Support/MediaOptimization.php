<?php

namespace App\Support;

/**
 * What happened to one file on its way to the editor.
 *
 * Returned rather than logged, because the decision belongs to the caller: a
 * composer might show "saved 2.1 MB", a debugging screen might show the whole
 * story, and a production app might record nothing at all. See
 * {@see MediaOptimizer}.
 */
class MediaOptimization
{
    public function __construct(
        /** The path to use from here on — optimized, or the original. */
        public string $path,
        public int $bytesBefore,
        public int $bytesAfter,
        /** optimized | unchanged | skipped | unavailable | failed */
        public string $outcome,
        /** Why, when the outcome is not `optimized`. Empty otherwise. */
        public string $reason = '',
    ) {}

    public function saved(): int
    {
        return max(0, $this->bytesBefore - $this->bytesAfter);
    }

    /** How much smaller, as a percentage. 0 when nothing was saved. */
    public function savedPercent(): int
    {
        if ($this->bytesBefore <= 0 || $this->saved() === 0) {
            return 0;
        }

        return (int) round($this->saved() / $this->bytesBefore * 100);
    }

    /** A short human line: "2.4 MB → 480 KB (80% smaller)". */
    public function summary(): string
    {
        if ($this->outcome !== 'optimized') {
            return $this->reason !== ''
                ? ucfirst($this->outcome).' — '.$this->reason
                : ucfirst($this->outcome);
        }

        return self::bytes($this->bytesBefore).' → '.self::bytes($this->bytesAfter)
            .' ('.$this->savedPercent().'% smaller)';
    }

    /** Bytes as a person would write them. */
    public static function bytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / 1024 / 1024, 1).' MB';
    }
}
