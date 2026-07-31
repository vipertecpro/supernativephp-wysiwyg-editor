<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Models\Page;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\SuggestionRequested;
use Vipertecpro\WysiwygEditor\Events\ToolTapped;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Demo 4 — block-based writing in the shape of Notion.
 *
 * The three social demos were about a composer: a short thing you write and
 * send. This one is about a DOCUMENT — and the two capabilities that makes it
 * need are the ones a notes app asks for first.
 *
 * A `/` menu, so a block type is something you type rather than something you
 * reach for on a bar. The editor spots the slash and asks what matches; which
 * commands exist is ours to decide, and the row we answer with names the tool
 * to run. `/date` is in the list precisely because the editor has no idea what
 * a date is — it reports the pick and we insert one.
 *
 * And to-dos, which are a block type rather than a decoration: the tick
 * survives the save, so a page re-opens with the same things done.
 */
class NotionPages extends NativeComponent
{
    // `/image` asks the HOST to pick, because the editor ships no picker.
    use InsertsMediaWithMarketplacePlugins;

    /** The page whose `···` menu is open, if any. */
    public ?int $actionsFor = null;

    public bool $confirmingDelete = false;

    /** The page whose icon is being chosen, if any. */
    public ?int $iconFor = null;

    /**
     * The commands `/` offers.
     *
     * ────────────────────────────────────────────────────────────────────
     *  These are OURS, not the editor's.
     * ────────────────────────────────────────────────────────────────────
     *
     * The editor supplies the mechanism and never the list. It spots the
     * slash, asks what matches, and runs whichever tool the chosen row names —
     * so which blocks a document app offers is the app's decision.
     *
     * `date` is the interesting one: it is not a tool the editor owns, so the
     * pick comes back as ToolTapped and we do the work. That is how an app
     * adds a command the editor has never heard of.
     *
     * @var list<array<string, string>>
     */
    protected const COMMANDS = [
        ['id' => 'h1', 'label' => 'Heading 1', 'detail' => 'Big section heading', 'icon' => 'h1', 'tool' => 'h1'],
        ['id' => 'h2', 'label' => 'Heading 2', 'detail' => 'Medium section heading', 'icon' => 'h2', 'tool' => 'h2'],
        ['id' => 'h3', 'label' => 'Heading 3', 'detail' => 'Small section heading', 'icon' => 'h3', 'tool' => 'h3'],
        ['id' => 'todo', 'label' => 'To-do list', 'detail' => 'Track tasks with a checkbox', 'icon' => 'checklist', 'tool' => 'checklist'],
        ['id' => 'bullet', 'label' => 'Bulleted list', 'detail' => 'A simple bulleted list', 'icon' => 'bulletList', 'tool' => 'bulletList'],
        ['id' => 'number', 'label' => 'Numbered list', 'detail' => 'A list with numbering', 'icon' => 'orderedList', 'tool' => 'orderedList'],
        ['id' => 'quote', 'label' => 'Quote', 'detail' => 'Capture a quotation', 'icon' => 'blockquote', 'tool' => 'blockquote'],
        ['id' => 'code', 'label' => 'Code', 'detail' => 'Inline monospaced text', 'icon' => 'code', 'tool' => 'code'],
        ['id' => 'divider', 'label' => 'Divider', 'detail' => 'Separate two sections', 'icon' => 'divider', 'tool' => 'divider'],
        ['id' => 'image', 'label' => 'Image', 'detail' => 'Upload or embed a picture', 'icon' => 'image', 'tool' => 'image'],
        ['id' => 'date', 'label' => "Today's date", 'detail' => 'Insert the date — our command, not the editor\'s', 'icon' => 'calendar', 'tool' => 'date'],
    ];

    /** The icons a page can wear. */
    public const ICONS = ['📄', '📌', '🗂️', '💡', '🎯', '🧪', '📝', '🚀', '📊', '🔧', '🌱', '🗓️'];

    public function newPage(): void
    {
        $page = Page::create([
            'icon' => '📄',
            'body_html' => '',
            'body_text' => '',
        ]);

        $this->openEditor($page);
    }

    public function openPage(int $id): void
    {
        $page = Page::find($id);

        if ($page !== null) {
            $this->openEditor($page);
        }
    }

    public function showActions(int $id): void
    {
        $this->actionsFor = $id;
    }

