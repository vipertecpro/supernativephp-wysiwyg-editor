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
    expect($files[0])->toMatchArray(['kind' => 'image', 'state' => 'uploaded', 'uploadId' => 'up-123']);
    // No `src` yet means the upload has not finished.
    expect($files[1])->toMatchArray(['kind' => 'video', 'state' => 'pending']);
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
