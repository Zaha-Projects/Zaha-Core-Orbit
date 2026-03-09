@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">{{ __('app.roles.transport.movements.show_title') }}</h1>
            <p><strong>{{ __('app.roles.transport.movements.fields.date') }}:</strong> {{ optional($movementDay->date)->format('Y-m-d') }}</p>
            <p><strong>{{ __('app.roles.transport.movements.fields.driver') }}:</strong> {{ $movementDay->driver?->name ?? '-' }}</p>
            <p><strong>{{ __('app.roles.transport.movements.fields.notes') }}:</strong> {{ $movementDay->notes ?: '-' }}</p>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.transport.movements.fields.vehicle') }}</th>
                            <th>{{ __('app.roles.transport.movements.fields.destination') }}</th>
                            <th>{{ __('app.roles.transport.movements.fields.team') }}</th>
                            <th>{{ __('app.roles.transport.movements.fields.departure_time') }}</th>
                            <th>{{ __('app.roles.transport.movements.fields.return_time') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movementDay->trips as $trip)
                            <tr>
                                <td>{{ $trip->vehicle?->vehicle_no ?? $trip->vehicle?->plate_no ?? '-' }}</td>
                                <td>{{ $trip->destination }}</td>
                                <td>{{ $trip->team ?: '-' }}</td>
                                <td>{{ $trip->departure_time ?: '-' }}</td>
                                <td>{{ $trip->return_time ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
