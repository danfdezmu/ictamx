<?php

namespace App\Http\Controllers;

use App\Models\GalleryPhoto;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $gallery = GalleryPhoto::query()
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (GalleryPhoto $photo): array => [
                'src' => $photo->url(),
                'alt' => $photo->alt,
            ])
            ->all();

        return Inertia::render('Welcome', [
            'church' => 'Iglesia Cristiana Tabernáculo de Adoración',
            'gallery' => $gallery,
        ]);
    }
}