    public function closeActions(): void
    {
        $this->actionsFor = null;
        $this->confirmingDelete = false;
    }

    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = false;
    }

    public function delete(int $id): void
    {
        // A real client would DELETE against its API here.
        Page::destroy($id);

        $this->actionsFor = null;
        $this->confirmingDelete = false;
    }

    /** The icon picker: a page's face is chosen from the list, as in Notion. */
    public function chooseIcon(int $id): void
    {
        $this->actionsFor = null;
        $this->iconFor = $id;
    }

    public function closeIcons(): void
    {
        $this->iconFor = null;
    }

    public function setIcon(string $icon): void
    {
        if ($this->iconFor !== null && in_array($icon, self::ICONS, true)) {
            Page::whereKey($this->iconFor)->update(['icon' => $icon]);
        }

        $this->iconFor = null;
    }

    protected function openEditor(Page $page): void
    {
        WysiwygEditor::open($page->body_json ?: $page->body_html, [
            // A document, not a post: no cap, no counter, nothing counting down.
            'placeholder' => 'Untitled — type / for commands',
            'toolbar' => [
                'bold', 'italic', 'underline', 'strikethrough', 'code',
                'h1', 'h2', 'h3',
                'bulletList', 'orderedList', 'checklist', 'blockquote',
                'link', 'image', 'divider',
            ],
            // Type the block you want instead of hunting for it on the bar.
            'triggers' => ['/' => 'command'],
            // Room to read. A page is looked at for longer than a post.
            'typography' => ['fontSize' => 18],
            'spacing' => 'roomy',
            'mediaLayout' => 'blocks',
            // Backing out of a page keeps what was written: a document is not
            // a post you decide whether to send.
            'cancelMode' => 'discard',
            'cancelStyle' => 'text',
            'strings' => ['save' => 'Done', 'cancel' => 'Close'],
            'id' => 'page-'.$page->id,
        ]);
    }

    /**
     * The user typed `/` and is writing after it.
     *
     * Fires on every keystroke, so this answers from memory and caps the list.
     * A real app with a long command palette would debounce and hit its API.
     */
    #[On(SuggestionRequested::class)]
    public function onSuggestionRequested(string $kind, string $query = '', ?string $id = null): void
    {
        if ($kind !== 'command' || $id === null || ! str_starts_with($id, 'page-')) {
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

    /**
     * A command the EDITOR does not own.
     *
     * `/date` is ours: the editor deleted the "/date" the user typed, found no
     * tool of its own by that name, and reported the pick instead of guessing.
     * Inserting the date is our business, because a date format is a product
     * decision and an editor should not have an opinion about it.
     */
    #[On(ToolTapped::class)]
    public function onToolTapped(string $tool, ?string $id = null): void
    {
        if ($tool === 'date' && $id !== null && str_starts_with($id, 'page-')) {
            WysiwygEditor::insertText(now()->format('j F Y'));
        }
    }

    #[On(ContentSaved::class)]
    public function onSaved(string $html, string $text, string $json = '', ?string $id = null): void
    {
        if ($id === null || ! str_starts_with($id, 'page-')) {
            return;
        }

        // ────────────────────────────────────────────────────────────────
        //  REPLACE THIS WITH YOUR API.
        // ────────────────────────────────────────────────────────────────
        //
        // Written to SQLite ON THE DEVICE because this demo has no server.
        // See ContentSaved for the shape of all three forms, and
        // WysiwygEditor::attachments($json) for sending the files separately.
        //
        // `json` matters more here than anywhere else: it is the only form
        // that carries which to-dos are ticked in a way that survives a
        // re-open, along with any picture still uploading.
        Page::whereKey((int) substr($id, strlen('page-')))
            ->update(['body_html' => $html, 'body_text' => $text, 'body_json' => $json]);
    }

    public function preview(string $kind, string $source, string $caption = ''): void
    {
        WysiwygEditor::preview($kind, $source, $caption);
    }

    public function render(): Element
    {
        return $this->view('notion-pages', [
            'pages' => Page::query()->latest('updated_at')->latest('id')->get(),
            'actionsFor' => $this->actionsFor,
            'confirmingDelete' => $this->confirmingDelete,
            'iconFor' => $this->iconFor,
            'icons' => self::ICONS,
        ]);
    }
}
