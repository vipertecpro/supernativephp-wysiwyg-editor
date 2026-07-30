<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Models\Post;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\SheetOptionPicked;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Demo 3 — rich, social posting in the shape of Facebook.
 *
 * X wanted a plain field with a countdown. LinkedIn wanted the writing tools
 * and a directory of people. This one wants the thing neither of those has:
 * a few words written ON a colour, held large and centred, so a short post
 * stops being a paragraph and becomes a card.
 *
 * That is a property of the DOCUMENT rather than of any run inside it, so the
 * editor carries it through the JSON and the markup both — and the feed below
 * renders it back the same way.
 */
class FacebookFeed extends NativeComponent
{
    use InsertsMediaWithMarketplacePlugins;

    public const AUTHOR = 'You';

    public const HANDLE = '@you';

    /** Who a post goes out to. */
    public string $audience = 'Friends';

    /** The post whose `···` menu is open, if any. */
    public ?int $actionsFor = null;

    public bool $confirmingDelete = false;

    /**
     * The colours a short post can be written on.
     *
     * ────────────────────────────────────────────────────────────────────
     *  These are OURS, not the editor's.
     * ────────────────────────────────────────────────────────────────────
     *
     * The editor has no palette of its own and should not grow one — which
     * colours a brand offers is a brand decision. It draws the swatches, holds
     * the post on the chosen one, and hands the id back with everything else.
     *
     * @var array<string, array<string, string>>
     */
    public const BACKGROUNDS = [
        'ocean' => ['from' => '#2563EB', 'to' => '#0EA5E9'],
        'sunset' => ['from' => '#F97316', 'to' => '#DB2777'],
        'forest' => ['from' => '#059669', 'to' => '#065F46'],
        'ink' => ['from' => '#1F2937', 'to' => '#4B5563'],
        'blush' => ['from' => '#FBCFE8', 'to' => '#FDE68A', 'textColor' => '#1F2937'],
    ];

    /**
     * Who may see a post.
     *
     * @var list<array<string, string>>
     */
    protected const AUDIENCES = [
        ['id' => 'public', 'label' => 'Public', 'detail' => 'Anyone on or off the app', 'icon' => 'globe'],
        ['id' => 'friends', 'label' => 'Friends', 'detail' => 'Your friends', 'icon' => 'people'],
        ['id' => 'only-me', 'label' => 'Only me', 'icon' => 'people'],
    ];

    public function compose(): void
    {
        $this->openEditor('', 'new');
    }

    public function edit(int $id): void
    {
        $this->actionsFor = null;
        $this->confirmingDelete = false;

        $post = Post::find($id);

        if ($post && $post->author_handle === self::HANDLE) {
            // From the JSON: HTML cannot carry a local file path, and a post
            // re-opened from it would come back without its photos.
            $this->openEditor($post->body_json ?: $post->body_html, (string) $post->id);
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
        Post::whereKey($id)->where('author_handle', self::HANDLE)->delete();

        $this->actionsFor = null;
        $this->confirmingDelete = false;
    }

    protected function openEditor(string $content, string $target): void
    {
        WysiwygEditor::open($content, [
            'placeholder' => "What's on your mind?",
            'title' => 'Create post',
            // Facebook's composer is not a writing tool — no bold, no lists.
            // What it has instead is the colours.
            'toolbar' => ['image', 'camera', 'video'],
            'history' => false,
            'backgrounds' => self::BACKGROUNDS,
            'avatar' => 'https://i.pravatar.cc/150?u=you',
            // In the header, not beside the text: the card is the writing
            // surface, and an avatar sitting on it makes the colour look like
            // a backdrop behind a person rather than the post itself.
            'avatarPlacement' => 'header',
            'accessories' => [
                [
                    'id' => 'audience',
                    'label' => $this->audience,
                    'placement' => 'header',
                    'sheet' => 'audience',
                ],
                ['id' => 'tag', 'label' => 'Tag people', 'icon' => 'people'],
                ['id' => 'feeling', 'label' => 'Feeling/activity', 'icon' => 'star'],
                ['id' => 'checkin', 'label' => 'Check in', 'icon' => 'link'],
            ],
            'sheets' => [
                'audience' => [
                    'title' => 'Who can see your post?',
                    'options' => array_map(fn (array $row) => $row + [
                        'selected' => $row['label'] === $this->audience,
                    ], self::AUDIENCES),
                ],
            ],
            'mediaLayout' => 'blocks',
            'saveStyle' => 'filled',
            'cancelStyle' => 'icon',
            'cancelMode' => 'discard',
            'strings' => ['save' => $target === 'new' ? 'Post' : 'Save'],
            'id' => 'fb-post-'.$target,
        ]);
    }

    #[On(SheetOptionPicked::class)]
    public function onSheetOptionPicked(string $sheet, string $option, ?string $id = null): void
    {
        if ($id === null || ! str_starts_with($id, 'fb-post-') || $sheet !== 'audience') {
            return;
        }

        $chosen = collect(self::AUDIENCES)->firstWhere('id', $option);

        if ($chosen === null) {
            return;
        }

        $this->audience = $chosen['label'];

        WysiwygEditor::setAccessory('audience', $chosen['label'], '');
    }

    #[On(ContentSaved::class)]
    public function onPosted(string $html, string $text, string $json = '', ?string $id = null): void
    {
        if ($id === null || ! str_starts_with($id, 'fb-post-') || trim($text) === '') {
            return;
        }

        $target = substr($id, strlen('fb-post-'));

        // ────────────────────────────────────────────────────────────────
        //  REPLACE THIS WITH YOUR API.
        // ────────────────────────────────────────────────────────────────
        //
        // Written to SQLite ON THE DEVICE because this demo has no server.
        // Send `json` too if the post should ever be EDITABLE again — it is
        // the only form that carries the background and the local file paths.
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

        Post::whereKey((int) $target)
            ->where('author_handle', self::HANDLE)
            ->update(['body_html' => $html, 'body_text' => $text, 'body_json' => $json]);
    }

    public function preview(string $kind, string $source, string $caption = ''): void
    {
        WysiwygEditor::preview($kind, $source, $caption);
    }

    public function render(): Element
    {
        return $this->view('facebook-feed', [
            'posts' => Post::query()->latest('created_at')->latest('id')->get(),
            'mine' => self::HANDLE,
            'audience' => $this->audience,
            'actionsFor' => $this->actionsFor,
            'confirmingDelete' => $this->confirmingDelete,
            'backgrounds' => self::BACKGROUNDS,
        ]);
    }
}
