<?php

use App\Models\Post;
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

it('leaves a short post alone', function () {
    $content = PostContent::parse(document([paragraph([['text' => 'short enough']])]));
    $clipped = PostContent::clip($content['paragraphs']);

    expect($clipped['clipped'])->toBeFalse()
        ->and($clipped['paragraphs'])->toBe($content['paragraphs']);
});

it('clips a long post on a word boundary', function () {
    $content = PostContent::parse(document([paragraph([['text' => str_repeat('lorem ipsum ', 60)]])]));
    $clipped = PostContent::clip($content['paragraphs'], 30);

    $text = implode('', array_column($clipped['paragraphs'][0], 'text'));

    expect($clipped['clipped'])->toBeTrue()
        // Never mid-word, and never longer than asked for.
        ->and($text)->toBe('lorem ipsum lorem ipsum lorem')
        ->and(mb_strlen($text))->toBeLessThanOrEqual(30);
});

it('keeps a mention that survives the clip styled as a mention', function () {
    $content = PostContent::parse(document([
        paragraph([
            ['text' => 'Congratulations '],
            ['text' => '@Ada Lovelace', 'marks' => ['link' => 'mention:u1']],
            ['text' => ' on the new role, richly deserved after all these years'],
        ]),
    ]));

    $clipped = PostContent::clip($content['paragraphs'], 40);

    expect($clipped['clipped'])->toBeTrue()
        ->and($clipped['paragraphs'][0][1])
        ->toBe(['text' => '@Ada Lovelace', 'link' => 'mention:u1']);
});

it('drops the paragraphs past the clip rather than showing empty ones', function () {
    $content = PostContent::parse(document([
        paragraph([['text' => str_repeat('a', 50)]]),
        paragraph([['text' => 'never seen']]),
    ]));

    expect(PostContent::clip($content['paragraphs'], 20)['paragraphs'])->toHaveCount(1);
});

it('clips one long word rather than returning nothing', function () {
    // No space to back up to, so the cut has to land somewhere.
    $content = PostContent::parse(document([paragraph([['text' => str_repeat('x', 400)]])]));
    $clipped = PostContent::clip($content['paragraphs'], 20);

    expect($clipped['paragraphs'][0][0]['text'])->toBe(str_repeat('x', 20));
});

it('gives a post with no JSON something for a row to draw', function () {
    // Seeded rows, and anything written before the editor stored JSON, have
    // body_text and nothing else. A row that draws paragraphs would show them
    // as blank.
    $post = new Post(['body_text' => "first line\nsecond line", 'body_json' => '']);

    expect($post->content()['paragraphs'])->toBe([
        [['text' => 'first line', 'link' => '']],
        [['text' => 'second line', 'link' => '']],
    ]);
});
