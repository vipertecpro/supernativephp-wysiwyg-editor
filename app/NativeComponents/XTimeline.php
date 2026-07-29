<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Models\Post;
use App\Support\PostContent;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
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

    /** 280, the limit the ring counts toward. */
    public const LIMIT = 280;

    /** The post whose actions sheet is open, if any. */
    public ?int $actionsFor = null;

    /** True once Delete is tapped: the same sheet asks to confirm. */
    public bool $confirmingDelete = false;

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

        $post = Post::find($id);

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
        Post::whereKey($id)->where('author_handle', self::HANDLE)->delete();

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

        if ($target === 'new') {
            Post::create([
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
            ->where('author_handle', self::HANDLE)
            ->update(['body_html' => $html, 'body_text' => $text, 'body_json' => $json]);
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

    protected function openEditor(string $html, string $target): void
    {
        WysiwygEditor::open($html, [
            'placeholder' => "What's happening?",
            // No FORMATTING at all — but the media row X has, on one bar with
            // the ring, which is where the ring belongs.
            'toolbar' => ['image', 'video', 'poll'],
            'history' => false,
            // Attachments belong to the post, not to a position in the prose.
            'mediaLayout' => 'strip',
            'maxLength' => self::LIMIT,
            // Let the writer overrun and see by how much, rather than
            // swallowing the keystroke.
            'maxLengthMode' => 'soft',
            'countStyle' => 'ring',
            'saveStyle' => 'filled',
            'strings' => ['save' => $target === 'new' ? 'Post' : 'Save'],
            'id' => 'x-post-'.$target,
        ]);
    }

    public function render(): Element
    {
        return $this->view('x-timeline', [
            'posts' => Post::query()->latest('created_at')->latest('id')->get(),
            'mine' => self::HANDLE,
        ]);
    }
}
