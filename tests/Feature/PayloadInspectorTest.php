<?php

use App\NativeComponents\PayloadInspector;
use App\Support\PayloadLog;

/**
 * The screen that exists so a developer can see what they are building against.
 *
 * Its whole value is that what it shows is really what the editor sent, so
 * these tests drive the real event handlers rather than setting properties.
 */
beforeEach(function () {
    PayloadLog::clear();
});

it('keeps the three formats the editor hands over', function () {
    $screen = new PayloadInspector;

    $screen->onChanged('<p>Hi</p>', 'Hi', '{"version":2,"blocks":[]}');

    expect($screen->html)->toBe('<p>Hi</p>');
    expect($screen->text)->toBe('Hi');
    expect($screen->json)->toBe('{"version":2,"blocks":[]}');
    expect($screen->changes)->toBe(1);
});

it('splits the files out of the document', function () {
    $screen = new PayloadInspector;

    $screen->onChanged('<p>Trip</p><figure><img src="/tmp/a.jpg" alt=""></figure>', 'Trip', json_encode([
        'version' => 2,
        'blocks' => [
            ['type' => 'p', 'runs' => [['text' => 'Trip', 'marks' => []]]],
            ['type' => 'image', 'src' => '/tmp/a.jpg', 'uploadId' => 'up-123'],
            ['type' => 'video', 'localPath' => '/tmp/b.mp4'],
        ],
    ]));

    $files = $screen->files();

    expect($files)->toHaveCount(2);
    expect($files[0])->toMatchArray(['kind' => 'image', 'state' => 'uploaded']);
    // No `src` yet means the upload has not finished.
    expect($files[1])->toMatchArray(['kind' => 'video', 'state' => 'pending']);
});

/**
 * `src` and `localPath` are different things and the screen must not collapse
 * them. It used to show whichever it found first, preferring the local one —
 * so an uploaded file displayed the picker's cache filename while its badge
 * said "uploaded", which is precisely the confusion this screen exists to
 * prevent.
 */
it('shows where a file points and where it lives, separately', function () {
    $screen = new PayloadInspector;

    $screen->onChanged('<p>x</p>', 'x', json_encode([
        'version' => 2,
        'blocks' => [[
            'type' => 'image',
            'src' => 'https://cdn.example.com/stored/abc.jpg',
            'localPath' => '/tmp/cache/cropped_original.jpg',
        ]],
    ]));

    $file = $screen->files()[0];

    expect($file['src'])->toBe('https://cdn.example.com/stored/abc.jpg');
    expect($file['local'])->toBe('/tmp/cache/cropped_original.jpg');
    expect($file['state'])->toBe('uploaded');
});

/**
 * The editor drops the correlation id the moment an upload completes, so an
 * always-empty uploadId column reads as broken. It is only shown while it
 * still means something.
 */
it('carries the upload id only while the upload is in flight', function () {
    $screen = new PayloadInspector;

    $screen->onChanged('<p>x</p>', 'x', json_encode([
        'version' => 2,
        'blocks' => [
            ['type' => 'image', 'localPath' => '/tmp/a.jpg', 'uploadId' => 'up-123'],
            ['type' => 'image', 'src' => '/tmp/stored.jpg'],
        ],
    ]));

    $files = $screen->files();

    expect($files[0]['uploadId'])->toBe('up-123');
    expect($files[1]['uploadId'])->toBe('');
});

it('says so when the local copy has been cleared from under it', function () {
    $screen = new PayloadInspector;

    $screen->onChanged('<p>x</p>', 'x', json_encode([
        'version' => 2,
        'blocks' => [['type' => 'image', 'localPath' => '/tmp/definitely-not-here-'.uniqid().'.jpg']],
    ]));

    expect($screen->files()[0]['missing'])->toBeTrue();
});

it('can be put back to empty', function () {
    $screen = new PayloadInspector;

    $screen->onChanged('<p>a</p>', 'a', '{"version":2,"blocks":[]}');
    $screen->onSaved('<p>a</p>', 'a', '{"version":2,"blocks":[]}');

    $screen->reset();

    expect($screen->html)->toBe('');
    expect($screen->text)->toBe('');
    expect($screen->json)->toBe('');
    expect($screen->changes)->toBe(0);
    expect($screen->saved)->toBeFalse();
    expect($screen->files())->toBe([]);
});

it('records the conversation, newest first', function () {
    $screen = new PayloadInspector;

    $screen->onChanged('<p>a</p>', 'a', '{}');
    $screen->onSaved('<p>a</p>', 'a', '{}');

    $events = array_column(PayloadLog::entries(), 'event');

    expect($events[0])->toBe('ContentSaved');
    expect($events[1])->toBe('ContentChanged');
});

/**
 * The reason the try/catch is there: on a device a throw inside a handler
 * leaves nothing behind. A debugging screen that swallowed them would be
 * worse than no screen at all.
 */
it('records a failure instead of losing it', function () {
    $screen = new class extends PayloadInspector
    {
        protected function capture(string $html, string $text, string $json): void
        {
            throw new RuntimeException('storage is full');
        }
    };

    $screen->onChanged('<p>a</p>', 'a', '{}');

    $entry = PayloadLog::entries()[0];

    expect($entry['kind'])->toBe('failure');
    expect($entry['event'])->toBe('ContentChanged');
    expect($entry['detail'])->toContain('storage is full');
});

it('caps the log rather than growing without limit', function () {
    foreach (range(1, PayloadLog::LIMIT + 15) as $i) {
        PayloadLog::event('Tick', (string) $i);
    }

    expect(PayloadLog::count())->toBe(PayloadLog::LIMIT);
    expect(PayloadLog::entries()[0]['detail'])->toBe((string) (PayloadLog::LIMIT + 15));
});
