<?php

use App\NativeComponents\AppleNotes;
use App\NativeComponents\CommentBox;
use App\NativeComponents\FacebookFeed;
use App\NativeComponents\LinkedInFeed;
use App\NativeComponents\Notes;
use App\NativeComponents\NotionPages;
use App\NativeComponents\XTimeline;
use Illuminate\Support\Str;

/**
 * State a component writes but nothing ever reads.
 *
 * The same failure as a control that leads nowhere, one layer down: a handler
 * assigns a property, the property reaches no view and no config, and the
 * feature looks wired while doing nothing. It happened to `$lastTool`,
 * `$scheduledFor` and `$savedAt` — twice after I had already fixed it once.
 */
it('renders every piece of state a component keeps', function (string $class) {
    $file = (new ReflectionClass($class))->getFileName();
    $source = file_get_contents($file);

    $view = Str::of(class_basename($class))->kebab()->toString();
    $blade = base_path("resources/views/native/{$view}.blade.php");
    $rendered = file_exists($blade) ? file_get_contents($blade) : '';

    preg_match_all('/public (?:\?)?(?:string|int|bool|array)\s+\$(\w+)/', $source, $properties);

    $dead = [];

    foreach ($properties[1] as $property) {
        // Reaches the view directly, is passed to it by name, or is read
        // somewhere else in the component (a config, a query, a guard).
        $inBlade = str_contains($rendered, '$'.$property);
        $passed = str_contains($source, "'{$property}' =>");
        $reads = preg_match_all('/\$this->'.$property.'\b/', $source);

        if (! $inBlade && ! $passed && $reads <= 1) {
            $dead[] = class_basename($class).'::$'.$property;
        }
    }

    expect($dead)->toBe([]);
})->with([
    XTimeline::class,
    LinkedInFeed::class,
    FacebookFeed::class,
    NotionPages::class,
    AppleNotes::class,
    Notes::class,
    CommentBox::class,
]);
