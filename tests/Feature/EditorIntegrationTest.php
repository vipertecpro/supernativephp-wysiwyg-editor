<?php

use App\NativeComponents\NotionPages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\FakeBridge;
use Native\Mobile\Testing\Native;

uses(RefreshDatabase::class);

/**
 * The macros these tests use come from the plugin, but the extension point
 * they hang off is new in NativePHP 4.0.0 — and 4.0.0 currently cannot build
 * this app (see the note in the plugin's README). Pinned to 4.0.0-rc.1, the
 * FakeBridge has no Macroable, so there is nothing to assert against.
 *
 * These run the moment the constraint moves forward.
 */
beforeEach(function () {
    if (! method_exists(FakeBridge::class, 'macro')) {
        test()->markTestSkipped('FakeBridge macros need NativePHP 4.0.0.');
    }
});

/**
 * The plugin's own test vocabulary, exercised the way a host app would.
 *
 * These assertions come from the PLUGIN — see BridgeMacros — so a test can
 * say what it means instead of knowing that opening an editor is a
 * `WysiwygEditor.Open` call carrying JSON.
 */
it('opens the editor when a new page is started', function () {
    $bridge = Native::fakeBridge();

    Native::test(NotionPages::class)->call('newPage');

    $bridge->assertEditorOpened();
});

it('answers a slash command with the matching rows', function () {
    $bridge = Native::fakeBridge();

    Native::test(NotionPages::class)
        ->call('onSuggestionRequested', 'command', 'to-do', 'page-1');

    $bridge->assertSuggestionsOffered(['To-do list']);
});

it('offers nothing for a lookup that is not ours', function () {
    $bridge = Native::fakeBridge();

    Native::test(NotionPages::class)
        ->call('onSuggestionRequested', 'mention', 'ada', 'page-1');

    $bridge->assertNoSuggestionsOffered();
});

/** `/date` is the host's own command: the editor reports it, we write it. */
it('writes the date for a command the editor does not own', function () {
    $bridge = Native::fakeBridge();

    Native::test(NotionPages::class)->call('onToolTapped', 'date', 'page-1');

    $bridge->assertTextInserted(now()->format('j F Y'));
});

/**
 * Somebody reaching for a to-do types `/todo`, not `/to-do`. A plain
 * str_contains against the label says no, because the label is hyphenated.
 */
it('finds a command however the user punctuates it', function (string $typed) {
    $bridge = Native::fakeBridge();

    Native::test(NotionPages::class)
        ->call('onSuggestionRequested', 'command', $typed, 'page-1');

    $bridge->assertSuggestionsOffered(['To-do list']);
})->with(['todo', 'to-do', 'To-Do', 'TODO']);
