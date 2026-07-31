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

/**
 * A handler is only a control if the element it sits on can be pressed.
 *
 * `<image>`, `<text>` and `<icon>` accept the directive on iOS and ignore it
 * on Android, so a control written that way works on one platform and is dead
 * on the other. The back control on three demo screens was dead on Android for
 * exactly this reason, and every photo in a post was untappable — none of which
 * the "no dead controls" test above could see, because the handler it points
 * at exists and is perfectly callable.
 *
 * Put the directive on a `<pressable>`, `<row>`, `<column>` or `<button>` and
 * wrap the visual inside it.
 */
it('never hangs a handler on an element that cannot be pressed', function () {
    $offenders = [];

    foreach (glob(resource_path('views/native/**/*.blade.php')) + glob(resource_path('views/native/*.blade.php')) as $view) {
        $markup = file_get_contents($view);

        preg_match_all('/<(image|text|icon)((?:[^<>]|\n)*?)>/', $markup, $tags, PREG_SET_ORDER);

        foreach ($tags as $tag) {
            if (preg_match('/@(press|navigate|swipe)/', $tag[2])) {
                $offenders[] = basename($view).' <'.$tag[1].'>';
            }
        }
    }

    expect($offenders)->toBe([], 'handlers that do nothing on Android: '.implode(', ', $offenders));
});
