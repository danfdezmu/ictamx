<?php

use App\Models\Album;
use App\Models\GalleryPhoto;
use App\Models\Tag;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a musician cannot open the albums', function () {
    $user = User::factory()->withRole(UserRole::Musico)->create();

    $this->actingAs($user)
        ->get(route('albums.index'))
        ->assertForbidden();
});

test('multimedia can create an album and its tag', function () {
    $user = User::factory()->withRole(UserRole::Multimedia)->create();

    $this->actingAs($user)
        ->post(route('albums.store'), ['name' => 'Jóvenes'])
        ->assertRedirect();

    $album = Album::query()->sole();

    expect($album->name)->toBe('Jóvenes')
        ->and($album->tag->name)->toBe('Jóvenes')
        ->and(Tag::query()->where('key', 'jóvenes')->exists())->toBeTrue();
});

test('an album name cannot be repeated', function () {
    $user = User::factory()->withRole(UserRole::Admin)->create();
    Album::createFor($user, 'Jóvenes');

    $this->actingAs($user)
        ->post(route('albums.store'), ['name' => 'jóvenes'])
        ->assertSessionHasErrors([
            'name' => 'Ya existe un álbum con ese nombre.',
        ]);
});

test('renaming an album moves its tag and keeps the other tags', function () {
    $user = User::factory()->withRole(UserRole::Admin)->create();
    $album = Album::createFor($user, 'Jóvenes');
    $photo = GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
    ]);
    $photo->syncTags($album, ['Adoración']);

    $this->actingAs($user)
        ->patch(route('albums.update', $album), ['name' => 'Juventud'])
        ->assertRedirect(route('albums.show', $album));

    $names = $photo->tags()->pluck('name')->sort()->values()->all();

    expect($album->refresh()->name)->toBe('Juventud')
        ->and($names)->toBe(['Adoración', 'Juventud']);
});

test('editing photo tags keeps the album tag', function () {
    $user = User::factory()->withRole(UserRole::Multimedia)->create();
    $album = Album::createFor($user, 'Jóvenes');
    $photo = GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'alt' => 'Reunión',
    ]);
    $photo->syncTags($album, ['Adoración', 'Celebración']);

    $this->actingAs($user)
        ->patch(route('albums.photos.update', [$album, $photo]), [
            'alt' => 'Reunión de jóvenes',
            'tags' => ['Celebración'],
        ])
        ->assertRedirect(route('albums.show', $album));

    expect($photo->refresh()->alt)->toBe('Reunión de jóvenes')
        ->and($photo->tags()->pluck('name')->sort()->values()->all())->toBe(['Celebración', 'Jóvenes']);
});

test('clearing a photo description leaves it blank', function () {
    $user = User::factory()->withRole(UserRole::Multimedia)->create();
    $album = Album::createFor($user, 'Jóvenes');
    $photo = GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $album->id,
        'alt' => 'Reunión',
    ]);

    $this->actingAs($user)
        ->patch(route('albums.photos.update', [$album, $photo]), [
            'alt' => '',
        ])
        ->assertRedirect(route('albums.show', $album));

    expect($photo->refresh()->alt)->toBeNull();
});

test('a photo from another album cannot be edited here', function () {
    $user = User::factory()->withRole(UserRole::Admin)->create();
    $album = Album::createFor($user, 'Jóvenes');
    $other = Album::createFor($user, 'Niños');
    $photo = GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $other->id,
    ]);

    $this->actingAs($user)
        ->patch(route('albums.photos.update', [$album, $photo]), [
            'alt' => 'Otra',
        ])
        ->assertNotFound();
});

test('the gallery can be filtered by a tag', function () {
    $user = User::factory()->withRole(UserRole::Multimedia)->create();
    $youth = Album::createFor($user, 'Jóvenes');
    $children = Album::createFor($user, 'Niños');

    $youthPhoto = GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $youth->id,
        'alt' => 'Campamento',
    ]);
    $youthPhoto->syncTags($youth, []);

    $childPhoto = GalleryPhoto::factory()->create([
        'user_id' => $user->id,
        'album_id' => $children->id,
        'alt' => 'Clase',
    ]);
    $childPhoto->syncTags($children, []);

    $this->actingAs($user)
        ->get(route('gallery.index', ['etiqueta' => $youth->tag_id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gallery/Index')
            ->has('photos', 1)
            ->where('photos.0.alt', 'Campamento'));
});

test('uploading without extra tags still applies the album tag', function () {
    Storage::fake('public');

    $user = User::factory()->withRole(UserRole::Multimedia)->create();
    $album = Album::createFor($user, 'Jóvenes');

    $this->actingAs($user)
        ->post(route('albums.photos.store', $album), [
            'image' => UploadedFile::fake()->image('foto.jpg'),
            'alt' => 'Grupo',
        ])
        ->assertRedirect(route('albums.show', $album));

    expect(GalleryPhoto::query()->sole()->tags()->pluck('name')->all())->toBe(['Jóvenes']);
});
