<?php

namespace App\Models;

use App\Support\RichText;
use Database\Factories\NoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A rich-text note written with the WysiwygEditor plugin.
 *
 * `body_html` is the editor's normalised HTML (the source of truth);
 * `body_text` is the plain-text rendition the plugin delivers alongside it,
 * kept for excerpts and search.
 */
class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;

    protected $fillable = ['surface', 'folder', 'pinned', 'body_html', 'body_text'];

    /**
     * Only the notes belonging to one demo.
     *
     * Two demos share this table and are meant to be two apps — a note written
     * in one should not appear in the other.
     */
    public function scopeOnSurface(Builder $query, string $surface): Builder
    {
        return $query->where('surface', $surface);
    }

    /** @var array<string, string> */
    protected $casts = ['pinned' => 'boolean'];

    /** First line of the note, used as its display title. */
    public function title(): string
    {
        return RichText::excerpt(strtok($this->body_text, "\n") ?: 'Untitled', 60);
    }

    /** Everything after the first line, as a short list excerpt. */
    public function excerpt(): string
    {
        $rest = trim((string) strstr((string) $this->body_text, "\n"));

        return RichText::excerpt($rest, 90);
    }

    /** "3m ago", the way a note list dates itself. */
    public function age(): string
    {
        $seconds = (int) max(0, $this->updated_at?->diffInSeconds() ?? 0);

        return match (true) {
            $seconds < 60 => 'Just now',
            $seconds < 3600 => intdiv($seconds, 60).'m ago',
            $seconds < 86400 => intdiv($seconds, 3600).'h ago',
            default => intdiv($seconds, 86400).'d ago',
        };
    }
}
