<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $text = fake()->sentence(fake()->numberBetween(6, 20));

        return [
            'author_name' => fake()->name(),
            'author_handle' => '@'.fake()->userName(),
            'body_html' => '<p>'.e($text).'</p>',
            'body_text' => $text,
            'replies' => fake()->numberBetween(0, 400),
            'reposts' => fake()->numberBetween(0, 2000),
            'likes' => fake()->numberBetween(0, 20000),
            'created_at' => fake()->dateTimeBetween('-2 days'),
        ];
    }
}
