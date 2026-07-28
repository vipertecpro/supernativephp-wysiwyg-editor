<?php

namespace App\NativeComponents;

use App\Concerns\InteractsWithWysiwygEditor;
use Native\Mobile\Edge\NativeComponent;

/**
 * Themed example — the editor recoloured to match a host app's brand.
 *
 * Same plugin, `note` preset, but every theme key overridden so the editor
 * screen looks like it was designed for this app rather than shipped by a
 * third-party package.
 */
class ThemedEditor extends NativeComponent
{
    use InteractsWithWysiwygEditor;

    protected function editorOptions(): array
    {
        return [
            'preset' => 'note',
            'title' => 'Branded editor',
            'placeholder' => 'This editor wears the app\'s colors…',
            'theme' => [
                'background' => '#121417',
                'text' => '#FFFFFF',
                'accent' => '#F97316',   // Save button
                'highlight' => '#22C55E', // active toolbar states
            ],
        ];
    }
}
