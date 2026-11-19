<?php

namespace App\Http\Requests;

use App\Concerns\ParsesPhotoTags;
use App\Models\GalleryPhoto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlbumPhotoRequest extends FormRequest
{
    use ParsesPhotoTags {
        prepareForValidation as private preparePhotoTags;
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', GalleryPhoto::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->preparePhotoTags();

        $description = trim($this->string('alt')->toString());

        $this->merge([
            'alt' => $description === '' ? null : $description,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt' => ['nullable', 'string', 'max:160'],
            ...$this->tagRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Selecciona una imagen.',
            'image.image' => 'El archivo tiene que ser una imagen.',
            'image.mimes' => 'Usa una imagen JPG, PNG o WebP.',
            'image.max' => 'La imagen no puede pasar de 5 MB.',
            'alt.max' => 'La descripción no puede pasar de 160 caracteres.',
            ...$this->tagMessages(),
        ];
    }
}
