<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ============================================================================
 *  REPLACE THIS CLASS WITH YOUR UPLOAD API.
 * ============================================================================
 *
 * This demo has no server, so it keeps attachments on the DEVICE. That is not
 * what a real app does — a real app POSTs the file somewhere and stores the URL
 * it gets back.
 *
 * The important thing is that the SHAPE is identical either way, because the
 * editor does not care where the file went. It asks for media, you hand it a
 * local path so the user sees the picture immediately, and then you tell it how
 * the upload finished:
 *
 *     WysiwygEditor::uploadProgress($uploadId, 0.4);
 *     WysiwygEditor::uploadCompleted($uploadId, $urlYourServerReturned);
 *     WysiwygEditor::uploadFailed($uploadId, $message);
 *
 * So to make this app real, you change {@see self::store()} to something like:
 *
 *     $response = Http::attach('file', file_get_contents($path), basename($path))
 *         ->withToken($user->apiToken)
 *         ->post('https://api.example.com/v1/media');
 *
 *     return $response->json('url');
 *
 * …and nothing else in the app changes. The editor, the timeline and the saved
 * document all keep working, because a `src` is a `src` whether it points at
 * your CDN or at a file in this app's storage.
 *
 * Doing the upload in the background (so the user can keep typing, and so it
 * survives leaving the screen) is what `nativephp/mobile-background-tasks` or a
 * queued job is for. The editor does not need to know either way — it only
 * needs the three callbacks above.
 */
class MediaStore
{
    /** Where attachments live on the device, inside the app's own storage. */
    public const DISK = 'local';

    public const DIRECTORY = 'attachments';

    /**
     * What to call the stored file.
     *
     * A picked file does not reliably arrive with an extension — Android hands
     * over a content URI resolved to a cache entry with no suffix at all — and
     * naming everything `.bin` is not harmless: a server hands `.bin` back as
     * `application/octet-stream`, so the `<video>` the editor saved will not
     * play and the `<img>` will not render. The bytes are fine; the name is
     * what breaks it.
     *
     * So the extension comes from the path when there is one, and from what
     * the file actually IS when there is not.
     */
    protected static function extensionFor(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if ($extension !== '') {
            return strtolower($extension);
        }

        $mime = function_exists('mime_content_type') ? @mime_content_type($path) : false;

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/heic', 'image/heif' => 'heic',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    /**
     * "Upload" a picked file.
     *
     * Here that means copying it out of the picker's temporary location into
     * storage this app controls, and returning a path that will still resolve
     * after the app restarts — which is exactly the guarantee a real upload
     * gives you when it returns a URL.
     *
     * The picker and the cropper both write to caches the system may clear;
     * a document that referenced those directly would lose its pictures.
     *
     * @return string absolute path on the device, or '' when the copy failed
     */
    public static function store(string $path): string
    {
        if ($path === '' || ! is_readable($path)) {
            return '';
        }

        $extension = self::extensionFor($path);
        $name = self::DIRECTORY.'/'.Str::uuid()->toString().'.'.$extension;

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            // Streamed rather than read whole: a video picked on a phone can
            // be hundreds of megabytes, and file_get_contents would hold all
            // of it in memory at once.
            $stored = Storage::disk(self::DISK)->writeStream($name, $handle);
        } finally {
            fclose($handle);
        }

        return $stored ? Storage::disk(self::DISK)->path($name) : '';
    }

    /**
     * Forget an attachment.
     *
     * Against a real API this would be a DELETE — and, like this, something you
     * can afford to get wrong occasionally: a file nobody references is
     * clutter, whereas deleting one that is still referenced breaks a post.
     */
    public static function forget(string $storedPath): void
    {
        $name = self::DIRECTORY.'/'.basename($storedPath);

        if (Storage::disk(self::DISK)->exists($name)) {
            Storage::disk(self::DISK)->delete($name);
        }
    }
}
