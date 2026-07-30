<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Models\Post;
use App\Support\PostContent;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\AccessoryTapped;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\DraftRequested;
use Vipertecpro\WysiwygEditor\Events\SheetOptionPicked;
use Vipertecpro\WysiwygEditor\Events\ToolTapped;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Demo 1 — a short-post composer in the shape of X.
 *
 * The point of this screen is that the WHOLE composer is the plugin. There is
 * no formatting bar, no headings, no lists: `history => false` drops undo/redo
 * and the toolbar is just the media row, so what is left is a plain-text field
 * with a countdown ring and a filled Post pill — which is what X's composer is.
 *
 * Writing, reading, editing and deleting a post all run through that same
 * editor, which is the part worth showing: `open($post->body_html)` re-opens
 * an existing post with its content intact, and the `id` option is what tells
 * the save handler which row it belongs to.
 *
 * The layout and interactions are modelled on X; the branding is our own,
 * because copying the interaction design is the point and copying a logo is
 * not.
 */
class XTimeline extends NativeComponent
{
    // The photo / video buttons in the composer ask the HOST to pick; without
    // this the editor emits MediaRequested and nothing is listening.
    use InsertsMediaWithMarketplacePlugins;

    /** The account doing the posting, as a real client would know it. */
    public const AUTHOR = 'You';

    public const HANDLE = '@you';

    /** Which demo a post belongs to; the three share a table. */
    public const SURFACE = 'x';

    /** 280, the limit the ring counts toward. */
    public const LIMIT = 280;

    /** The post whose actions sheet is open, if any. */
    public ?int $actionsFor = null;

    /** True once Delete is tapped: the same sheet asks to confirm. */
    public bool $confirmingDelete = false;

    /** Chosen in the composer through the accessory rows. */
    public string $location = '';

    public string $audience = 'Everyone';

    /** Who may reply, chosen in the composer. */
    public string $replies = 'Everyone';

    /**
     * Who a post goes out to.
     *
     * @var list<array<string, string>>
     */
    protected const AUDIENCES = [
        ['id' => 'everyone', 'label' => 'Everyone', 'detail' => 'Anyone on or off X', 'icon' => 'globe'],
        ['id' => 'circle', 'label' => 'Circle', 'detail' => 'Only the people you picked', 'icon' => 'people'],
    ];

    /**
     * Who may reply to it.
     *
     * @var list<array<string, string>>
     */
    protected const REPLIERS = [
        ['id' => 'everyone', 'label' => 'Everyone', 'icon' => 'globe'],
        ['id' => 'following', 'label' => 'Accounts you follow', 'icon' => 'people'],
        ['id' => 'mentioned', 'label' => 'Only accounts you mention', 'icon' => 'people'],
    ];

    /**
     * The unsent post, if the writer backed out and kept it.
     *
     * @var array<string, string>
     */
    public array $draft = [];

    /** Which of our own toolbar buttons was tapped last, for the demo. */
    public string $lastTool = '';

    /**
     * Which timeline is showing.
     *
     * X leads with two: an algorithmic one and the accounts you chose. A demo
     * account follows nobody, so Following is honestly empty rather than
     * showing the same posts twice under a different name.
     */
    public string $tab = 'forYou';

    public function showTab(string $tab): void
    {
        $this->tab = $tab === 'following' ? 'following' : 'forYou';
    }

    /** Open the composer for a NEW post. */
    public function compose(): void
    {
        $this->openEditor('', 'new');
    }

    /** Re-open an existing post in the same editor, content intact. */
    public function edit(int $id): void
    {
        $this->actionsFor = null;
        $this->confirmingDelete = false;

        $post = Post::query()->onSurface(self::SURFACE)->find($id);

        if ($post && $post->author_handle === self::HANDLE) {
            // Re-open from the JSON, not the HTML. HTML cannot carry a local
            // file path, so a post whose photos never finished uploading would
            // come back without them.
            $this->openEditor($post->body_json ?: $post->body_html, (string) $post->id);
        }
    }

    /** The `···` on your own posts. */
    public function showActions(int $id): void
    {
        $this->actionsFor = $id;
    }

    public function closeActions(): void
    {
        $this->actionsFor = null;
        $this->confirmingDelete = false;
    }

    /**
     * Deleting cannot be undone, so it asks first — in the SAME sheet.
     *
     * A system alert chained off the sheet's dismissal never appeared: iOS
     * refuses to present one while a sheet is still on its way out, silently.
     * Confirming in place sidesteps that entirely, and a destructive
     * confirmation inside the action sheet is what iOS apps do anyway.
     */
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
        // A real client would DELETE against its API here — and would decide
        // whether removing a post also removes its attachments. This demo
        // leaves the files: they are small, and an attachment that outlives
        // its post is clutter, while deleting one still referenced elsewhere
        // breaks a post that was fine.
        Post::whereKey($id)->onSurface(self::SURFACE)->delete();

