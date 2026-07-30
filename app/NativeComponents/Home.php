<?php

namespace App\NativeComponents;

use Native\Mobile\Edge\NativeComponent;

/**
 * Home — a gallery of WysiwygEditor examples. Each card opens the SAME native
 * plugin configured differently (full notes editor, locked-down comment box,
 * composer with raw HTML output, branded theme), showing how one editor
 * covers many use cases.
 */
class Home extends NativeComponent
{
    public function openX(): void
    {
        $this->navigate('/x');
    }

    public function openLinkedIn(): void
    {
        $this->navigate('/linkedin');
    }

    public function openFacebook(): void
    {
        $this->navigate('/facebook');
    }

    public function openNotion(): void
    {
        $this->navigate('/notion');
    }

    public function openNotes(): void
    {
        $this->navigate('/notes');
    }

    public function openComments(): void
    {
        $this->navigate('/comments');
    }

    public function openComposer(): void
    {
        $this->navigate('/composer');
    }

    public function openThemed(): void
    {
        $this->navigate('/themed');
    }
}
