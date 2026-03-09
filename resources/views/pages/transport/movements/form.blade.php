@csrf

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('app.roles.transport.movements.fields.driver') }}</label>
        <select class="form-select" name="driver_id" required>
            <option value="">{{ __('app.roles.transport.movements.fields.driver_placeholder') }}</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" @selected(old('driver_id', $movementDay->driver_id ?? null) == $driver->id)>{{ $driver->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('app.roles.transport.movements.fields.date') }}</label>
        <input class="form-control" type="date" name="date" value="{{ old('date', optional($movementDay->date ?? null)->format('Y-m-d')) }}" required>
    </div>
    <div class="col-12 col-md-4">
        <label class="form-label">{{ __('app.roles.transport.movements.fields.notes') }}</label>
        <input class="form-control" name="notes" value="{{ old('notes', $movementDay->notes ?? '') }}">
    </div>

    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 mb-0">{{ __('app.roles.transport.movements.trips_title') }}</h2>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-trip">{{ __('app.roles.transport.movements.actions.add_trip') }}</button>
        </div>
        <div id="trips-wrapper" class="d-flex flex-column gap-3"></div>
    </div>

    <div class="col-12 d-flex justify-content-end">
        <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
    </div>
</div>

<template id="trip-template">
    <div class="border rounded p-3 trip-item">
        <div class="row g-2">
            <div class="col-12 col-md-3">
                <label class="form-label">{{ __('app.roles.transport.movements.fields.vehicle') }}</label>
                <select class="form-select" data-name="vehicle_id">
                    <option value="">{{ __('app.roles.transport.movements.fields.vehicle_placeholder') }}</option>
                    @foreach ($vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no ?? $vehicle->plate_no }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label">{{ __('app.roles.transport.movements.fields.destination') }}</label>
                <input class="form-control" data-name="destination" required>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">{{ __('app.roles.transport.movements.fields.team') }}</label>
                <input class="form-control" data-name="team">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">{{ __('app.roles.transport.movements.fields.departure_time') }}</label>
                <input class="form-control" type="time" data-name="departure_time">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">{{ __('app.roles.transport.movements.fields.return_time') }}</label>
                <input class="form-control" type="time" data-name="return_time">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" class="btn btn-sm btn-outline-danger remove-trip">{{ __('app.roles.transport.movements.actions.remove_trip') }}</button>
            </div>
        </div>
    </div>
</template>

@php
    $oldTrips = old('trips');
    $initialTrips = is_array($oldTrips) ? $oldTrips : collect($movementDay?->trips ?? [])->map(fn ($trip) => is_array($trip) ? $trip : [
        'vehicle_id' => $trip->vehicle_id,
        'destination' => $trip->destination,
        'team' => $trip->team,
        'departure_time' => $trip->departure_time,
        'return_time' => $trip->return_time,
    ])->values()->all();

    if (empty($initialTrips)) {
        $initialTrips = [[]];
    }
@endphp

@push('scripts')
<script>
    (() => {
        const wrapper = document.getElementById('trips-wrapper');
        const template = document.getElementById('trip-template');
        const addBtn = document.getElementById('add-trip');
        const trips = @json($initialTrips);

        const reindexTrips = () => {
            wrapper.querySelectorAll('.trip-item').forEach((item, index) => {
                item.querySelectorAll('[data-name]').forEach((field) => {
                    const key = field.getAttribute('data-name');
                    field.name = `trips[${index}][${key}]`;
                });
            });
        };

        const addTripRow = (trip = {}) => {
            const node = template.content.cloneNode(true);
            const item = node.querySelector('.trip-item');

            item.querySelectorAll('[data-name]').forEach((field) => {
                const key = field.getAttribute('data-name');
                if (trip[key] !== undefined && trip[key] !== null) {
                    field.value = trip[key];
                }
            });

            item.querySelector('.remove-trip').addEventListener('click', () => {
                item.remove();
                if (!wrapper.querySelector('.trip-item')) {
                    addTripRow();
                    return;
                }

                reindexTrips();
            });

            wrapper.appendChild(node);
            reindexTrips();
        };

        addBtn.addEventListener('click', () => addTripRow());

        const normalizedTrips = Array.isArray(trips) ? trips : Object.values(trips || {});

        if (normalizedTrips.length) {
            normalizedTrips.forEach((trip) => addTripRow(trip));
        } else {
            addTripRow();
        }
    })();
</script>
@endpush
