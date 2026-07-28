<?php

use App\Models\Note;
use App\NativeComponents\Notes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Native\Mobile\Testing\Native;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;

uses(RefreshDatabase::class);

it('renders the empty state without notes', function () {
    Native::test(Notes::class)
        ->assertSee('No notes yet');
});

it('lists notes by title', function () {
    Note::factory()->create([
        'body_html' => '<h1>Groceries</h1><p>Milk and eggs</p>',
        'body_text' => "Groceries\nMilk and eggs",
    ]);

    Native::test(Notes::class)
        ->assertSee('Groceries')
        ->assertSee('Milk and eggs');
});

it('opens the native editor to create a note', function () {
    $test = Native::test(Notes::class)->press('newNote');

    $test->bridge()->assertCalled(
        'WysiwygEditor.Open',
        fn (array $params): bool => $params['id'] === 'new' && $params['content'] === ''
    );
});

it('opens the native editor with the saved html of an existing note', function () {
    $note = Note::factory()->create([
        'body_html' => '<p>Hello <strong>world</strong></p>',
        'body_text' => 'Hello world',
    ]);

    $test = Native::test(Notes::class)->press("editNote({$note->id})");

    $test->bridge()->assertCalled(
        'WysiwygEditor.Open',
        fn (array $params): bool => $params['id'] === (string) $note->id
            && $params['content'] === '<p>Hello <strong>world</strong></p>'
    );
});

it('creates a note when the editor saves with id new', function () {
    Native::test(Notes::class)
        ->emitNative(ContentSaved::class, [
            'html' => '<h1>Fresh</h1><p>Body</p>',
            'text' => "Fresh\nBody",
            'id' => 'new',
        ])
        ->assertSee('Fresh');

    expect(Note::sole())
        ->body_html->toBe('<h1>Fresh</h1><p>Body</p>')
        ->body_text->toBe("Fresh\nBody");
});

it('updates the right note when the editor saves with its id', function () {
    $note = Note::factory()->create();
    $other = Note::factory()->create();

    Native::test(Notes::class)
        ->emitNative(ContentSaved::class, [
            'html' => '<p>Rewritten</p>',
            'text' => 'Rewritten',
            'id' => (string) $note->id,
        ]);

    expect($note->refresh()->body_text)->toBe('Rewritten')
        ->and($other->refresh()->body_text)->not->toBe('Rewritten');
});

it('creates nothing when an empty document is saved', function () {
    Native::test(Notes::class)
        ->emitNative(ContentSaved::class, ['html' => '', 'text' => '', 'id' => 'new']);

    expect(Note::count())->toBe(0);
});

it('deletes a note from its row action', function () {
    $note = Note::factory()->create();

    Native::test(Notes::class)->press("deleteNote({$note->id})");

    expect(Note::count())->toBe(0);
});
