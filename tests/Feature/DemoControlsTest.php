<?php

use App\NativeComponents\FacebookFeed;
use App\NativeComponents\LinkedInFeed;
use App\NativeComponents\XTimeline;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Every control a demo draws must lead somewhere.
 *
 * This is the failure that keeps recurring: a tile, a row or a button is
 * declared, the editor draws it faithfully, and tapping it does nothing at
 * all. It looks exactly like a bug and there is no event to debug from — and
 * an app built by copying these demos would inherit the habit.
 */
function composerConfig(string $component): array
{
    $instance = new $component;
    $open = new ReflectionMethod($instance, 'openEditor');
    $open->setAccessible(true);

    $captured = [];

    // openEditor() calls the facade; swap it for a recorder.
    WysiwygEditor::swap(new class($captured)
    {
        public function __construct(public array &$captured) {}

        public function open(string $content, array $options = []): void
        {
            $this->captured = $options;
        }

        public function __call($name, $arguments) {}
    });

    $open->invoke($instance, '', 'new');

    return WysiwygEditor::getFacadeRoot()->captured;
}

dataset('composers', [
    'X' => [XTimeline::class],
    'LinkedIn' => [LinkedInFeed::class],
    'Facebook' => [FacebookFeed::class],
]);

it('names only sheets it actually declares', function (string $component) {
    $config = composerConfig($component);
    $declared = array_keys($config['sheets'] ?? []);

    $named = [];

    foreach (array_merge($config['accessories'] ?? [], $config['customTools'] ?? []) as $control) {
        if (($control['sheet'] ?? '') !== '') {
            $named[] = $control['sheet'];
        }
    }

    expect(array_diff($named, $declared))->toBe([]);
})->with('composers');

it('offers no sheet nothing opens', function (string $component) {
    $config = composerConfig($component);

    $named = array_filter(array_map(
        fn (array $c) => $c['sheet'] ?? '',
        array_merge($config['accessories'] ?? [], $config['customTools'] ?? []),
    ));

    expect(array_diff(array_keys($config['sheets'] ?? []), $named))->toBe([]);
})->with('composers');

/**
 * A glyph the editor does not have draws a blank gap, and a blank gap in a
 * toolbar reads as a rendering fault rather than a missing name.
 */
it('names only glyphs the editor can draw', function (string $component) {
    $swift = file_get_contents(base_path('vendor/vipertecpro/wysiwyg-editor/resources/ios/WysiwygEditorFunctions.swift'));

    preg_match_all('/"([a-zA-Z0-9]+)": ToolIcon\(/', $swift, $known);

    $config = composerConfig($component);
    $used = [];

    foreach (array_merge($config['accessories'] ?? [], $config['customTools'] ?? []) as $control) {
        if (($control['icon'] ?? '') !== '') {
            $used[] = $control['icon'];
        }
    }

    foreach ($config['sheets'] ?? [] as $sheet) {
        foreach ($sheet['options'] ?? [] as $option) {
            if (($option['icon'] ?? '') !== '') {
                $used[] = $option['icon'];
            }
        }
    }

    expect(array_diff(array_unique($used), $known[1]))->toBe([]);
})->with('composers');
