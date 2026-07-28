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

    /** A note containing MEDIA blocks, to exercise the segment shell. */
    public function withMedia(): static
    {
        $html = '<h1>Trip notes</h1>'
            .'<p>The harbour at dawn.</p>'
            .'<figure><img src="https://example.com/harbour.jpg" alt="Harbour"><figcaption>Dawn light</figcaption></figure>'
            .'<p>Then we walked inland.</p>'
            .'<hr>'
            .'<figure data-poll="{&quot;version&quot;:2,&quot;blocks&quot;:[{&quot;id&quot;:&quot;p1&quot;,&quot;type&quot;:&quot;poll&quot;,&quot;question&quot;:&quot;Go back?&quot;,&quot;options&quot;:[{&quot;id&quot;:&quot;o1&quot;,&quot;label&quot;:&quot;Yes&quot;},{&quot;id&quot;:&quot;o2&quot;,&quot;label&quot;:&quot;Maybe&quot;}]}]}"></figure>'
            .'<p>End of the day.</p>';

        return $this->state(fn (): array => [
            'body_html' => $html,
            'body_text' => "Trip notes\nThe harbour at dawn.\nDawn light\nThen we walked inland.\n---\nGo back?\nEnd of the day.",
        ]);
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
