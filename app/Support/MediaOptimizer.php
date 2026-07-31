<?php

namespace App\Support;

/**
 * ============================================================================
 *  THE SEAM FOR COMPRESSING, RESIZING OR TRANSCODING A FILE.
 * ============================================================================
 *
 * Between the picker handing you a file and the editor being told about it,
 * the file is YOURS. The editor has not seen it, nothing is uploaded, and
 * nothing downstream knows it exists. That is the moment to shrink a 12 MP
 * photo to something a phone connection can actually send.
 *
 * The editor is deliberately not involved. It takes a path and shows what is
 * at that path — so whatever you return here is what the user sees, what gets
 * uploaded, and what ends up in the saved document. There is no second copy
 * and no cache to invalidate.
 *
 *     $optimized = MediaOptimizer::optimize($pickedPath, 'image');
 *     WysiwygEditor::insertMedia('image', ['localPath' => $optimized->path, …]);
 *
 * What this class does is deliberately modest — a resize and a re-encode with
 * GD, which is enough to show the shape. Real apps go further, and the point
 * is that they can:
 *
 *   - strip EXIF (location data in a holiday photo is a privacy leak);
 *   - transcode video with FFmpeg, or hand it to a native encoder through
 *     your own NativePHP plugin — PHP on a phone should not be doing H.264;
 *   - refuse a file that is too large and tell the user why, rather than
 *     starting an upload that will time out;
 *   - convert HEIC to JPEG, because not every server understands HEIC.
 *
 * Video is passed through untouched here. Compressing it in PHP would mean
 * decoding frames in userland, which is the wrong tool — the note above about
 * a native encoder is the honest answer, not a TODO.
 */
class MediaOptimizer
{
    /** Longest edge, in pixels, an image is allowed to keep. */
    public const MAX_EDGE = 1600;

    /** JPEG quality for the re-encode. 82 is where most eyes stop noticing. */
    public const QUALITY = 82;

    /**
     * Optimize a picked file, returning what happened to it.
     *
     * Never throws and never returns a path that does not exist: if anything
     * goes wrong the original is handed back with the reason recorded, because
     * an unoptimized picture is a far better outcome than a lost one.
     */
    public static function optimize(string $path, string $kind): MediaOptimization
    {
        $before = is_readable($path) ? (int) @filesize($path) : 0;

        if ($kind !== 'image') {
            return new MediaOptimization($path, $before, $before, 'skipped', 'only images are optimized here');
        }

        // Asked for by FUNCTION rather than by extension name on purpose. The
        // PHP that ships inside a NativePHP app is not the one on your Mac:
        // `extension_loaded('gd')` answers false there while `imagescale` and
        // friends are present and work. Testing for what you are about to call
        // is both stricter and more forgiving than testing for its packaging.
        foreach (['getimagesize', 'imagejpeg', 'imagedestroy'] as $needed) {
            if (! function_exists($needed)) {
                return new MediaOptimization($path, $before, $before, 'unavailable', $needed.'() is not available in this PHP build');
            }
        }

        // `imagescale` is the newer, tidier call and is the one most likely to
        // be missing from a trimmed build; `imagecopyresampled` has been there
        // since PHP 4. Either will do.
        if (! function_exists('imagescale') && ! function_exists('imagecopyresampled')) {
            return new MediaOptimization($path, $before, $before, 'unavailable', 'this PHP build can decode images but not resize them');
        }

        try {
            $resized = self::resize($path);
        } catch (\Throwable $e) {
            // A failure here must not cost the user their photo.
            return new MediaOptimization($path, $before, $before, 'failed', $e->getMessage());
        }

        if ($resized === null) {
            return new MediaOptimization($path, $before, $before, 'unchanged', 'already within the size limit');
        }

        $after = (int) @filesize($resized);

        // Re-encoding a small picture can make it BIGGER. Keep whichever is
        // smaller — the point is fewer bytes, not having run the optimizer.
        if ($after >= $before && $before > 0) {
            @unlink($resized);

            return new MediaOptimization($path, $before, $before, 'unchanged', 'the re-encode was not smaller');
        }

        return new MediaOptimization($resized, $before, $after, 'optimized', '');
    }

    /**
     * Resize to fit MAX_EDGE and re-encode as JPEG.
     *
     * @return string|null the new path, or null when the image is already small
     *                     enough to leave alone
     */
    protected static function resize(string $path): ?string
    {
        $info = @getimagesize($path);

        if ($info === false) {
            throw new \RuntimeException('not an image GD can read');
        }

        [$width, $height] = $info;
        $longest = max($width, $height);

        if ($longest <= self::MAX_EDGE) {
            return null;
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => throw new \RuntimeException('unsupported image type '.($info['mime'] ?? '?')),
        };

        if ($source === false) {
            throw new \RuntimeException('the file could not be decoded');
        }

        $scale = self::MAX_EDGE / $longest;
        $to = [(int) round($width * $scale), (int) round($height * $scale)];

        $target = function_exists('imagescale')
            ? imagescale($source, $to[0], $to[1])
            : self::resample($source, $width, $height, $to[0], $to[1]);

        imagedestroy($source);

        if ($target === false) {
            throw new \RuntimeException('the resize failed');
        }

        $out = tempnam(sys_get_temp_dir(), 'opt').'.jpg';
        $written = imagejpeg($target, $out, self::QUALITY);

        imagedestroy($target);

        if (! $written) {
            throw new \RuntimeException('the re-encode could not be written');
        }

        return $out;
    }

    /**
     * The long way round, for builds without `imagescale`.
     *
     * @return \GdImage|false
     */
    protected static function resample(mixed $source, int $width, int $height, int $toWidth, int $toHeight): mixed
    {
        $target = imagecreatetruecolor($toWidth, $toHeight);

        if ($target === false) {
            return false;
        }

        $copied = imagecopyresampled($target, $source, 0, 0, 0, 0, $toWidth, $toHeight, $width, $height);

        if (! $copied) {
            imagedestroy($target);

            return false;
        }

        return $target;
    }
}
