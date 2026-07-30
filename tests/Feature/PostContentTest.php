<?php

use App\Support\PostContent;

/**
 * What a timeline row draws, from what the editor saved.
 *
 * The spans are the interesting part: a mention has to arrive with its entity
 * reference intact, or the row cannot tell a name apart from the prose around
 * it — which is the whole reason the editor stores it as a link.
 */
function document(array $blocks): string
{
    return json_encode(['blocks' => $blocks]);
}

function paragraph(array $runs): array
{
    return ['type' => 'p', 'runs' => $runs];
}

it('keeps a mention as its own span, carrying the entity reference', function () {
    $content = PostContent::parse(document([
        paragraph([
            ['text' => 'Shipping this with '],
            ['text' => '@Grace Hopper', 'marks' => ['link' => 'mention:u2']],
            ['text' => ' today'],
        ]),
    ]));

    expect($content['paragraphs'])->toBe([[
        ['text' => 'Shipping this with ', 'link' => ''],
        ['text' => '@Grace Hopper', 'link' => 'mention:u2'],
        ['text' => ' today', 'link' => ''],
    ]]);
});

it('reads the plain text back out of the paragraphs', function () {
    $content = PostContent::parse(document([
        paragraph([
            ['text' => 'Hello '],
            ['text' => '#nativephp', 'marks' => ['link' => 'hashtag:t1']],
        ]),
    ]));

    expect($content['text'])->toBe('Hello #nativephp');
});

it('merges runs that would draw identically', function () {
    // The editor splits a run per styling change, so a paragraph typed in one
    // go can arrive as several. A <text> per keystroke is not what we want.
    $content = PostContent::parse(document([
        paragraph([
            ['text' => 'one '],
            ['text' => 'two '],
            ['text' => 'three'],
        ]),
    ]));

    expect($content['paragraphs'])->toBe([[['text' => 'one two three', 'link' => '']]]);
});

it('keeps each paragraph separate, so a row can draw them apart', function () {
    $content = PostContent::parse(document([
        paragraph([['text' => 'first']]),
        paragraph([['text' => 'second']]),
    ]));

    expect($content['text'])->toBe("first\nsecond")
        ->and($content['paragraphs'])->toHaveCount(2);
});

it('drops empty paragraphs rather than emitting blank gaps', function () {
    $content = PostContent::parse(document([
        paragraph([['text' => 'kept']]),
        paragraph([]),
        paragraph([['text' => 'also kept']]),
    ]));

    expect($content['text'])->toBe("kept\nalso kept");
});

it('leaves media out of the paragraphs', function () {
    $content = PostContent::parse(document([
        paragraph([['text' => 'a photo']]),
        ['type' => 'image', 'src' => 'https://example.com/a.jpg'],
    ]));

    expect($content['text'])->toBe('a photo')
        ->and($content['images'])->toHaveCount(1);
});

it('survives a document it cannot read', function () {
    expect(PostContent::parse('not json'))
        ->toBe(['text' => '', 'paragraphs' => [], 'images' => [], 'video' => null, 'poll' => null]);
});
