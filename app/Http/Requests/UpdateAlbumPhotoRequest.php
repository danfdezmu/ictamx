<?php

namespace App\Http\Requests;

use App\Concerns\ParsesPhotoTags;
use App\Models\GalleryPhoto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAlbumPhotoRequest extends FormRequest
{
    use ParsesPhotoTags {
        prepareForValidation as private preparePhotoTags;
    }

    public function authorize(): bool
    {
        $photo = $this->route('galleryPhoto');

        return $photo instanceof GalleryPhoto && ($this->user()?->can('update', $photo) ?? false);
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
            'alt.max' => 'La descripción no puede pasar de 160 caracteres.',
            ...$this->tagMessages(),
        ];
    }
}
