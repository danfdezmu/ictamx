<?php

namespace App\Policies;

use App\Models\GalleryPhoto;
use App\Models\User;
use App\UserRole;

class GalleryPhotoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role?->managesGallery() ?? false;
    }

    public function create(User $user): bool
    {
        return $user->role?->managesGallery() ?? false;
    }

    public function update(User $user, GalleryPhoto $galleryPhoto): bool
    {
        return $user->role?->managesGallery() ?? false;
    }

    public function delete(User $user, GalleryPhoto $galleryPhoto): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return $user->role === UserRole::Multimedia
            && $galleryPhoto->user_id === $user->id;
    }
}
