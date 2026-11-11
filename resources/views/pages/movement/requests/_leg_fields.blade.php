{{-- Leg inputs. Params: $type ('outbound'|'return'), $leg (MovementRequestLeg|null), $locations --}}
@php
    $p = "legs.$type";
    $local = fn ($value) => $value ? \App\Modules\Movement\Support\MovementTime::toLocal($value)->format('Y-m-d H:i') : null;
@endphp
<fieldset class="border rounded p-3 mb-3" data-leg="{{ $type }}">
    <legend class="float-none w-auto px-2 h6 mb-0">{{ __('movement.requests.'.$type) }}</legend>
    <div class="row g-3">
        @foreach(['origin', 'destination'] as $end)
            <div class="col-12 col-md-6">
                <label class="form-label" for="{{ $type }}-{{ $end }}-location">{{ __('movement.requests.'.$end) }} — {{ __('movement.requests.saved_location') }}</label>
                <select class="form-select" id="{{ $type }}-{{ $end }}-location" name="legs[{{ $type }}][{{ $end }}_location_id]">
                    <option value="">—</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" {{ ((int) old("$p.{$end}_location_id", $leg?->{$end.'_location_id'}) === $location->id) ? 'selected' : '' }}>{{ $location->display_name }}</option>
                    @endforeach
                </select>
                <label class="form-label mt-2 small text-muted" for="{{ $type }}-{{ $end }}-text">{{ __('movement.requests.custom_place') }}</label>
                <input class="form-control" id="{{ $type }}-{{ $end }}-text" name="legs[{{ $type }}][{{ $end }}_text]" value="{{ old("$p.{$end}_text", $leg?->{$end.'_text'}) }}" maxlength="255">
                <input class="form-control mt-2" type="url" name="legs[{{ $type }}][{{ $end }}_maps_url]" value="{{ old("$p.{$end}_maps_url", $leg?->{$end.'_maps_url'}) }}" maxlength="2000" dir="ltr" placeholder="{{ __('movement.requests.maps_url') }}" aria-label="{{ __('movement.requests.maps_url') }}">
            </div>
        @endforeach
        <div class="col-6 col-md-3">
            <label class="form-label" for="{{ $type }}-passengers">{{ __('movement.requests.passengers') }}</label>
            <input class="form-control" id="{{ $type }}-passengers" type="number" min="1" max="1000" name="legs[{{ $type }}][passenger_count]" value="{{ old("$p.passenger_count", $leg?->passenger_count) }}" data-availability-passengers="{{ $type }}">
        </div>
        <div class="col-6 col-md-4">
            <label class="form-label" for="{{ $type }}-arrival">{{ __('movement.requests.required_arrival_at') }}</label>
            <input class="form-control" id="{{ $type }}-arrival" name="legs[{{ $type }}][required_arrival_at]" value="{{ old("$p.required_arrival_at", $local($leg?->required_arrival_at)) }}" data-movement-datetime data-availability-arrival="{{ $type }}" autocomplete="off">
        </div>
        <div class="col-12 col-md-5">
            <label class="form-label" for="{{ $type }}-departure">{{ __('movement.requests.preferred_departure_at') }}</label>
            <input class="form-control" id="{{ $type }}-departure" name="legs[{{ $type }}][preferred_departure_at]" value="{{ old("$p.preferred_departure_at", $local($leg?->preferred_departure_at)) }}" data-movement-datetime autocomplete="off">
        </div>
        <div class="col-12">
            <label class="form-label" for="{{ $type }}-notes">{{ __('movement.requests.notes') }}</label>
            <input class="form-control" id="{{ $type }}-notes" name="legs[{{ $type }}][notes]" value="{{ old("$p.notes", $leg?->notes) }}" maxlength="2000">
        </div>
    </div>
</fieldset>
