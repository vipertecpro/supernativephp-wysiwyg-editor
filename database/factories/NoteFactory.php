<?php

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * A title line plus two paragraphs, in the plugin's normalised HTML shape.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(4), '.');
        $first = $this->faker->sentence(12);
        $second = $this->faker->sentence(10);

        return [
            'body_html' => "<h2>{$title}</h2><p>{$first}</p><p>{$second}</p>",
            'body_text' => "{$title}\n{$first}\n{$second}",
        ];
    }

    /** A note that shows off every block and mark the editor supports. */
    public function showcase(): static
    {
        $html = '<h1>Welcome to WysiwygEditor</h1>'
            .'<p>A <strong>fully native</strong> rich text editor — <em>no webview</em>, just the platform text engine.</p>'
            .'<h2>What it can do</h2>'
            .'<ul><li>Headings, <strong>bold</strong> and <em>italic</em></li><li>Bullet and numbered lists</li><li><a href="https://nativephp.com">Links</a> and <code>inline code</code></li></ul>'
            .'<blockquote>Clean HTML in, clean HTML out — identical on iOS and Android.</blockquote>'
            .'<p>Tap this note to open it in the editor.</p>';

        $text = "Welcome to WysiwygEditor\n"
            ."A fully native rich text editor — no webview, just the platform text engine.\n"
            ."What it can do\n"
            ."- Headings, bold and italic\n"
            ."- Bullet and numbered lists\n"
            ."- Links and inline code\n"
            ."Clean HTML in, clean HTML out — identical on iOS and Android.\n"
            .'Tap this note to open it in the editor.';

        return $this->state(fn (): array => [
            'body_html' => $html,
            'body_text' => $text,
        ]);
    }
}
