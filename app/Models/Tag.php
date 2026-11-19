<?php

namespace App\Models;

use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'key'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    public static function normalize(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    public static function keyFor(string $name): string
    {
        return mb_strtolower(self::normalize($name));
    }

    /**
     * @param  array<int, mixed>  $selected
     * @return list<string>
     */
    public static function namesFrom(array $selected, mixed $additional): array
    {
        $pieces = $selected;

        foreach (preg_split('/\s*,\s*/u', (string) $additional) ?: [] as $piece) {
            $pieces[] = $piece;
        }

        return collect($pieces)
            ->map(fn (mixed $name): string => self::normalize((string) $name))
            ->filter()
            ->unique(fn (string $name): string => self::keyFor($name))
            ->values()
            ->all();
    }

    public static function fromName(string $name): self
    {
        $name = self::normalize($name);
        $key = self::keyFor($name);

        $tag = self::query()->where('key', $key)->first();

        if ($tag instanceof self) {
            return $tag;
        }

        return self::query()->create([
            'name' => $name,
            'key' => $key,
        ]);
    }

    /**
     * @return BelongsToMany<GalleryPhoto, $this>
     */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(GalleryPhoto::class);
    }
}
