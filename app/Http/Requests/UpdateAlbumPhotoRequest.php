<?php

namespace App\Http\Requests;

use App\Concerns\ParsesPhotoTags;
use App\Models\GalleryPhoto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAlbumPhotoRequest extends FormRequest
{
    use ParsesPhotoTags;

    public function authorize(): bool
    {
        $photo = $this->route('galleryPhoto');

        return $photo instanceof GalleryPhoto && ($this->user()?->can('update', $photo) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alt' => ['required', 'string', 'max:160'],
            ...$this->tagRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alt.required' => 'Escribe una descripción de la foto.',
            'alt.max' => 'La descripción no puede pasar de 160 caracteres.',
            ...$this->tagMessages(),
        ];
    }
}
