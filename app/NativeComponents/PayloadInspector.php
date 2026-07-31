<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Support\MediaOptimization;
use App\Support\MediaOptimizer;
use App\Support\PayloadLog;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentChanged;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\EditCancelled;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Demo 0 — what your server actually receives.
 *
 * Every other screen here shows the editor being a product. This one shows it
 * being an API, because that is the thing you have to understand before you
 * can build against it: what arrives, in what shape, and what you are expected
 * to do with the parts.
 *
 * Write something, put a picture in it, and watch three things update:
 *
 *   1. THE THREE FORMATS. `ContentSaved` hands you `$html`, `$text` and
 *      `$json`, and which you store is a real decision rather than a detail.
 *      Their byte sizes are on screen because a document with four photos in
 *      it is not the same shape of request as one without.
 *
 *   2. THE FILES, listed separately. Media is NOT embedded in the HTML — the
 *      markup references a path, and the bytes are yours to send wherever you
 *      send bytes. `WysiwygEditor::attachments($json)` is the split, and this
 *      screen shows exactly what it returns.
 *
 *   3. THE CONVERSATION. Every event the editor fired and every call this app
 *      made back, in order, newest first — including failures. A handler that
 *      throws on a phone leaves no stack trace anywhere a person can see, so
 *      failures are caught and recorded rather than lost.
 *
 * The media pipeline is instrumented too: pick a photo and the log shows what
 * the optimizer did to it before the editor ever saw it. That is where
 * compression, EXIF stripping and format conversion belong — see
 * {@see MediaOptimizer}.
 */
class PayloadInspector extends NativeComponent
{
    use InsertsMediaWithMarketplacePlugins;

    /** Which pane is showing: html | text | json | files | log. */
    public string $pane = 'html';

    public string $html = '';

    public string $text = '';

    public string $json = '';

    /** How many times the editor has reported the document settled. */
    public int $changes = 0;

    /** Set when the document was committed rather than merely edited. */
    public bool $saved = false;

    public function show(string $pane): void
    {
        if (in_array($pane, ['html', 'text', 'json', 'files', 'log'], true)) {
            $this->pane = $pane;
        }
    }

    public function compose(): void
    {
        WysiwygEditor::open($this->json ?: $this->html, [
            'title' => 'Write something',
            'placeholder' => 'Write a line, then add a picture…',
            'toolbar' => [
                'bold', 'italic', 'underline', 'strikethrough', 'code',
                'h1', 'h2', 'bulletList', 'orderedList', 'checklist',
                'blockquote', 'link', 'image', 'video', 'file', 'table', 'divider',
            ],
            // Reported while typing, so the panes move as you write rather
            // than only when you commit.
            'changeDebounce' => 600,
            'counts' => ['characters', 'words'],
            'strings' => ['save' => 'Send'],
            'id' => 'payload',
        ]);

        PayloadLog::call('WysiwygEditor::open', 'id=payload');
    }

    public function clearLog(): void
    {
        PayloadLog::clear();
    }

    /**
     * Typing settled.
     *
     * Wrapped because this is the honest shape: an exception thrown in here
     * disappears on a device, and a screen about debugging should not model
     * the thing that makes debugging hard.
     */
    #[On(ContentChanged::class)]
    public function onChanged(string $html, string $text, string $json = '', ?string $id = null): void
    {
        try {
            $this->capture($html, $text, $json);
            $this->changes++;

            PayloadLog::event('ContentChanged', 'html '.strlen($html).' B · json '.strlen($json).' B');
        } catch (\Throwable $e) {
            PayloadLog::failure('ContentChanged', $e->getMessage());
        }
    }

    #[On(ContentSaved::class)]
    public function onSaved(string $html, string $text, string $json = '', ?string $id = null): void
    {
        try {
            $this->capture($html, $text, $json);
            $this->saved = true;

            // ────────────────────────────────────────────────────────────
            //  THIS IS THE REQUEST YOU WOULD SEND.
            // ────────────────────────────────────────────────────────────
            //
            //   Http::withToken($user->apiToken)->post('/v1/documents', [
            //       'html' => $html,          // to render
            //       'text' => $text,          // to index and search
            //       'json' => $json,          // to re-open the editor with
            //   ]);
            //
            // …and the files, separately, because they are not in any of the
            // three above — only referenced by them:
            //
            //   foreach (WysiwygEditor::attachments($json) as $file) {
            //       Http::attach('file', fopen($file['path'], 'r'))->post('/v1/media');
            //   }
            PayloadLog::event('ContentSaved', 'html '.strlen($html).' B · '.count($this->files()).' file(s)');
        } catch (\Throwable $e) {
            PayloadLog::failure('ContentSaved', $e->getMessage());
        }
    }

    #[On(EditCancelled::class)]
    public function onCancelled(?string $id = null): void
    {
        PayloadLog::event('EditCancelled', 'nothing was committed');
    }

    protected function capture(string $html, string $text, string $json): void
    {
        $this->html = $html;
        $this->text = $text;
        $this->json = $json;
    }

    /**
     * The files, split out of the document.
     *
     * This is the whole answer to "how do I upload the pictures separately" —
     * one call, and each row is a file on disk you can POST.
     *
     * @return list<array<string, string>>
     */
    public function files(): array
    {
        if ($this->json === '') {
            return [];
        }

        return array_map(function (array $file): array {
            $path = $file['path'] !== '' ? $file['path'] : $file['url'];
            $exists = $path !== '' && is_readable($path);

            return [
                'kind' => $file['kind'],
                'name' => $path === '' ? '—' : basename($path),
                'size' => $exists ? MediaOptimization::bytes((int) filesize($path)) : 'not on disk',
                'state' => $file['url'] !== '' ? 'uploaded' : 'pending',
                'uploadId' => $file['uploadId'] !== '' ? $file['uploadId'] : '—',
            ];
        }, WysiwygEditor::attachments($this->json));
    }

    public function render(): Element
    {
        return $this->view('payload-inspector', [
            'pane' => $this->pane,
            'html' => $this->html,
            'text' => $this->text,
            'json' => $this->json,
            'files' => $this->files(),
            'log' => PayloadLog::entries(),
            'changes' => $this->changes,
            'saved' => $this->saved,
            'sizes' => [
                'html' => MediaOptimization::bytes(strlen($this->html)),
                'text' => MediaOptimization::bytes(strlen($this->text)),
                'json' => MediaOptimization::bytes(strlen($this->json)),
            ],
            'optimization' => $this->lastOptimization?->summary() ?? '',
        ]);
    }
}
