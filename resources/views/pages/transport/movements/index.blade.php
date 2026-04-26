@extends('layouts.app')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                <h1 class="h4 mb-0">{{ __('app.roles.transport.movements.title') }}</h1>
                <a href="{{ route('role.transport.movements.create') }}" class="btn btn-primary">{{ __('app.roles.transport.movements.actions.create') }}</a>
            </div>
            <form method="GET" class="row g-2">
                <div class="col-12 col-md-3"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('app.roles.transport.movements.filters.search') }}"></div>
                <div class="col-6 col-md-3"><input class="form-control" type="date" name="from_date" value="{{ request('from_date') }}"></div>
                <div class="col-6 col-md-3"><input class="form-control" type="date" name="to_date" value="{{ request('to_date') }}"></div>
                <div class="col-12 col-md-2">
                    <select class="form-select" name="driver_id">
                        <option value="">{{ __('app.roles.transport.movements.filters.driver') }}</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected((string)request('driver_id') === (string)$driver->id)>{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-1"><button class="btn btn-outline-secondary w-100">{{ __('app.roles.transport.movements.actions.filter') }}</button></div>
            </form>
        </div>
    </div>

    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.transport.movements.table.date') }}</th>
                            <th>{{ __('app.roles.transport.movements.table.driver') }}</th>
                            <th>{{ __('app.roles.transport.movements.table.trips_count') }}</th>
                            <th>{{ __('app.roles.transport.movements.table.notes') }}</th>
                            <th class="text-end">{{ __('app.roles.transport.movements.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movementDays as $day)
                            <tr>
                                <td>{{ optional($day->date)->format('Y-m-d') }}</td>
                                <td>{{ $day->driver?->name ?? '-' }}</td>
                                <td>{{ $day->trips->count() }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($day->notes, 60) ?: '-' }}</td>
                                <td class="text-end d-flex justify-content-end gap-1">
                                    <a href="{{ route('role.transport.movements.show', $day) }}" class="btn btn-sm btn-outline-info">{{ __('app.roles.transport.movements.actions.view') }}</a>
                                    <a href="{{ route('role.transport.movements.edit', $day) }}" class="btn btn-sm btn-outline-secondary">{{ __('app.roles.transport.movements.actions.edit') }}</a>
                                    <form method="POST" action="{{ route('role.transport.movements.destroy', $day) }}" onsubmit="return confirm('{{ __('app.roles.transport.movements.messages.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">{{ __('app.roles.transport.movements.actions.delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">{{ __('app.roles.transport.movements.table.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movementDays->links() }}
        </div>
    </div>
@endsection
