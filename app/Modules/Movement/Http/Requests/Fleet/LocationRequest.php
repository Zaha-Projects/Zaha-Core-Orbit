<?php

namespace App\Modules\Movement\Http\Requests\Fleet;

use App\Modules\Movement\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Saved place (plan §7.5, screen 2b). A pasted Google Maps link fills empty coordinates. */
class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $location = $this->route('location');

        return $location ? $this->user()->can('update', $location) : $this->user()->can('create', Location::class);
    }

    protected function prepareForValidation(): void
    {
        $merge = ['is_active' => $this->boolean('is_active')];

        if (blank($this->input('latitude')) && blank($this->input('longitude'))
            && ($coordinates = Location::coordinatesFromMapsUrl($this->input('maps_url')))) {
            [$merge['latitude'], $merge['longitude']] = $coordinates;
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['nullable', 'required_without:name_en', 'string', 'max:255'],
            'name_en' => ['nullable', 'required_without:name_ar', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:2000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'google_place_id' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'is_active' => ['boolean'],
        ];
    }
}
