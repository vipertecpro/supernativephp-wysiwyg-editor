<?php

namespace App\NativeComponents;

use App\Concerns\InteractsWithWysiwygEditor;
use Native\Mobile\Edge\NativeComponent;

/**
 * Composer example — the full toolbar, plus a look under the hood.
 *
 * Write with every tool enabled, then flip the preview between the rendered
 * blocks and the RAW normalised HTML the plugin actually returned — the
 * pitch of the plugin in one screen: clean HTML in, clean HTML out.
 */
class Composer extends NativeComponent
{
    use InteractsWithWysiwygEditor;

    /** When true the preview shows the raw HTML instead of rendered blocks. */
    public bool $showHtml = false;

    protected function editorOptions(): array
    {
        return [
            'title' => 'Write post',
            'placeholder' => 'Write your post — try headings, lists, colors…',
        ];
    }

    public function toggleHtml(): void
    {
        $this->showHtml = ! $this->showHtml;
    }
}
