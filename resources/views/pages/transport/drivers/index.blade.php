@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.transport.drivers.title');
    $subtitle = __('app.roles.transport.drivers.subtitle');
@endphp


@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.drivers.create_title') }}</h2>
            <form method="POST" action="{{ route('role.transport.drivers.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.drivers.fields.name') }}</label>
                    <input class="form-control" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.drivers.fields.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.drivers.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="active">{{ __('app.roles.transport.drivers.statuses.active') }}</option>
                        <option value="inactive">{{ __('app.roles.transport.drivers.statuses.inactive') }}</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.transport.drivers.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.drivers.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.transport.drivers.table.name') }}</th>
                            <th>{{ __('app.roles.transport.drivers.table.status') }}</th>
                            <th class="text-end">{{ __('app.roles.transport.drivers.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($drivers as $driver)
                            <tr>
                                <td>{{ $driver->name }}</td>
                                <td>{{ $driver->status }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.transport.drivers.edit', $driver) }}">
                                        {{ __('app.roles.transport.drivers.actions.edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('app.roles.transport.drivers.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
