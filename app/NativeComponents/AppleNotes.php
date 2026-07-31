<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Models\Note;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentChanged;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Demo 5 — native mobile interactions, in the shape of Apple Notes.
 *
 * The other four were about what an editor can DO. This one is about how it
 * behaves: there is no Save button, because the note is already written by the
 * time you would reach for one.
 *
 * `changeDebounce` is the whole trick. The editor emits ContentChanged when
 * typing settles and the app writes it away; `saveStyle => 'none'` then drops
 * the button that would otherwise claim to do what the app is already doing,
 * and the close control commits whatever the debounce had not caught yet.
 *
 * Everything else here — folders, pinning, swipe to delete — is filing rather
 * than editing, which is why the editor knows nothing about any of it.
 */
class AppleNotes extends NativeComponent
{
    // The photo button asks the HOST to pick; the editor ships no picker.
    use InsertsMediaWithMarketplacePlugins;

    /** Which demo a note belongs to; two of them share the table. */
    public const SURFACE = 'apple';

    /** How long after typing stops the note is written away. */
    public const AUTOSAVE_MS = 700;

    /** The folders a note can sit in. Apple ships a handful; so do we. */
    public const FOLDERS = ['Notes', 'Ideas', 'Shopping'];

    public string $folder = 'Notes';

    public function showFolder(string $folder): void
    {
        if (in_array($folder, self::FOLDERS, true)) {
            $this->folder = $folder;
        }
    }

    public function newNote(): void
    {
        $note = Note::create([
            'surface' => self::SURFACE,
            'folder' => $this->folder,
            'body_html' => '',
            'body_text' => '',
        ]);

        $this->openEditor($note);
    }

    public function openNote(int $id): void
    {
        $note = Note::query()->onSurface(self::SURFACE)->find($id);

        if ($note !== null) {
            $this->openEditor($note);
        }
    }

    /** Apple's swipe: pin to the top, or bin it. */
    public function togglePin(int $id): void
    {
        $note = Note::query()->onSurface(self::SURFACE)->find($id);

        if ($note !== null) {
            $note->update(['pinned' => ! $note->pinned]);
        }
    }

    public function deleteNote(int $id): void
    {
        // A real client would DELETE against its API here.
        Note::whereKey($id)->onSurface(self::SURFACE)->delete();
    }

    protected function openEditor(Note $note): void
    {
        WysiwygEditor::open($note->body_html, [
            'placeholder' => 'Start writing…',
            // What Apple Notes actually offers: formatting, lists, checklists
            // and a camera. No polls, no embeds — this is a notebook.
            'toolbar' => [
                'bold', 'italic', 'underline', 'strikethrough',
                'h1', 'h2',
                'checklist', 'bulletList', 'orderedList',
                'link', 'image', 'camera',
            ],
            // ────────────────────────────────────────────────────────────
            //  THE POINT OF THIS DEMO.
            // ────────────────────────────────────────────────────────────
            //
            // The editor emits ContentChanged this many milliseconds after
            // typing stops, and we write it away. With that running there is
            // nothing left for a Save button to do — so `none` removes it and
            // the close control commits whatever the debounce had not caught.
            'changeDebounce' => self::AUTOSAVE_MS,
            'saveStyle' => 'none',
            'cancelStyle' => 'text',
            'strings' => ['save' => 'Done'],
            'typography' => ['fontSize' => 18],
            'spacing' => 'roomy',
            'id' => 'note-'.$note->id,
        ]);
    }

    /**
     * Typing settled. This is the autosave.
     *
     * ────────────────────────────────────────────────────────────────────
     *  REPLACE THIS WITH YOUR API.
     * ────────────────────────────────────────────────────────────────────
     *
     * A real client would PATCH here, and would want to think about what
     * happens offline — the editor is indifferent either way, because it has
     * already handed over everything it knows. See ContentSaved for the shape.
     */
    #[On(ContentChanged::class)]
    public function onChanged(string $html, string $text, string $json = '', ?string $id = null): void
    {
        $this->write($html, $text, $json, $id);
    }

    /** Closing commits whatever the debounce had not caught yet. */
    #[On(ContentSaved::class)]
    public function onSaved(string $html, string $text, string $json = '', ?string $id = null): void
    {
        $this->write($html, $text, $json, $id);
    }

    protected function write(string $html, string $text, string $json, ?string $id): void
    {
        if ($id === null || ! str_starts_with($id, 'note-')) {
            return;
        }

        Note::whereKey((int) substr($id, strlen('note-')))
            ->onSurface(self::SURFACE)
            ->update(['body_html' => $html, 'body_text' => $text]);
    }

    public function render(): Element
    {
        return $this->view('apple-notes', [
            'notes' => Note::query()
                ->onSurface(self::SURFACE)
                ->where('folder', $this->folder)
                // Pinned first, then most recently touched — Apple's order.
                ->orderByDesc('pinned')
                ->latest('updated_at')
                ->latest('id')
                ->get(),
            'folder' => $this->folder,
            'folders' => self::FOLDERS,
            'counts' => Note::query()->onSurface(self::SURFACE)
                ->selectRaw('folder, count(*) as total')
                ->groupBy('folder')
                ->pluck('total', 'folder'),
        ]);
    }
}
