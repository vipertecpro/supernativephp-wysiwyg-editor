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

    // array_merge, NOT `+`: the union operator keeps keys from the left, so
    // `+` silently drops the first file of the second list — which is how this
    // very guard missed apple-notes.blade.php while reporting success.
    $views = array_merge(
        glob(resource_path('views/native/*.blade.php')),
        glob(resource_path('views/native/**/*.blade.php')),
    );

    foreach ($views as $view) {
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

/**
 * A directive the precompiler does not recognise is silently dropped.
 *
 * `@swipe-delete` looks exactly like `@swipeDelete` and does nothing at all —
 * swipe-to-delete in the Apple Notes demo was inert on both platforms because
 * of one hyphen. Nothing warns: the attribute is simply not translated, and
 * the element renders without it.
 *
 * The recognised set is read out of the precompiler itself, so this follows
 * NativePHP rather than a list copied here that could drift from it.
 */
it('only uses event directives the precompiler recognises', function () {
    $precompiler = file_get_contents(base_path('vendor/nativephp/mobile/src/Edge/NativeTagPrecompiler.php'));

    // The precompiler translates in several passes, each with its own
    // alternation — take them all, not the first one found.
    expect(preg_match_all('/@\(([a-zA-Z|]+)\)=/', $precompiler, $lists))->toBeGreaterThan(0, 'cannot read the directive list');

    $known = ['navigate.back', 'navigate', 'a11y-label'];

    foreach ($lists[1] as $alternation) {
        $known = array_merge($known, explode('|', $alternation));
    }

    // If this fails, the alternation moved and everything below is checking
    // nothing. `toContain` is variadic, so it takes no message.
    expect($known)->toContain('swipeDelete');

    $unknown = [];

    // array_merge, NOT `+`: the union operator keeps keys from the left, so
    // `+` silently drops the first file of the second list — which is how this
    // very guard missed apple-notes.blade.php while reporting success.
    $views = array_merge(
        glob(resource_path('views/native/*.blade.php')),
        glob(resource_path('views/native/**/*.blade.php')),
    );

    foreach ($views as $view) {
        preg_match_all('/@([a-zA-Z][a-zA-Z.-]*)=/', file_get_contents($view), $used);

        foreach ($used[1] as $directive) {
            // A hyphenated name that is not known is a child-component event
            // binding — legitimate — UNLESS its camelCase form is a real
            // directive, which makes it a misspelling of one.
            $camel = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $directive))));

            if (! in_array($directive, $known, true) && in_array($camel, $known, true)) {
                $unknown[] = basename($view).' @'.$directive.' (meant @'.$camel.')';
            }
        }
    }

    expect($unknown)->toBe([], 'directives that are silently dropped: '.implode(', ', $unknown));
});

/**
 * An icon name that only iOS knows draws nothing at all on Android.
 *
 * Names are SF Symbols; NativePHP maps them onto Material icons for Android,
 * and anything absent from that map renders as empty space. Three of them were
 * in here — two in the payload log and one on the Notes screens — so a row of
 * events had its direction markers on one platform only.
 *
 * The map is read out of the installed runtime rather than copied, so this
 * follows NativePHP instead of drifting from it.
 */
it('only uses icons Android can actually draw', function () {
    $helper = base_path('nativephp/android/app/src/main/java/com/nativephp/mobile/ui/IconHelper.kt');

    if (! is_readable($helper)) {
        $this->markTestSkipped('the Android runtime is not installed — run native:install android');
    }

    $mapped = [];

    foreach (explode("\n", file_get_contents($helper)) as $line) {
        if (! str_contains($line, '->')) {
            continue;
        }

        preg_match_all('/"([a-zA-Z0-9._]+)"/', explode('->', $line)[0], $names);
        $mapped = array_merge($mapped, $names[1]);
    }

    expect($mapped)->toContain('doc');

    $missing = [];

    $views = array_merge(
        glob(resource_path('views/native/*.blade.php')),
        glob(resource_path('views/native/**/*.blade.php')),
    );

    foreach ($views as $view) {
        preg_match_all('/name="([a-z][a-zA-Z0-9._]*)"/', file_get_contents($view), $used);

        foreach (array_unique($used[1]) as $icon) {
            if (! in_array($icon, $mapped, true)) {
                $missing[] = basename($view).' → '.$icon;
            }
        }
    }

    expect($missing)->toBe([], 'icons that draw nothing on Android: '.implode(', ', $missing));
});
