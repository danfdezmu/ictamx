<?php

use App\Models\Album;
use App\Models\GalleryPhoto;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login page', function () {
    $this->get(route('gallery.index'))->assertRedirect(route('login'));
});

test('a musician cannot open the gallery manager', function () {
    $user = User::factory()->withRole(UserRole::Musico)->create();

    $this->actingAs($user)
        ->get(route('gallery.index'))
        ->assertForbidden();
});

test('a user without a role cannot open the gallery manager', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('gallery.index'))
        ->assertForbidden();
});

test('multimedia can see the gallery manager', function () {
    $user = User::factory()->withRole(UserRole::Multimedia)->create();
    $photo = GalleryPhoto::factory()->for($user)->create(['alt' => 'Culto del domingo']);

    $this->actingAs($user)
        ->get(route('gallery.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('gallery/Index')
            ->where('photos.0.alt', 'Culto del domingo')
            ->where('photos.0.can_delete', true));
});

test('admin and multimedia can manage the gallery and a musician cannot', function (UserRole $role, bool $allowed) {
    $user = User::factory()->withRole($role)->create();

    expect($user->can('viewAny', GalleryPhoto::class))->toBe($allowed)
        ->and($user->can('create', GalleryPhoto::class))->toBe($allowed);
})->with([
    'admin' => [UserRole::Admin, true],
    'multimedia' => [UserRole::Multimedia, true],
    'musician' => [UserRole::Musico, false],
]);

test('an admin can delete any photo and multimedia can delete only their own', function (UserRole $role, bool $ownsPhoto, bool $allowed) {
    $user = User::factory()->withRole($role)->create();
    $photo = GalleryPhoto::factory()->create([
        'user_id' => $ownsPhoto ? $user->id : User::factory(),
    ]);

    expect($user->can('delete', $photo))->toBe($allowed);
})->with([
    'admin deletes another photo' => [UserRole::Admin, false, true],
    'multimedia deletes their photo' => [UserRole::Multimedia, true, true],
    'multimedia deletes another photo' => [UserRole::Multimedia, false, false],
    'musician deletes their photo' => [UserRole::Musico, true, false],
]);

test('multimedia can add a photo to an album with its tag', function () {
    Storage::fake('public');

    $user = User::factory()->withRole(UserRole::Multimedia)->create();

    $this->actingAs($user)
        ->post(route('albums.store'), ['name' => 'Jóvenes'])
        ->assertRedirect();

    $album = Album::query()->sole();

    $this->post(route('albums.photos.store', $album), [
        'image' => UploadedFile::fake()->image('culto.jpg'),
        'alt' => 'Culto del domingo',
        'new_tags' => 'Adoración',
    ])->assertRedirect(route('albums.show', $album));

    $photo = GalleryPhoto::query()->sole();

    expect($photo->alt)->toBe('Culto del domingo')
        ->and($photo->user_id)->toBe($user->id)
        ->and($photo->album_id)->toBe($album->id)
        ->and($photo->tags()->pluck('name')->sort()->values()->all())->toBe(['Adoración', 'Jóvenes']);

    Storage::disk('public')->assertExists($photo->path);
});

test('adding a photo requires an image and the description can be blank', function () {
    Storage::fake('public');

    $user = User::factory()->withRole(UserRole::Multimedia)->create();
    $album = Album::createFor($user, 'Jóvenes');

    $this->actingAs($user)
        ->post(route('albums.photos.store', $album), [])
        ->assertSessionHasErrors([
            'image' => 'Selecciona una imagen.',
        ])
        ->assertSessionDoesntHaveErrors('alt');

    $this->post(route('albums.photos.store', $album), [
        'image' => UploadedFile::fake()->image('culto.jpg'),
        'alt' => '   ',
    ])->assertRedirect(route('albums.show', $album));

    expect(GalleryPhoto::query()->sole()->alt)->toBeNull();
});

test('adding a photo rejects a file that is not an image', function () {
    $user = User::factory()->withRole(UserRole::Multimedia)->create();
    $album = Album::createFor($user, 'Jóvenes');

    $this->actingAs($user)
        ->post(route('albums.photos.store', $album), [
            'image' => UploadedFile::fake()->create('notas.pdf', 100, 'application/pdf'),
            'alt' => 'Notas',
        ])
        ->assertSessionHasErrors([
            'image' => 'El archivo tiene que ser una imagen.',
        ]);
});

test('multimedia cannot delete a photo uploaded by someone else', function () {
    Storage::fake('public');

    $owner = User::factory()->withRole(UserRole::Multimedia)->create();
    $other = User::factory()->withRole(UserRole::Multimedia)->create();
    $path = UploadedFile::fake()->image('culto.jpg')->store('gallery', 'public');
    $photo = GalleryPhoto::factory()->create([
        'user_id' => $owner->id,
        'path' => $path,
    ]);

    $this->actingAs($other)
        ->delete(route('gallery.destroy', $photo))
        ->assertForbidden();

    $this->assertModelExists($photo);
    Storage::disk('public')->assertExists($path);
});

test('the owner can delete their photo and its file', function () {
    Storage::fake('public');

    $owner = User::factory()->withRole(UserRole::Multimedia)->create();
    $path = UploadedFile::fake()->image('culto.jpg')->store('gallery', 'public');
    $photo = GalleryPhoto::factory()->create([
        'user_id' => $owner->id,
        'path' => $path,
    ]);

    $this->actingAs($owner)
        ->from(route('gallery.index'))
        ->delete(route('gallery.destroy', $photo))
        ->assertRedirect(route('gallery.index'));

    $this->assertModelMissing($photo);
    Storage::disk('public')->assertMissing($path);
});

test('the public home shows only the six newest photos', function () {
    GalleryPhoto::factory()
        ->count(7)
        ->sequence(fn ($sequence) => [
            'alt' => 'Foto '.$sequence->index,
            'created_at' => now()->subMinutes(7 - $sequence->index),
        ])
        ->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('gallery', 6)
            ->where('gallery.0.alt', 'Foto 6')
            ->where('gallery.5.alt', 'Foto 1')
            ->missing('gallery.6'));
});
