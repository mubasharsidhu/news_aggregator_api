<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Article;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title'       => $this->faker->sentence,
            'description' => $this->faker->text,
            'content'     => $this->faker->paragraph,
            'source'      => $this->faker->word,
            'author'      => $this->faker->name,
            'imageUrl'    => $this->faker->imageUrl,
            'articleUrl'  => $this->faker->url,
            'publishedAt' => $this->faker->dateTimeThisYear,
            'apiSource'   => $this->faker->word,
        ];
    }
}
