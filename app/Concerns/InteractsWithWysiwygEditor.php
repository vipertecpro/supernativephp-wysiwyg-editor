<?php

namespace App\Concerns;

use App\Support\RichText;
use Native\Mobile\Attributes\On;
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Events\EditCancelled;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Drop-in editor behaviour for a demo screen.
 *
 * A screen opens the native WYSIWYG editor with ITS OWN config (via
 * {@see editorOptions()}) and receives normalised HTML + plain text back. All
 * the plumbing — opening, handling the result, re-editing — lives here so each
 * example screen is just a preview + a config.
 */
trait InteractsWithWysiwygEditor
{
    /** The current content as the plugin's normalised HTML ('' when empty). */
    public string $html = '';

    /** The matching plain-text rendition delivered by the plugin. */
    public string $text = '';

    /**
     * The per-screen editor configuration handed to {@see WysiwygEditor::open()}.
     * Return `[]` for the full toolbar, or lock it down, e.g.:
     *
     *   return ['preset' => 'comment', 'maxLength' => 500];
     *
     * @return array<string, mixed>
     */
    abstract protected function editorOptions(): array;

    /** Open the native editor on the current content. */
    public function startEdit(): void
    {
        WysiwygEditor::open($this->html, $this->editorOptions());
    }

    #[On(ContentSaved::class)]
    public function onContentSaved(string $html, string $text, ?string $id = null): void
    {
        $this->html = $html;
        $this->text = $text;

        $this->contentSaved($id);
    }

    #[On(EditCancelled::class)]
    public function onEditCancelled(?string $id = null): void
    {
        // User backed out — content unchanged. Screens may override.
    }

    /** Hook for screens that persist the result (DB, API, …). */
    protected function contentSaved(?string $id): void
    {
        //
    }

    /**
     * The current content as displayable blocks for the preview partial.
     *
     * @return list<array{type: string, text?: string, items?: list<string>}>
     */
    public function previewBlocks(): array
    {
        return RichText::blocks($this->html);
    }
}
