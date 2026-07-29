<?php

namespace App\NativeComponents;

use App\Concerns\InsertsMediaWithMarketplacePlugins;
use App\Concerns\InteractsWithWysiwygEditor;
use Native\Mobile\Attributes\On;
use Native\Mobile\Edge\NativeComponent;
use Vipertecpro\WysiwygEditor\Events\ContentChanged;

/**
 * Composer example — the full toolbar, plus a look under the hood.
 *
 * Write with every tool enabled, then flip the preview between the rendered
 * blocks and the RAW normalised HTML the plugin actually returned — the
 * pitch of the plugin in one screen: clean HTML in, clean HTML out.
 */
class Composer extends NativeComponent
{
    use InsertsMediaWithMarketplacePlugins;
    use InteractsWithWysiwygEditor;

    /** When true the preview shows the raw HTML instead of rendered blocks. */
    public bool $showHtml = false;

    /** How many times the editor has told us the document settled. */
    public int $autosaves = 0;

    protected function editorOptions(): array
    {
        return [
            'title' => 'Write post',
            'placeholder' => 'Write your post — try headings, lists, colors…',
            // No `theme` here on purpose: the editor should adopt the app's
            // own NativeUI palette automatically.
            'counts' => ['characters', 'words', 'readingTime'],
            // Bottom sheets instead of a bar that scrolls off the screen.
            'menu' => 'sheet',
            // Every user-visible string is overridable — here the save action
            // is renamed to match what this screen actually does.
            'strings' => ['save' => 'Publish'],
            'changeDebounce' => 1200,
            'validation' => ['minWords' => 5],
        ];
    }

    /** Auto-save seam: the editor tells us it settled; we decide what that means. */
    #[On(ContentChanged::class)]
    public function onContentChanged(string $html, string $json): void
    {
        $this->html = $html;
        $this->autosaves++;
    }

    public function toggleHtml(): void
    {
        $this->showHtml = ! $this->showHtml;
    }
}
