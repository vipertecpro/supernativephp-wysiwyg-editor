<?php

namespace App\Models;

use App\Support\RichText;
use Database\Factories\NoteFactory;
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

    protected $fillable = ['body_html', 'body_text'];

    /** First line of the note, used as its display title. */
    public function title(): string
    {
        return RichText::excerpt(strtok($this->body_text, "\n") ?: 'Untitled', 60);
    }

    /** Everything after the first line, as a short list excerpt. */
    public function excerpt(): string
    {
        $rest = trim((string) strstr($this->body_text, "\n"));

        return RichText::excerpt($rest, 90);
    }
}
