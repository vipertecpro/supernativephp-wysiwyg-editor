<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A page in the Notion demo.
 *
 * The title is not a column: it is the first line of the document, the way
 * Notion works. Storing it separately would mean two places to keep in step,
 * and the editor already hands back the plain text on every save.
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = ['icon', 'body_html', 'body_text', 'body_json'];

    /** The first line of the document, or a placeholder for an empty page. */
    public function title(): string
    {
        $first = trim(strtok((string) $this->body_text, "\n") ?: '');

        return $first === '' ? 'Untitled' : $first;
    }

    /** What is left after the title, for the one-line preview. */
    public function excerpt(): string
    {
        $lines = array_values(array_filter(
            array_map('trim', explode("\n", (string) $this->body_text)),
            fn (string $line) => $line !== '',
        ));

        return implode('  ', array_slice($lines, 1, 2));
    }

    /** "Edited 3h ago", the way a page list dates itself. */
    public function edited(): string
    {
        $seconds = (int) max(0, $this->updated_at?->diffInSeconds() ?? 0);

        return match (true) {
            $seconds < 60 => 'just now',
            $seconds < 3600 => intdiv($seconds, 60).'m ago',
            $seconds < 86400 => intdiv($seconds, 3600).'h ago',
            default => intdiv($seconds, 86400).'d ago',
        };
    }
}
