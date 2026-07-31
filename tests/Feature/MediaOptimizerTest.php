<?php

use App\Support\MediaOptimization;
use App\Support\MediaOptimizer;

/**
 * The seam where compression belongs.
 *
 * The contract that matters is not "it makes files smaller" — it is that it
 * NEVER costs the user their photo. Every failure path has to hand back a
 * readable path, because an unoptimized picture beats a lost one.
 */
function jpegOf(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);

    // Noise, so the encoder cannot trivially compress it to nothing.
    for ($i = 0; $i < 400; $i++) {
        imagefilledrectangle(
            $image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height),
            imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255))
        );
    }

    $path = tempnam(sys_get_temp_dir(), 'src').'.jpg';
    imagejpeg($image, $path, 95);
    imagedestroy($image);

    return $path;
}

it('shrinks an image that is bigger than the limit', function () {
    $path = jpegOf(3000, 2000);

    try {
        $result = MediaOptimizer::optimize($path, 'image');

        expect($result->outcome)->toBe('optimized');
        expect($result->bytesAfter)->toBeLessThan($result->bytesBefore);
        expect(is_readable($result->path))->toBeTrue();

        // And it actually reached the limit rather than just re-encoding.
        [$width] = getimagesize($result->path);
        expect($width)->toBe(MediaOptimizer::MAX_EDGE);

        @unlink($result->path);
    } finally {
        @unlink($path);
    }
})->skip(! function_exists('imagescale'), 'this PHP build has no imagescale()');

it('leaves an image that is already small enough alone', function () {
    $path = jpegOf(400, 300);

    try {
        $result = MediaOptimizer::optimize($path, 'image');

        expect($result->outcome)->toBe('unchanged');
        expect($result->path)->toBe($path);
    } finally {
        @unlink($path);
    }
})->skip(! function_exists('imagescale'), 'this PHP build has no imagescale()');

it('passes video straight through, because PHP is the wrong tool for it', function () {
    $path = tempnam(sys_get_temp_dir(), 'clip');
    file_put_contents($path, str_repeat('x', 2048));

    try {
        $result = MediaOptimizer::optimize($path, 'video');

        expect($result->outcome)->toBe('skipped');
        expect($result->path)->toBe($path);
    } finally {
        @unlink($path);
    }
});

it('hands back the original when the file is not an image it can read', function () {
    $path = tempnam(sys_get_temp_dir(), 'junk');
    file_put_contents($path, str_repeat("\x01", 2048));

    try {
        $result = MediaOptimizer::optimize($path, 'image');

        // Whatever went wrong, the caller still gets something to insert.
        expect($result->path)->toBe($path);
        expect(is_readable($result->path))->toBeTrue();
        expect($result->outcome)->not->toBe('optimized');
    } finally {
        @unlink($path);
    }
});

it('describes what it did in a line a person can read', function () {
    expect((new MediaOptimization('/x', 2_400_000, 480_000, 'optimized'))->summary())
        ->toBe('2.3 MB → 469 KB (80% smaller)');

    expect((new MediaOptimization('/x', 100, 100, 'skipped', 'only images are optimized here'))->summary())
        ->toBe('Skipped — only images are optimized here');
});
