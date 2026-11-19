<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Album>
 */
class AlbumFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'user_id' => User::factory(),
            'tag_id' => Tag::factory()->state([
                'name' => $name,
                'key' => Tag::keyFor($name),
            ]),
            'name' => $name,
            'key' => Tag::keyFor($name),
        ];
    }
}
