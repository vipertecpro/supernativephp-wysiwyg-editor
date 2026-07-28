<?php

use App\NativeComponents\CommentBox;
use Native\Mobile\Testing\Native;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\EditCancelled;
use Vipertecpro\WysiwygEditor\WysiwygEditor;

it('opens the editor with the locked-down comment preset', function () {
    $test = Native::test(CommentBox::class)->press('startEdit');

    $test->bridge()->assertCalled(
        'WysiwygEditor.Open',
        fn (array $params): bool => $params['toolbar'] === WysiwygEditor::TOOLBAR_PRESETS['comment']
            && $params['maxLength'] === 500
    );
});

it('appends the saved comment to the thread and resets the draft', function () {
    Native::test(CommentBox::class)
        ->emitNative(ContentSaved::class, [
            'html' => '<p>Nice <strong>work</strong></p>',
            'text' => 'Nice work',
        ])
        ->assertSee('Nice work')
        ->assertSet('html', '')
        ->assertSet('text', '');
});

it('keeps the thread unchanged when the editor is cancelled', function () {
    $test = Native::test(CommentBox::class)
        ->emitNative(EditCancelled::class, []);

    expect($test->get('comments'))->toHaveCount(2);
});
