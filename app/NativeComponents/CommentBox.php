<?php

namespace App\NativeComponents;

use App\Concerns\InteractsWithWysiwygEditor;
use Native\Mobile\Edge\NativeComponent;

/**
 * Comment box example — a locked-down, "super simple" editor.
 *
 * A fake thread where replies are written with the `comment` preset: only
 * bold / italic / link, capped at 500 characters with the live counter. Shows
 * that the same plugin scales DOWN to a one-line reply box.
 */
class CommentBox extends NativeComponent
{
    use InteractsWithWysiwygEditor;

    /** @var list<array{author: string, text: string}> */
    public array $comments = [
        ['author' => 'Maya', 'text' => 'This native editor feels exactly like the platform keyboard — no webview lag at all.'],
        ['author' => 'Jonas', 'text' => 'Agreed. The comment preset keeps people from pasting a whole formatted document in here.'],
    ];

    protected function editorOptions(): array
    {
        return [
            'preset' => 'comment',       // bold · italic · link only
            'maxLength' => 500,          // hard cap
            // The cap decides when Save refuses; this is what asks for the
            // live "n/500" beside it.
            'counts' => ['characters'],
            'title' => 'Add comment',
            'placeholder' => 'Write a comment…',
        ];
    }

    protected function contentSaved(?string $id): void
    {
        $this->comments[] = ['author' => 'You', 'text' => $this->text];

        // Next comment starts from a blank editor.
        $this->html = '';
        $this->text = '';
    }
}
