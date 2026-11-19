<?php

namespace App\Http\Requests;

use App\Models\Album;
use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SaveAlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        $album = $this->route('album');

        if ($album instanceof Album) {
            return Gate::allows('update', $album);
        }

        return Gate::allows('create', Album::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => Tag::normalize($this->string('name')->toString()),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $album = $this->route('album');

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                function (string $attribute, mixed $value, Closure $fail) use ($album): void {
                    $query = Album::query()->where('key', Tag::keyFor((string) $value));

                    if ($album instanceof Album) {
                        $query->whereKeyNot($album->id);
                    }

                    if ($query->exists()) {
                        $fail('Ya existe un álbum con ese nombre.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Escribe el nombre del álbum.',
            'name.max' => 'El nombre del álbum no puede pasar de 80 caracteres.',
        ];
    }
}
