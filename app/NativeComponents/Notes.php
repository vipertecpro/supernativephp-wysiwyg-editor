<?php

namespace App\NativeComponents;

use App\Models\Note;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\SuggestionRequested;
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
    /** Which demo a note belongs to; two of them share the table. */
    public const SURFACE = 'notes';

    protected function editorOptions(): array
    {
        return [
            // Bigger type and a roomier measure — the whole ramp follows.
            'typography' => ['fontSize' => 20],
            'spacing' => 'roomy',
            'placeholder' => 'Start with a title — the first line names the note…',
            // A slash is a trigger like any other; what makes it a COMMAND is
            // that the rows we answer with name a tool.
            'triggers' => ['/' => 'command'],
        ];
    }

    /**
     * The commands `/` offers.
     *
     * ────────────────────────────────────────────────────────────────────
     *  These are OURS, not the editor's.
     * ────────────────────────────────────────────────────────────────────
     *
     * The editor supplies the mechanism and never the list: it spots the
     * slash, asks what matches, and runs whichever tool the chosen row names.
     * Which commands a notes app offers is the app's decision.
     *
     * @var list<array<string, string>>
     */
    protected const COMMANDS = [
        ['id' => 'h1', 'label' => 'Heading 1', 'icon' => 'h1', 'tool' => 'h1'],
        ['id' => 'h2', 'label' => 'Heading 2', 'icon' => 'h2', 'tool' => 'h2'],
        ['id' => 'todo', 'label' => 'To-do list', 'icon' => 'checklist', 'tool' => 'checklist'],
        ['id' => 'bullet', 'label' => 'Bulleted list', 'icon' => 'bulletList', 'tool' => 'bulletList'],
        ['id' => 'number', 'label' => 'Numbered list', 'icon' => 'orderedList', 'tool' => 'orderedList'],
        ['id' => 'quote', 'label' => 'Quote', 'icon' => 'blockquote', 'tool' => 'blockquote'],
        ['id' => 'divider', 'label' => 'Divider', 'icon' => 'divider', 'tool' => 'divider'],
        ['id' => 'image', 'label' => 'Image', 'icon' => 'image', 'tool' => 'image'],
    ];

    /**
     * The user typed `/` and is writing after it.
     *
     * Fires on every keystroke, so this answers from memory. A real app with a
     * long command list would cap it the same way.
     */
    #[On(SuggestionRequested::class)]
    public function onSuggestionRequested(string $kind, string $query = ''): void
    {
        if ($kind !== 'command') {
            return;
        }

        // Punctuation stripped so `/todo` finds "To-do list".
        $needle = preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim($query)));

        $matches = array_values(array_filter(
            self::COMMANDS,
            fn (array $command) => $this->commandMatches($command, (string) $needle),
        ));

        WysiwygEditor::suggestions($query, array_slice($matches, 0, 6));
    }

    /**
     * Does this command answer to what was typed?
     *
     * Matched against the id as well as the label, and with punctuation
     * ignored — somebody typing the obvious `/todo` should find "To-do list",
     * and a plain `str_contains` says no because of the hyphen.
     */
    protected function commandMatches(array $command, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $haystack = preg_replace('/[^a-z0-9]/', '', mb_strtolower($command['label'].' '.$command['id']));

        return str_contains((string) $haystack, $needle);
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
        $note = Note::query()->onSurface(self::SURFACE)->find($noteId);

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
        Note::whereKey($noteId)->onSurface(self::SURFACE)->delete();
    }

    #[On(ContentSaved::class)]
    public function onSaved(string $html, string $text, ?string $id = null): void
    {
        if (trim($text) === '') {
            return; // an empty save creates nothing
        }

        if ($id === 'new' || $id === null) {
            Note::create(['surface' => self::SURFACE, 'body_html' => $html, 'body_text' => $text]);

            return;
        }

        Note::whereKey((int) $id)->onSurface(self::SURFACE)
            ->update(['body_html' => $html, 'body_text' => $text]);
    }

    public function render(): Element
    {
        return $this->view('notes', [
            'notes' => Note::query()->onSurface(self::SURFACE)->latest('updated_at')->get(),
        ]);
    }
}
