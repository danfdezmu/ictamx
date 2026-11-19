<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumPhotoRequest;
use App\Http\Requests\UpdateAlbumPhotoRequest;
use App\Models\Album;
use App\Models\GalleryPhoto;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AlbumPhotoController extends Controller
{
    public function store(StoreAlbumPhotoRequest $request, Album $album): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $path = $request->file('image')?->store('gallery', 'public');

        if (! is_string($path)) {
            throw ValidationException::withMessages([
                'image' => 'No se pudo guardar la imagen.',
            ]);
        }

        $photo = $album->photos()->make([
            'path' => $path,
            'alt' => $request->string('alt')->toString(),
        ]);
        $photo->user()->associate($user);
        $photo->save();

        $photo->syncTags($album, $request->input('tags', []));

        return to_route('albums.show', $album);
    }

    public function update(UpdateAlbumPhotoRequest $request, Album $album, GalleryPhoto $galleryPhoto): RedirectResponse
    {
        abort_unless($galleryPhoto->album_id === $album->id, 404);

        $galleryPhoto->update([
            'alt' => $request->string('alt')->toString(),
        ]);

        $galleryPhoto->syncTags($album, $request->input('tags', []));

        return to_route('albums.show', $album);
    }
}
