<?php

namespace App\Concerns;

use App\Models\Tag;

trait ParsesPhotoTags
{
    protected function prepareForValidation(): void
    {
        $selected = $this->input('tags', []);

        $this->merge([
            'tags' => Tag::namesFrom(is_array($selected) ? $selected : [], $this->input('new_tags')),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function tagRules(): array
    {
        return [
            'tags' => ['array'],
            'tags.*' => ['string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function tagMessages(): array
    {
        return [
            'tags.*.max' => 'Cada etiqueta puede tener hasta 40 caracteres.',
        ];
    }
}
