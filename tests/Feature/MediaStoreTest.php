<?php

use App\Support\MediaStore;

/**
 * A stored attachment has to keep a name that says what it is.
 *
 * Android's picker hands over a content URI resolved to a cache entry with no
 * extension, so everything landed as `.bin` — and a `.bin` comes back from a
 * server as `application/octet-stream`, which means the `<video>` the editor
 * saved does not play and the `<img>` does not render. The bytes were always
 * right; the name was what broke it.
 */
function extensionFor(string $path): string
{
    $method = new ReflectionMethod(MediaStore::class, 'extensionFor');
    $method->setAccessible(true);

    return $method->invoke(null, $path);
}

it('keeps the extension a path already carries', function () {
    expect(extensionFor('/tmp/holiday.MP4'))->toBe('mp4');
    expect(extensionFor('/tmp/IMG_0001.HEIC'))->toBe('heic');
});

it('reads the type off a file that arrives without one', function (string $bytes, string $expected) {
    $path = tempnam(sys_get_temp_dir(), 'media');

    file_put_contents($path, $bytes);

    try {
        expect(extensionFor($path))->toBe($expected);
    } finally {
        @unlink($path);
    }
})->with([
    // Real files, not just magic bytes: the detector reads structure, and a
    // signature followed by zeroes is not a PNG to it.
    'jpeg' => ["\xFF\xD8\xFF\xE0".str_repeat("\x00", 32), 'jpg'],
    'png' => [base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='), 'png'],
    // Something unrecognisable still has to be storable.
    'unknown' => [str_repeat("\x01", 64), 'bin'],
]);
