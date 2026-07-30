<?php

use App\Models\Note;
use App\Models\Post;
use App\NativeComponents\FacebookFeed;
use App\NativeComponents\LinkedInFeed;
use App\NativeComponents\XTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The three demos share one table and are meant to be three apps.
 *
 * They did not always: a post written on a colour in the Facebook demo turned
 * up in LinkedIn as plain text and in X as a short post, because every feed
 * read every row.
 */
it('gives each demo a surface of its own', function () {
    $surfaces = [XTimeline::SURFACE, LinkedInFeed::SURFACE, FacebookFeed::SURFACE];

    expect($surfaces)->toBe(['x', 'linkedin', 'facebook'])
        ->and(array_unique($surfaces))->toHaveCount(3);
});

it('shows a feed only its own posts', function () {
    foreach (['x', 'linkedin', 'facebook'] as $surface) {
        Post::create([
            'surface' => $surface,
            'author_name' => 'You',
            'author_handle' => '@you',
            'body_html' => "<p>{$surface}</p>",
            'body_text' => $surface,
        ]);
    }

    expect(Post::query()->onSurface('linkedin')->pluck('body_text')->all())->toBe(['linkedin'])
        ->and(Post::query()->onSurface('facebook')->count())->toBe(1)
        ->and(Post::count())->toBe(3);
});

/**
 * Anything written before the split was written in X, which is where the
 * column's default puts it.
 */
it('leaves a row with no surface in the demo that had one', function () {
    Post::create([
        'author_name' => 'You',
        'author_handle' => '@you',
        'body_html' => '<p>older</p>',
        'body_text' => 'older',
    ]);

    expect(Post::query()->onSurface('x')->count())->toBe(1);
});

/**
 * Two note demos share the `notes` table for the same reason the three feeds
 * share `posts`: they are separate apps and must not see each other's rows.
 */
it('keeps the two note demos apart', function () {
    foreach (['notes', 'apple'] as $surface) {
        Note::create([
            'surface' => $surface,
            'body_html' => "<p>{$surface}</p>",
            'body_text' => $surface,
        ]);
    }

    expect(Note::query()->onSurface('apple')->pluck('body_text')->all())->toBe(['apple'])
        ->and(Note::query()->onSurface('notes')->count())->toBe(1);
});
