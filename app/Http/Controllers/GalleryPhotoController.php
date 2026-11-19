<?php

namespace App\Http\Controllers;

use App\Models\GalleryPhoto;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GalleryPhotoController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GalleryPhoto::class);

        $user = $request->user();
        $tagId = $request->integer('etiqueta');
        $activeTag = $tagId > 0 ? Tag::query()->find($tagId) : null;

        $photos = GalleryPhoto::query()
            ->with(['user:id,name', 'album:id,name', 'tags:id,name'])
            ->when($activeTag, fn ($query) => $query->whereHas(
                'tags',
                fn ($tags) => $tags->whereKey($activeTag->id),
            ))
            ->latest()
            ->get()
            ->map(fn (GalleryPhoto $photo): array => [
                'id' => $photo->id,
                'url' => $photo->url(),
                'alt' => $photo->alt,
                'author' => $photo->user?->name ?? 'Iglesia',
                'album_id' => $photo->album_id,
                'album' => $photo->album?->name,
                'tags' => $photo->tags->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])->all(),
                'created_at' => $photo->created_at?->timezone(config('app.timezone'))->format('d/m/Y'),
                'can_delete' => $user instanceof User && $user->can('delete', $photo),
            ])
            ->all();

        $tags = Tag::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])
            ->all();

        return Inertia::render('gallery/Index', [
            'photos' => $photos,
            'tags' => $tags,
            'active_tag' => $activeTag?->id,
        ]);
    }

    public function destroy(GalleryPhoto $galleryPhoto): RedirectResponse
    {
        Gate::authorize('delete', $galleryPhoto);

        Storage::disk('public')->delete($galleryPhoto->path);
        $galleryPhoto->delete();

        return back();
    }
}
