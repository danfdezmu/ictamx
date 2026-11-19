<?php

namespace Database\Seeders;

use App\Models\GalleryPhoto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class GalleryPhotoSeeder extends Seeder
{
    /**
     * Load the photos already published on the public site.
     */
    public function run(): void
    {
        if (GalleryPhoto::query()->exists()) {
            return;
        }

        $photos = [
            [
                'file' => 'ordena-tu-casa.jpg',
                'alt' => 'Ordena tu casa. 2 Reyes 20:1-11. Ap. Martín López',
            ],
            [
                'file' => 'culto.jpg',
                'alt' => 'Culto en Iglesia Cristiana Tabernáculo de Adoración',
            ],
        ];

        foreach ($photos as $photo) {
            $source = database_path('seeders/images/'.$photo['file']);

            if (! is_file($source)) {
                continue;
            }

            $contents = file_get_contents($source);

            if ($contents === false) {
                continue;
            }

            $path = 'gallery/'.$photo['file'];
            Storage::disk('public')->put($path, $contents);

            GalleryPhoto::query()->create([
                'path' => $path,
                'alt' => $photo['alt'],
            ]);
        }
    }
}
