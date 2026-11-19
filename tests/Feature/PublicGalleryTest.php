<?php

use App\Models\Album;
use App\Models\GalleryPhoto;
use App\Models\User;

test('anyone can browse the public gallery', function () {
    $user = User::factory()->create();
    $youth = Album::createFor($user, 'Jóvenes');
    $worship = Album::createFor($user, 'Culto');

    GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $youth->id,
        'alt' => 'Campamento',
        'created_at' => now()->subDay(),
    ]);
    GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => null,
        'alt' => 'Foto suelta',
        'created_at' => now(),
    ]);

    $this->get(route('galeria.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gallery/Public')
            ->where('album', null)
            ->has('photos', 2)
            ->where('photos.0.alt', 'Foto suelta')
            ->where('albums.0.name', 'Culto')
            ->where('albums.0.photos_count', 0)
            ->where('albums.1.name', 'Jóvenes')
            ->where('albums.1.photos_count', 1));
});

test('an album page shows only the photos from that album', function () {
    $user = User::factory()->create();
    $youth = Album::createFor($user, 'Jóvenes');
    $worship = Album::createFor($user, 'Culto');

    GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $youth->id,
        'alt' => 'Campamento',
    ]);
    GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $worship->id,
        'alt' => 'Domingo',
    ]);

    $this->get(route('galeria.show', $youth))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gallery/Public')
            ->where('album.name', 'Jóvenes')
            ->has('photos', 1)
            ->where('photos.0.alt', 'Campamento'));
});

test('a missing album is not shown', function () {
    $this->get(route('galeria.show', 99999))->assertNotFound();
});
