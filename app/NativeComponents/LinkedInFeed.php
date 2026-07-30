<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Models\Post;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\AccessoryTapped;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\SuggestionRequested;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Demo 2 — long-form posting in the shape of LinkedIn.
 *
 * The same editor as demo 1, configured almost oppositely. X wanted a
 * plain-text field with a countdown; this wants the writing tools — bold,
 * italic, lists — a 3000-character allowance counted quietly, and media that
 * sits IN the prose rather than clipped to the post.
 *
 * The part worth watching is @ and #. The editor spots the trigger and
 * collects the query; who is mentionable is our business, so we answer from
 * our own data. It has no directory of people and should not grow one.
 */
class LinkedInFeed extends NativeComponent
{
    use InsertsMediaWithMarketplacePlugins;

    public const AUTHOR = 'You';

    public const HANDLE = '@you';

    /** LinkedIn's allowance, an order of magnitude past a short post. */
    public const LIMIT = 3000;

    public string $audience = 'Anyone';

    /**
     * Who this account can mention.
     *
     * ────────────────────────────────────────────────────────────────────
     *  REPLACE THIS WITH YOUR API.
     * ────────────────────────────────────────────────────────────────────
     *
     * A real client asks its server, scoped to the signed-in user — their
     * connections, their colleagues, whoever they are allowed to see. The
     * editor deliberately has no directory of its own, because who is
     * mentionable depends entirely on who is asking.
     *
     * @var list<array<string, string>>
     */
    protected const DIRECTORY = [
        ['id' => 'u1', 'label' => 'Ada Lovelace', 'detail' => 'Mathematician · Analytical Engine'],
        ['id' => 'u2', 'label' => 'Grace Hopper', 'detail' => 'Rear Admiral · Compiler pioneer'],
        ['id' => 'u3', 'label' => 'Alan Turing', 'detail' => 'Computer scientist · Bletchley Park'],
        ['id' => 'u4', 'label' => 'Barbara Liskov', 'detail' => 'Professor · MIT'],
        ['id' => 'u5', 'label' => 'Katherine Johnson', 'detail' => 'Mathematician · NASA'],
    ];

    /** @var list<array<string, string>> */
    protected const TAGS = [
        ['id' => 't1', 'label' => 'nativephp', 'detail' => '12,480 followers'],
        ['id' => 't2', 'label' => 'laravel', 'detail' => '318,902 followers'],
        ['id' => 't3', 'label' => 'mobile', 'detail' => '1,204,551 followers'],
        ['id' => 't4', 'label' => 'opensource', 'detail' => '892,110 followers'],
    ];

    public function compose(): void
    {
        WysiwygEditor::open('', [
            'placeholder' => 'What do you want to talk about?',
            // The writing tools, which the short-post composer had none of.
            'toolbar' => ['bold', 'italic', 'bulletList', 'orderedList', 'link', 'image', 'video', 'file', 'poll'],
            'maxLength' => self::LIMIT,
            // Three thousand characters is not something to count down to; a
            // quiet readout is right and a ring would be absurd.
            'counts' => ['characters', 'words', 'readingTime'],
            'saveStyle' => 'filled',
            'cancelStyle' => 'icon',
            'cancelMode' => 'discard',
            // Long-form: a picture belongs at a point in the argument, not
            // clipped to the end of it.
            'mediaLayout' => 'blocks',
            'spacing' => 'roomy',
            'avatar' => 'https://i.pravatar.cc/150?u=you',
            'accessories' => [
                ['id' => 'audience', 'label' => 'Post to', 'value' => $this->audience],
            ],
            'strings' => ['save' => 'Post'],
            'id' => 'li-post',
        ]);
    }

    /**
     * The user typed @ or # and is writing after it.
     *
     * Fires on every keystroke, so this answers from memory and caps the list.
     * A real client would debounce and hit its API.
     */
    #[On(SuggestionRequested::class)]
    public function onSuggestionRequested(string $kind, string $trigger, string $query = '', ?string $id = null): void
    {
        if ($id !== 'li-post') {
            return;
        }

        $source = $kind === 'hashtag' ? self::TAGS : self::DIRECTORY;
        $needle = mb_strtolower(trim($query));

        $matches = array_values(array_filter(
            $source,
            fn (array $row) => $needle === '' || str_contains(mb_strtolower($row['label']), $needle),
        ));

        WysiwygEditor::suggestions($query, array_slice($matches, 0, 5));
    }

    #[On(AccessoryTapped::class)]
    public function onAccessoryTapped(string $accessory): void
    {
        if ($accessory !== 'audience') {
            return;
        }

        $order = ['Anyone', 'Connections only', 'Anyone + Twitter'];
        $this->audience = $order[(array_search($this->audience, $order, true) + 1) % count($order)];

        WysiwygEditor::setAccessory('audience', 'Post to', $this->audience);
    }

    #[On(ContentSaved::class)]
    public function onPosted(string $html, string $text, string $json = '', ?string $id = null): void
    {
        if ($id !== 'li-post' || trim($text) === '') {
            return;
        }

        Post::create([
            'author_name' => self::AUTHOR,
            'author_handle' => self::HANDLE,
            'body_html' => $html,
            'body_text' => $text,
            'body_json' => $json,
        ]);
    }

    public function preview(string $kind, string $source, string $caption = ''): void
    {
        WysiwygEditor::preview($kind, $source, $caption);
    }

    public function render(): Element
    {
        return $this->view('linkedin-feed', [
            'posts' => Post::query()->latest('created_at')->latest('id')->get(),
            'audience' => $this->audience,
            'mine' => self::HANDLE,
        ]);
    }
}
