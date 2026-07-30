<?php

namespace App\Models;

use App\Support\PostContent;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A short post written with the WysiwygEditor plugin.
 *
 * The X-style timeline shows the plain-text rendition rather than the HTML:
 * that composer is configured for plain text, so there is no formatting to
 * render — and reading `body_text` is what a real client would do for a feed
 * row it has to lay out thousands of.
 */
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $fillable = [
        'author_name', 'author_handle', 'body_html', 'body_text', 'body_json',
        'replies', 'reposts', 'likes',
    ];

    /**
     * The parts a timeline row draws: words, photos, video, poll.
     *
     * @return array{text: string, paragraphs: list<list<array{text: string, link: string}>>,
     *               images: list<array<string, string>>,
     *               video: ?array<string, string>, poll: ?array<string, mixed>}
     */
    public function content(): array
    {
        $parsed = PostContent::parse((string) $this->body_json);

        // Posts seeded without JSON still have to render something — and the
        // spans too, not just the text: a row that draws paragraphs would
        // otherwise show an empty post rather than a plain one.
        if ($parsed['text'] === '' && $parsed['images'] === [] && ! $parsed['video'] && ! $parsed['poll']) {
            $parsed['text'] = (string) $this->body_text;
            $parsed['paragraphs'] = array_values(array_filter(array_map(
                fn (string $line) => $line === '' ? [] : [['text' => $line, 'link' => '']],
                explode("\n", $parsed['text']),
            )));
        }

        return $parsed;
    }

    /** Initials for the avatar circle, the way a client does without a photo. */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->author_name)) ?: [];
        $letters = array_map(fn (string $p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2));

        return mb_strtoupper(implode('', $letters));
    }

    /** "2h", "3d" — the compact relative time a timeline uses. */
    public function age(): string
    {
        $seconds = (int) max(0, $this->created_at?->diffInSeconds() ?? 0);

        return match (true) {
            $seconds < 60 => $seconds.'s',
            $seconds < 3600 => intdiv($seconds, 60).'m',
            $seconds < 86400 => intdiv($seconds, 3600).'h',
            default => intdiv($seconds, 86400).'d',
        };
    }

    /** Counts read as "1.2K" past a thousand, never as a bare 1200. */
    public function metric(int $value): string
    {
        return match (true) {
            $value >= 1000000 => round($value / 1000000, 1).'M',
            $value >= 1000 => round($value / 1000, 1).'K',
            $value === 0 => '',
            default => (string) $value,
        };
    }
}
