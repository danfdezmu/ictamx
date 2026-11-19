<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\GalleryPhoto;
use Inertia\Inertia;
use Inertia\Response;

class PublicGalleryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('gallery/Public', [
            'albums' => $this->albums(),
            'photos' => $this->photos(),
            'album' => null,
        ]);
    }

    public function show(Album $album): Response
    {
        return Inertia::render('gallery/Public', [
            'albums' => $this->albums(),
            'photos' => $this->photos($album),
            'album' => [
                'id' => $album->id,
                'name' => $album->name,
            ],
        ]);
    }

    /**
     * @return list<array{id: int, name: string, photos_count: int}>
     */
    private function albums(): array
    {
        return Album::query()
            ->withCount('photos')
            ->orderBy('name')
            ->get()
            ->map(fn (Album $album): array => [
                'id' => $album->id,
                'name' => $album->name,
                'photos_count' => $album->photos_count,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, src: string, alt: string|null}>
     */
    private function photos(?Album $album = null): array
    {
        return GalleryPhoto::query()
            ->when($album, fn ($query) => $query->whereBelongsTo($album))
            ->latest()
            ->get()
            ->map(fn (GalleryPhoto $photo): array => [
                'id' => $photo->id,
                'src' => $photo->url(),
                'alt' => $photo->alt,
            ])
            ->all();
    }
}
