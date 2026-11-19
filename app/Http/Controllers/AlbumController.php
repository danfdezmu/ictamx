<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAlbumRequest;
use App\Models\Album;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Album::class);

        $albums = Album::query()
            ->with('tag:id,name')
            ->withCount('photos')
            ->latest()
            ->get()
            ->map(fn (Album $album): array => [
                'id' => $album->id,
                'name' => $album->name,
                'photos_count' => $album->photos_count,
            ])
            ->all();

        return Inertia::render('albums/Index', [
            'albums' => $albums,
        ]);
    }

    public function store(SaveAlbumRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $album = Album::createFor($user, $request->string('name')->toString());

        return to_route('albums.show', $album);
    }

    public function show(Album $album): Response
    {
        Gate::authorize('view', $album);

        $album->load(['tag:id,name', 'photos' => fn ($query) => $query->with('tags:id,name')->latest()]);

        $photos = $album->photos->map(fn ($photo): array => [
            'id' => $photo->id,
            'url' => $photo->url(),
            'alt' => $photo->alt,
            'tags' => $photo->tags
                ->reject(fn (Tag $tag): bool => $tag->id === $album->tag_id)
                ->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                ])
                ->values()
                ->all(),
            'can_delete' => request()->user() instanceof User && request()->user()->can('delete', $photo),
        ])->all();

        $tags = Tag::query()
            ->whereKeyNot($album->tag_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
            ])
            ->all();

        return Inertia::render('albums/Show', [
            'album' => [
                'id' => $album->id,
                'name' => $album->name,
                'tag' => $album->tag?->name ?? $album->name,
            ],
            'photos' => $photos,
            'tags' => $tags,
        ]);
    }

    public function update(SaveAlbumRequest $request, Album $album): RedirectResponse
    {
        $album->renameTo($request->string('name')->toString());

        return to_route('albums.show', $album);
    }
}
