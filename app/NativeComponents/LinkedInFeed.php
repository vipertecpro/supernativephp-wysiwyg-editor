<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Models\Post;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\SheetOptionPicked;
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

    /** Which demo a post belongs to; the three share a table. */
    public const SURFACE = 'linkedin';

    /** LinkedIn's allowance, an order of magnitude past a short post. */
    public const LIMIT = 3000;

    public string $audience = 'Anyone';

    /** When the post goes out, if the writer scheduled it. */
    public string $scheduledFor = '';

    /** Which "+" tile was tapped last, for the demo. */
    public string $lastTool = '';

    /**
     * Posts the reader has opened out with "…see more".
     *
     * A long post is clipped in the feed, the way LinkedIn clips it: the row
     * is a summary, and reading the whole thing is a decision. Kept per-post
     * so opening one does not open the rest.
     *
     * @var list<int>
     */
    public array $expanded = [];

    /** Show the rest of a clipped post. */
    public function expand(int $id): void
    {
        if (! in_array($id, $this->expanded, true)) {
            $this->expanded[] = $id;
        }
    }

    /** The post whose `···` menu is open, if any. */
    public ?int $actionsFor = null;

    /** True once Delete is tapped: the same sheet asks to confirm. */
    public bool $confirmingDelete = false;

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

    /** Re-open an existing post in the same editor, content intact. */
    public function edit(int $id): void
    {
        $this->actionsFor = null;
        $this->confirmingDelete = false;

        $post = Post::query()->onSurface(self::SURFACE)->find($id);

        if ($post && $post->author_handle === self::HANDLE) {
            // From the JSON, not the HTML: HTML cannot carry a local file
            // path, so a post whose photos never uploaded would come back
            // without them.
            $this->openEditor($post->body_json ?: $post->body_html, (string) $post->id);
        }
    }

    public function delete(int $id): void
    {
        // A real client would DELETE against its API here.
        Post::whereKey($id)->onSurface(self::SURFACE)->delete();

        $this->actionsFor = null;
        $this->confirmingDelete = false;
    }

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

    /**
     * Who may see a post, and what each choice means.
     *
     * @var list<array<string, string>>
     */
    protected const AUDIENCES = [
        ['id' => 'anyone', 'label' => 'Anyone', 'detail' => 'Anyone on or off the network', 'icon' => 'globe'],
        ['id' => 'connections', 'label' => 'Connections only', 'icon' => 'people'],
        ['id' => 'group', 'label' => 'Group', 'icon' => 'people'],
    ];

    /**
     * When to publish.
     *
     * A real client opens a date and a time picker here. The editor draws the
     * options it is given, so a demo offers the handful that matter and a real
     * app would report the tap and present its own picker instead.
     *
     * @var list<array<string, string>>
     */
    protected const SCHEDULES = [
        ['id' => 'now', 'label' => 'Post now', 'icon' => 'clock'],
        ['id' => 'morning', 'label' => 'Tomorrow, 9:00 AM', 'icon' => 'calendar'],
        ['id' => 'evening', 'label' => 'Tomorrow, 5:00 PM', 'icon' => 'calendar'],
        ['id' => 'week', 'label' => 'Next Monday, 9:00 AM', 'icon' => 'calendar'],
    ];

    public function compose(): void
    {
        $this->openEditor('', 'new');
    }

    protected function openEditor(string $content, string $target): void
    {
        WysiwygEditor::open($content, [
            'placeholder' => 'Share your thoughts...',
            // Two buttons, parked in the corner. LinkedIn's composer has no
            // formatting bar at all: a photo shortcut and a "+" onto
            // everything else.
            'toolbar' => ['image'],
            'toolbarAlign' => 'trailing',
            'history' => false,
            'customTools' => [
                ['id' => 'more', 'icon' => 'plus', 'label' => 'More', 'sheet' => 'compose'],
            ],
            'maxLength' => self::LIMIT,
            // A 3000-character allowance is not something to count down to,
            // and LinkedIn shows no counter at all until you are near it.
            'counts' => [],
            'saveStyle' => 'filled',
            'cancelStyle' => 'icon',
            'cancelMode' => 'discard',
            // Long-form: a picture belongs at a point in the argument, not
            // clipped to the end of it.
            'mediaLayout' => 'blocks',
            'spacing' => 'roomy',
            'avatar' => 'https://i.pravatar.cc/150?u=you',
            // Beside the audience picker, not beside the text — so the
            // writing runs the full width.
            'avatarPlacement' => 'header',
            'accessories' => [
                [
                    'id' => 'audience',
                    'label' => 'Anyone',
                    'value' => $this->audience,
                    'placement' => 'header',
                    'sheet' => 'audience',
                ],
                [
                    'id' => 'schedule',
                    'label' => 'Schedule',
                    'icon' => 'clock',
                    'placement' => 'header',
                    'style' => 'icon',
                    'sheet' => 'schedule',
                ],
            ],
            // ────────────────────────────────────────────────────────────
            //  These are OUR sheets. The editor owns its own window, so one
            //  we drew would open behind it — we declare the options and it
            //  presents them, then tells us what was picked.
            // ────────────────────────────────────────────────────────────
            'sheets' => [
                'compose' => [
                    'style' => 'grid',
                    'options' => [
                        ['id' => 'media', 'label' => 'Media', 'icon' => 'image'],
                        ['id' => 'event', 'label' => 'Event', 'icon' => 'calendar'],
                        ['id' => 'celebrate', 'label' => 'Celebrate', 'icon' => 'star'],
                        ['id' => 'job', 'label' => 'Job', 'icon' => 'briefcase'],
                        ['id' => 'poll', 'label' => 'Poll', 'icon' => 'poll'],
                        ['id' => 'document', 'label' => 'Document', 'icon' => 'document'],
                        ['id' => 'services', 'label' => 'Services', 'icon' => 'people'],
                    ],
                ],
                'audience' => [
                    'title' => 'Who can see your post?',
                    'options' => array_map(fn (array $row) => $row + [
                        'selected' => $row['label'] === $this->audience,
                    ], self::AUDIENCES),
                ],
                'schedule' => [
                    'title' => 'Schedule',
                    'options' => self::SCHEDULES,
                ],
            ],
            'strings' => ['save' => $target === 'new' ? 'Post' : 'Save'],
            'id' => 'li-post-'.$target,
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
        if ($id === null || ! str_starts_with($id, 'li-post-')) {
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
        if ($id === null || ! str_starts_with($id, 'li-post-')) {
            return;
        }

        match ($sheet) {
            'audience' => $this->chooseAudience($option),
            'schedule' => $this->chooseSchedule($option),
            'compose' => $this->composeOption($option),
            default => null,
        };
    }

    protected function chooseAudience(string $option): void
    {
        $chosen = collect(self::AUDIENCES)->firstWhere('id', $option);

        if ($chosen === null) {
            return;
        }

        $this->audience = $chosen['label'];

        WysiwygEditor::setAccessory('audience', $chosen['label'], $chosen['label']);
    }

    protected function chooseSchedule(string $option): void
    {
        $chosen = collect(self::SCHEDULES)->firstWhere('id', $option);

        // A real client would keep the chosen time and send it with the post.
        // The clock is an icon, so there is no label to write back to — the
        // choice shows up when the post is published.
        $this->scheduledFor = $chosen === null || $option === 'now' ? '' : $chosen['label'];
    }

    /**
     * A tile in the "+" grid.
     *
     * Media and Poll are things the EDITOR can do, so they are asked for as
     * tools. The rest are LinkedIn features an app would build itself — the
     * editor drew the tile and told us, which is as far as its job goes.
     */
    protected function composeOption(string $option): void
    {
        match ($option) {
            'media' => $this->onMediaRequested('image'),
            'document' => $this->onMediaRequested('file'),
            'poll' => WysiwygEditor::insertTool('poll'),
            default => $this->lastTool = $option,
        };
    }

    #[On(ContentSaved::class)]
    public function onPosted(string $html, string $text, string $json = '', ?string $id = null): void
    {
        if ($id === null || ! str_starts_with($id, 'li-post-') || trim($text) === '') {
            return;
        }

        $target = substr($id, strlen('li-post-'));

        // ────────────────────────────────────────────────────────────────
        //  REPLACE THIS WITH YOUR API.
        // ────────────────────────────────────────────────────────────────
        //
        // Written to SQLite ON THE DEVICE because this demo has no server.
        // Send `json` too if the post should ever be EDITABLE again — HTML
        // cannot carry a local file path or a poll's option ids.
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

        // An edit only ever touches your own row.
        Post::whereKey((int) $target)
            ->onSurface(self::SURFACE)
            ->update(['body_html' => $html, 'body_text' => $text, 'body_json' => $json]);
    }

    public function preview(string $kind, string $source, string $caption = ''): void
    {
        WysiwygEditor::preview($kind, $source, $caption);
    }

    public function render(): Element
    {
        return $this->view('linkedin-feed', [
            'posts' => Post::query()->onSurface(self::SURFACE)->latest('created_at')->latest('id')->get(),
            'audience' => $this->audience,
            'mine' => self::HANDLE,
            'expanded' => $this->expanded,
            'actionsFor' => $this->actionsFor,
            'confirmingDelete' => $this->confirmingDelete,
        ]);
    }
}
