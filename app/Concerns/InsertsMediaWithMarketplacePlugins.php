<?php

namespace App\Concerns;

use Native\Mobile\Attributes\On;
use Native\Mobile\Events\Gallery\MediaSelected;
use Native\Mobile\Facades\Camera;
use Vipertecpro\ImageCropper\Events\ImageCropped;
use Vipertecpro\ImageCropper\Facades\ImageCropper;
use Vipertecpro\WysiwygEditor\Events\MediaRequested;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

/**
 * Media insertion, assembled from EXISTING marketplace plugins.
 *
 * The editor ships no picker and no uploader on purpose. It asks
 * ({@see MediaRequested}) and waits. Here that request is served by chaining
 * three plugins that already exist:
 *
 *   WysiwygEditor  →  nativephp/mobile-camera  →  vipertecpro/image-cropper
 *        ↑                                                    │
 *        └────────────── insertMedia(localPath) ←──────────────┘
 *
 * Nothing about this flow lives in the editor, which is what lets each app
 * substitute its own picker, cropper, permissions and upload stack.
 */
trait InsertsMediaWithMarketplacePlugins
{
    /** The kind the user asked for, remembered across the picker round-trip. */
    public string $pendingMediaKind = '';

    /** Correlates a block with its upload so the editor can be told the outcome. */
    public string $pendingUploadId = '';

    #[On(MediaRequested::class)]
    public function onMediaRequested(string $kind): void
    {
        $this->pendingMediaKind = $kind;

        // Files aren't a camera concern — a real app would open its own picker.
        if ($kind === 'file') {
            return;
        }

        Camera::pickImages('images', false);
    }

    #[On(MediaSelected::class)]
    public function onMediaSelected(bool $success, array $files, int $count): void
    {
        if (! $success || $files === [] || $this->pendingMediaKind === '') {
            return;
        }

        $first = $files[0];
        $path = is_array($first) ? ($first['path'] ?? null) : $first;

        if (! is_string($path)) {
            return;
        }

        // Images get a crop pass first; video goes straight in.
        if ($this->pendingMediaKind === 'image') {
            ImageCropper::open($path, ['preset' => 'landscape']);

            return;
        }

        $this->insertPickedMedia($path);
    }

    #[On(ImageCropped::class)]
    public function onImageCropped(string $path): void
    {
        $this->insertPickedMedia($path);
    }

    /**
     * Hand the local file to the editor. It renders immediately from
     * `localPath`, so the user sees the media before any upload starts.
     */
    protected function insertPickedMedia(string $path): void
    {
        $this->pendingUploadId = 'up-'.substr(md5($path.microtime()), 0, 8);

        WysiwygEditor::insertMedia($this->pendingMediaKind, [
            'localPath' => $path,
            'alt' => basename($path),
            'uploadId' => $this->pendingUploadId,
        ]);

        $this->pendingMediaKind = '';

        // A real app would upload here — with its own auth and endpoint, or
        // nativephp/mobile-background-tasks — then report the outcome:
        //
        //   WysiwygEditor::uploadProgress($this->pendingUploadId, 0.4);
        //   WysiwygEditor::uploadCompleted($this->pendingUploadId, $cdnUrl);
        //   WysiwygEditor::uploadFailed($this->pendingUploadId, $e->getMessage());
    }
}
