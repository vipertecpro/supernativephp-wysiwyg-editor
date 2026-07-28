<?php

use App\Support\RichText;

it('parses every block type of the plugin contract', function () {
    $html = '<h1>Title</h1>'
        .'<p>Intro with <strong>bold</strong> and <em>italic</em>.</p>'
        .'<p><br></p>'
        .'<ul><li>one</li><li>two</li></ul>'
        .'<ol><li>first</li></ol>'
        .'<blockquote>Quoted.</blockquote>';

    expect(RichText::blocks($html))->toBe([
        ['type' => 'h1', 'text' => 'Title'],
        ['type' => 'p', 'text' => 'Intro with bold and italic.'],
        ['type' => 'p', 'text' => ''],
        ['type' => 'ul', 'items' => ['one', 'two']],
        ['type' => 'ol', 'items' => ['first']],
        ['type' => 'blockquote', 'text' => 'Quoted.'],
    ]);
});

it('flattens nested inline marks and decodes entities', function () {
    $html = '<p><a href="https://x.io"><strong>go</strong></a> now &amp; again</p>';

    expect(RichText::blocks($html))->toBe([
        ['type' => 'p', 'text' => 'go now & again'],
    ]);
});

it('returns no blocks for empty content', function () {
    expect(RichText::blocks(''))->toBe([]);
});

it('excerpts on a word boundary with an ellipsis', function () {
    expect(RichText::excerpt('short text', 60))->toBe('short text')
        ->and(RichText::excerpt(str_repeat('word ', 30), 20))->toEndWith('…')
        ->and(mb_strlen(RichText::excerpt(str_repeat('word ', 30), 20)))->toBeLessThanOrEqual(20);
});
