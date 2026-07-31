<?php

namespace App\Concerns;

use App\Support\MediaOptimization;
use App\Support\MediaOptimizer;
use App\Support\MediaStore;
use App\Support\PayloadLog;
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

    /** What the optimizer did to the last file, for a screen that shows it. */
    public ?MediaOptimization $lastOptimization = null;

    /**
     * Paths still waiting to go in, when the user picked several at once.
     *
     * @var list<string>
     */
    public array $pendingMediaQueue = [];

    #[On(MediaRequested::class)]
    public function onMediaRequested(string $kind): void
    {
        $this->pendingMediaKind = $kind;
        $this->pendingMediaQueue = [];

        match ($kind) {
            // Photos are the one kind worth picking several of at a time.
            'image' => Camera::pickImages('images', true, \Vipertecpro\WysiwygEditor\WysiwygEditor::DEFAULT_MAX_MEDIA),
            // A photo you TAKE is a different screen from one you pick.
            'camera' => Camera::getPhoto(),
            'video' => Camera::pickImages('videos', false),
            // No document picker exists in the marketplace yet, so `all` is
            // the closest thing. A real app would open its own — which is
            // exactly the point of the editor not shipping one.
            default => Camera::pickImages('all', false),
        };
    }

    #[On(MediaSelected::class)]
    public function onMediaSelected(bool $success, array $files, int $count): void
    {
        if (! $success || $files === [] || $this->pendingMediaKind === '') {
            return;
        }

        $paths = [];

        foreach ($files as $file) {
            $path = is_array($file) ? ($file['path'] ?? null) : $file;

            if (is_string($path) && $path !== '') {
                $paths[] = $path;
            }
        }

        if ($paths === []) {
            return;
        }

        // The queue is what makes picking ten photos work: each one has to
        // finish its crop round-trip before the next can start, because the
        // cropper is a single screen and the editor inserts at the caret.
        $this->pendingMediaQueue = $paths;

        $this->advanceMediaQueue();
    }

    #[On(ImageCropped::class)]
    public function onImageCropped(string $path): void
    {
        $this->insertPickedMedia($path);
        $this->advanceMediaQueue();
    }

    /**
     * Take the next picked file, cropping it first if it is an image.
     *
     * Video and attachments go straight in — there is nothing to crop, and
     * sending them through an image cropper would fail.
     */
    protected function advanceMediaQueue(): void
    {
        $path = array_shift($this->pendingMediaQueue);

        if ($path === null) {
            $this->pendingMediaKind = '';

            return;
        }

        if ($this->pendingMediaKind === 'image') {
            ImageCropper::open($path, ['preset' => 'landscape']);

            return;
        }

        $this->insertPickedMedia($path);
        $this->advanceMediaQueue();
    }

    /**
     * Hand the local file to the editor, then "upload" it.
     *
     * Two steps on purpose, because that is the shape of the real thing:
     *
     *  1. Insert immediately with `localPath`, so the picture appears the
     *     instant it is picked and the user can carry on writing.
     *  2. Store it and report the outcome, which swaps the block's temporary
     *     path for a permanent one and clears the pending state.
     *
     * ────────────────────────────────────────────────────────────────────
     *  REPLACE STEP 2 WITH YOUR UPLOAD API.
     * ────────────────────────────────────────────────────────────────────
     *
     * This demo has no server, so {@see MediaStore} copies the file into the
     * device's own storage and hands back a path. A real app POSTs the file
     * and hands back the URL its server returned — the editor cannot tell the
     * difference, and nothing else here changes.
     *
     * A real upload also takes time, so it belongs in a queued job or
     * `nativephp/mobile-background-tasks`, calling
     * {@see WysiwygEditor::uploadProgress()} as it goes. The user keeps
     * typing; the block shows its own progress.
     */
    protected function insertPickedMedia(string $path): void
    {
        $uploadId = 'up-'.substr(md5($path.microtime()), 0, 8);
        $this->pendingUploadId = $uploadId;

        // ── COMPRESS / RESIZE / TRANSCODE HERE ──────────────────────────
        //
        // The file is still yours at this point: the editor has not seen it
        // and nothing has been uploaded. Whatever comes back is what the user
        // sees AND what gets sent, so there is no second copy to keep in step.
        // See App\Support\MediaOptimizer for what belongs here and what does
        // not — video transcoding in PHP being the clearest "does not".
        $optimization = MediaOptimizer::optimize($path, $this->pendingMediaKind);
        $path = $optimization->path;

        $this->lastOptimization = $optimization;

        PayloadLog::call('MediaOptimizer::optimize', $optimization->summary());

        // No `alt` is sent. The editor labels a picture with its alt text when
        // there is one, and a filename is not alt text — a composer showing
        // `cropped_AD7C9A15-….jpg` under a photo looks like a file manager,
        // and none of the apps these demos imitate do it. Real alt text comes
        // from the person posting, or from a describe-image service; until
        // there is one, "Image" is the honest label.
        WysiwygEditor::insertMedia($this->pendingMediaKind, [
            'localPath' => $path,
            'uploadId' => $uploadId,
        ]);

        PayloadLog::call('WysiwygEditor::insertMedia', $this->pendingMediaKind.' '.$path);

        // ── Where your API call goes ────────────────────────────────────
        // $url = Http::attach('file', file_get_contents($path), basename($path))
        //     ->withToken($user->apiToken)
        //     ->post('https://api.example.com/v1/media')
        //     ->json('url');
        // A throw in here would leave the block spinning forever with nothing
        // on screen to say why — the failure mode this whole screen exists to
        // make visible. Catch it, tell the editor, and record it.
        try {
            $url = MediaStore::store($path);
        } catch (\Throwable $e) {
            PayloadLog::failure('MediaStore::store', $e->getMessage());
            WysiwygEditor::uploadFailed($uploadId, 'Could not store this file.');

            return;
        }

        if ($url === '') {
            PayloadLog::failure('MediaStore::store', 'returned no path for '.$path);
            WysiwygEditor::uploadFailed($uploadId, 'Could not store this file.');

            return;
        }

        WysiwygEditor::uploadCompleted($uploadId, $url);

        PayloadLog::call('WysiwygEditor::uploadCompleted', $url);

        // A real app would upload here — with its own auth and endpoint, or
        // nativephp/mobile-background-tasks — then report the outcome:
        //
        //   WysiwygEditor::uploadProgress($this->pendingUploadId, 0.4);
        //   WysiwygEditor::uploadCompleted($this->pendingUploadId, $cdnUrl);
        //   WysiwygEditor::uploadFailed($this->pendingUploadId, $e->getMessage());
    }
}