        $this->actionsFor = null;
        $this->confirmingDelete = false;
    }

    #[On(ContentSaved::class)]
    public function onPosted(string $html, string $text, string $json = '', ?string $id = null): void
    {
        if ($id === null || ! str_starts_with($id, 'x-post-')) {
            return;
        }

        // A photo with no caption, or a poll on its own, is a real post — so
        // "empty" has to mean no words AND no blocks, not no words.
        $parsed = PostContent::parse($json);
        $hasContent = trim($text) !== ''
            || $parsed['images'] !== []
            || $parsed['video'] !== null
            || $parsed['poll'] !== null;

        if (! $hasContent) {
            return;
        }

        $target = substr($id, strlen('x-post-'));

        // ────────────────────────────────────────────────────────────────
        //  REPLACE THIS WITH YOUR API.
        // ────────────────────────────────────────────────────────────────
        //
        // The post is written to SQLite ON THE DEVICE because this demo has no
        // server. A real client sends it and keeps whatever the server returns:
        //
        //     Http::withToken($user->apiToken)
        //         ->post('https://api.example.com/v1/posts', [
        //             'html' => $html,          // to render
        //             'text' => $text,          // for search and excerpts
        //             'json' => $json,          // canonical — media and polls
        //         ]);
        //
        // Send `json` if you ever want the post to be EDITABLE again. HTML
        // cannot carry a local file path or a poll's option ids, so a post
        // re-opened from HTML alone comes back without them — which is why
        // `edit()` below hands `body_json` to the editor, not `body_html`.
        //
        // Offline-first apps write locally first and sync after; the editor is
        // indifferent either way, because it has already handed you everything
        // it knows.

        if ($target === 'new') {
            Post::create([
                'surface' => self::SURFACE,
                'author_name' => self::AUTHOR,
                'author_handle' => self::HANDLE,
                'body_html' => $html,
                'body_text' => $text,
                'body_json' => $json,
            ]);

            return;
        }

        // The id came from us, but a save handler that trusts an id it was
        // handed is a habit worth not forming — an edit only ever touches
        // your own row.
        Post::whereKey((int) $target)
            ->onSurface(self::SURFACE)
            ->update(['body_html' => $html, 'body_text' => $text, 'body_json' => $json]);
    }

    /**
     * The user backed out but chose to keep what they had written.
     *
     * The editor hands the document over and steps back; where a draft lives
     * is our business. This demo keeps it on the device like everything else —
     * a real client would POST it to a drafts endpoint, or queue it.
     */
    #[On(DraftRequested::class)]
    public function onDraftRequested(string $html, string $text, string $json = '', ?string $id = null): void
    {
        if ($id === null || ! str_starts_with($id, 'x-post-')) {
            return;
        }

        $this->draft = ['html' => $html, 'text' => $text, 'json' => $json];
    }

    /** Resume a kept draft in the same editor. */
    public function resumeDraft(): void
    {
        if ($this->draft !== []) {
            $this->openEditor($this->draft['json'] ?: $this->draft['html'], 'new');
        }
    }

    public function discardDraft(): void
    {
        $this->draft = [];
    }

    /**
     * One of our own toolbar buttons was tapped.
     *
     * A real app would open a GIF picker or a scheduling sheet here. The
     * editor knows nothing about either — it drew the button and told us.
     */
    #[On(ToolTapped::class)]
    public function onToolTapped(string $tool): void
    {
        $this->lastTool = $tool;
    }

    /**
     * Show a photo or a video full-screen.
     *
     * Handed to the plugin, which already decodes images and plays video for
     * its own cards — and the platform ships no video element to build a
     * second viewer out of.
     */
    public function preview(string $kind, string $source, string $caption = ''): void
    {
        WysiwygEditor::preview($kind, $source, $caption);
    }

    /**
     * One of our own rows was tapped inside the editor.
     *
     * A real app would open a people picker or ask for a location here. The
     * editor stays open throughout; `setAccessory` writes the answer back into
     * the row so the user sees what they chose.
     */
    #[On(AccessoryTapped::class)]
    public function onAccessoryTapped(string $accessory): void
    {
        match ($accessory) {
            'location' => $this->chooseLocation(),
            default => null,
        };
    }

    /**
     * Something was chosen in one of OUR sheets.
     *
     * The editor presented it because it owns the screen; the options were
     * ours and so is what they mean. Write the answer back into the control
     * that opened it, so the user sees what they chose.
     */
    #[On(SheetOptionPicked::class)]
    public function onSheetOptionPicked(string $sheet, string $option, ?string $id = null): void
    {
        if ($id === null || ! str_starts_with($id, 'x-post-')) {
            return;
        }

        match ($sheet) {
            'audience' => $this->chooseFrom(self::AUDIENCES, $option, 'audience'),
            'reply' => $this->chooseFrom(self::REPLIERS, $option, 'reply'),
            default => null,
        };
    }

    /**
     * @param  list<array<string, string>>  $options
     */
    protected function chooseFrom(array $options, string $option, string $accessory): void
    {
        $chosen = collect($options)->firstWhere('id', $option);

        if ($chosen === null) {
            return;
        }

        if ($accessory === 'audience') {
            $this->audience = $chosen['label'];

            WysiwygEditor::setAccessory('audience', $chosen['label'], '');

            return;
        }

        $this->replies = $chosen['label'];

        WysiwygEditor::setAccessory('reply', 'Everyone can reply', $chosen['label']);
    }

    protected function chooseLocation(): void
    {
        $this->location = $this->location === '' ? 'San Francisco' : '';

        WysiwygEditor::setAccessory(
            'location',
            $this->location === '' ? 'Add location' : 'Location',
            $this->location,
        );
    }

    protected function openEditor(string $html, string $target): void
    {
        WysiwygEditor::open($html, [
            'placeholder' => "What's happening?",
            // No FORMATTING at all — but the media row X has, on one bar with
            // the ring, which is where the ring belongs.
            'toolbar' => ['image', 'camera', 'video', 'poll'],
            // Buttons the APP owns. The editor draws them and reports the tap;
            // what a GIF picker or a scheduler does is our business.
            'customTools' => [
                ['id' => 'gif', 'icon' => 'embed', 'label' => 'GIF'],
                ['id' => 'schedule', 'icon' => 'orderedList', 'label' => 'Schedule'],
            ],
            // The author, beside what they are writing.
            'avatar' => 'https://i.pravatar.cc/150?u=you',
            'history' => false,
            // Attachments belong to the post, not to a position in the prose.
            'mediaLayout' => 'strip',
            // Rows the APP owns. The editor draws them and reports the tap;
            // who may reply is our data, not the editor's business.
            'accessories' => [
                // Who may see it sits beside Post, the way X arranges it.
                [
                    'id' => 'audience',
                    'label' => $this->audience,
                    'placement' => 'header',
                    'sheet' => 'audience',
                ],
                ['id' => 'tag', 'label' => 'Tag people', 'icon' => 'people'],
                ['id' => 'location', 'label' => 'Add location', 'icon' => 'link', 'value' => $this->location],
                ['id' => 'reply', 'label' => 'Everyone can reply', 'icon' => 'globe', 'sheet' => 'reply'],
            ],
            // ────────────────────────────────────────────────────────────
            //  OUR sheets. The editor owns its own window, so one we drew
            //  would open behind it — we declare the options and it
            //  presents them, then tells us what was picked.
            // ────────────────────────────────────────────────────────────
            'sheets' => [
                'audience' => [
                    'title' => 'Choose audience',
                    'options' => array_map(fn (array $row) => $row + [
                        'selected' => $row['label'] === $this->audience,
                    ], self::AUDIENCES),
                ],
                'reply' => [
                    'title' => 'Who can reply?',
                    'options' => array_map(fn (array $row) => $row + [
                        'selected' => $row['label'] === $this->replies,
                    ], self::REPLIERS),
                ],
            ],
            'maxLength' => self::LIMIT,
            // Let the writer overrun and see by how much, rather than
            // swallowing the keystroke.
            'maxLengthMode' => 'soft',
            'countStyle' => 'ring',
            'saveStyle' => 'filled',
            // Backing out of a half-written post should offer to keep it, not
            // bin it — and the close control is a ✕, as in every composer.
            'cancelMode' => 'draft',
            'cancelStyle' => 'icon',
            'strings' => ['save' => $target === 'new' ? 'Post' : 'Save'],
            'id' => 'x-post-'.$target,
        ]);
    }

    public function render(): Element
    {
        return $this->view('x-timeline', [
            // Following is what you chose to see, and a fresh account chose
            // nothing — so it is empty until there is somebody to follow.
            'posts' => $this->tab === 'following'
                ? Post::query()->whereRaw('1 = 0')->get()
                : Post::query()->onSurface(self::SURFACE)->latest('created_at')->latest('id')->get(),
            'mine' => self::HANDLE,
            'tab' => $this->tab,
        ]);
    }
}
