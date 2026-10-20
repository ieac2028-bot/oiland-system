<?php

namespace App\Http\Requests\Admin;

use App\Models\Branch;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the station create/update form.
 *
 * The form collects photo links one per line in a textarea, so the newline
 * separated string is folded into a clean array before the rules run.
 */
class BranchRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:branches,code'.($this->branch?->id ? ','.$this->branch->id : '')],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_24_hours' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['nullable', 'url', 'max:255'],
            'photo_uploads' => ['nullable', 'array', 'max:8'],
            'photo_uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['nullable', 'boolean'],
            'opening_hours' => ['nullable', 'array'],
            'opening_hours.*.open' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
            'opening_hours.*.close' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}$/'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $photoLinks = array_filter((array) $this->input('photos', []));
            $uploadedPhotos = $this->file('photo_uploads', []);

            if (count($photoLinks) + count($uploadedPhotos) > 8) {
                $validator->errors()->add('photo_uploads', 'Use no more than 8 photos total for a station.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => 'You can save up to 8 photos.',
            'photo_uploads.max' => 'You can upload up to 8 station photos at a time.',
            'code.unique' => 'That station code is already taken.',
        ];
    }

    /**
     * The station being edited, or null when creating a new one.
     */
    public function branch(): ?Branch
    {
        return $this->route('branch');
    }

    /**
     * Split the newline separated photo textarea into a validated array and
     * drop the blank lines the operator left behind.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('photos')) {
            return;
        }

        $raw = $this->input('photos');

        $photos = is_array($raw)
            ? $raw
            : (preg_split('/\R/', (string) $raw) ?: []);

        $this->merge([
            'photos' => array_values(array_filter(
                array_map(static fn ($url): string => trim((string) $url), $photos),
                static fn (string $url): bool => $url !== '',
            )),
        ]);
    }
}
