<?php

namespace App\Models;

use Database\Factories\AlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $tag_id
 * @property string $name
 * @property string $key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'key', 'tag_id'])]
class Album extends Model
{
    /** @use HasFactory<AlbumFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Tag, $this>
     */
    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<GalleryPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class);
    }

    public static function createFor(User $user, string $name): self
    {
        $tag = Tag::fromName($name);

        return $user->albums()->create([
            'name' => $tag->name,
            'key' => $tag->key,
            'tag_id' => $tag->id,
        ]);
    }

    public function renameTo(string $name): void
    {
        $tag = Tag::fromName($name);
        $previousTagId = $this->tag_id;

        $this->update([
            'name' => $tag->name,
            'key' => $tag->key,
            'tag_id' => $tag->id,
        ]);

        if ($previousTagId === $tag->id) {
            return;
        }

        $this->photos()->each(function (GalleryPhoto $photo) use ($previousTagId, $tag): void {
            $photo->tags()->detach($previousTagId);
            $photo->tags()->syncWithoutDetaching([$tag->id]);
        });
    }
}
