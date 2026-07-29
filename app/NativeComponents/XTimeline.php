<?php

namespace App\NativeComponents;

use App\Models\Post;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Demo 1 — a short-post composer in the shape of X.
 *
 * The point of this screen is that the WHOLE composer is the plugin. There is
 * no formatting bar, no headings, no lists: `toolbar => []` turns the bar off
 * entirely, `history => false` drops undo/redo, and what is left is a
 * plain-text field with a photo button, a countdown ring and a filled Post
 * pill — which is exactly what X's composer is.
 *
 * The layout and interactions are modelled on X; the branding is our own,
 * because copying the interaction design is the point and copying a logo is
 * not.
 */
class XTimeline extends NativeComponent
{
    /** The account doing the posting, as a real client would know it. */
    public const AUTHOR = 'You';

    public const HANDLE = '@you';

    /** 280, the limit the ring counts toward. */
    public const LIMIT = 280;

    public function mount(): void
    {
        // A demo that opens on an empty feed shows nothing about the editor.
        if (Post::count() === 0) {
            Post::factory()->seeded()->count(4)->create();
        }
    }

    /**
     * Open the composer.
     *
     * Everything that makes this look like X rather than a word processor is
     * in these options — none of it is a special case inside the plugin.
     */
    public function compose(): void
    {
        WysiwygEditor::open('', [
            'title' => '',
            'placeholder' => "What's happening?",
            // No FORMATTING at all — but the media row X has, on one bar
            // with the ring, which is where the ring belongs.
            'toolbar' => ['image', 'video', 'poll'],
            'history' => false,
            'maxLength' => self::LIMIT,
            // Let the writer overrun and see by how much, rather than
            // swallowing the keystroke.
            'maxLengthMode' => 'soft',
            'countStyle' => 'ring',
            'saveStyle' => 'filled',
            'strings' => ['save' => 'Post'],
            'id' => 'x-post',
        ]);
    }

    #[On(ContentSaved::class)]
    public function onPosted(string $html, string $text, string $json = '', ?string $id = null): void
    {
        if ($id !== 'x-post' || trim($text) === '') {
            return;
        }

        Post::create([
            'author_name' => self::AUTHOR,
            'author_handle' => self::HANDLE,
            'body_html' => $html,
            'body_text' => $text,
        ]);
    }

    public function render(): Element
    {
        return $this->view('x-timeline', [
            'posts' => Post::query()->latest('created_at')->latest('id')->get(),
        ]);
    }
}
