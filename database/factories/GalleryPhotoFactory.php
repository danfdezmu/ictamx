<?php

namespace Database\Factories;

use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'path' => 'gallery/'.fake()->uuid().'.jpg',
            'alt' => fake()->sentence(4),
        ];
    }
}
