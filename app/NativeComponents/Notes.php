<?php

namespace App\NativeComponents;

use App\Models\Note;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Notes example — a real notes app backed by the full editor.
 *
 * The list lives in SQLite; tapping a note re-opens it in the native editor
 * and creating one starts empty. The editor's `id` option correlates each
 * save back to the right row — 'new' means "create".
 */
class Notes extends NativeComponent
{
    /** Options shared by create and edit. */
    protected function editorOptions(): array
    {
        return [
            // Bigger type and a roomier measure — the whole ramp follows.
            'typography' => ['fontSize' => 20],
            'spacing' => 'roomy',
            'placeholder' => 'Start with a title — the first line names the note…',
        ];
    }

    public function newNote(): void
    {
        WysiwygEditor::open('', [
            ...$this->editorOptions(),
            'title' => 'New note',
            'id' => 'new',
        ]);
    }

    public function editNote(int $noteId): void
    {
        $note = Note::find($noteId);

        if ($note === null) {
            return;
        }

        WysiwygEditor::open($note->body_html, [
            ...$this->editorOptions(),
            'title' => 'Edit note',
            'id' => (string) $noteId,
        ]);
    }

    public function deleteNote(int $noteId): void
    {
        Note::destroy($noteId);
    }

    #[On(ContentSaved::class)]
    public function onSaved(string $html, string $text, ?string $id = null): void
    {
        if (trim($text) === '') {
            return; // an empty save creates nothing
        }

        if ($id === 'new' || $id === null) {
            Note::create(['body_html' => $html, 'body_text' => $text]);

            return;
        }

        Note::whereKey((int) $id)->update(['body_html' => $html, 'body_text' => $text]);
    }

    public function render(): Element
    {
        return $this->view('notes', [
            'notes' => Note::latest('updated_at')->get(),
        ]);
    }
}
