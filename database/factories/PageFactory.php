<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->sentence(3), '.');
        $body = $this->faker->sentence(12);

        return [
            'icon' => '📄',
            'body_html' => "<h1>{$title}</h1><p>{$body}</p>",
            'body_text' => "{$title}\n{$body}",
        ];
    }
}
