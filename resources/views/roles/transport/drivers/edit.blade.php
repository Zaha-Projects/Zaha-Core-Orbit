@extends('layouts.app')

@php
    $title = __('app.roles.transport.drivers.edit_title');
    $subtitle = __('app.roles.transport.drivers.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.transport.drivers.update', $driver) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.drivers.fields.name') }}</label>
                    <input class="form-control" name="name" value="{{ $driver->name }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.drivers.fields.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ $driver->phone }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.drivers.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="active" @selected($driver->status === 'active')>{{ __('app.roles.transport.drivers.statuses.active') }}</option>
                        <option value="inactive" @selected($driver->status === 'inactive')>{{ __('app.roles.transport.drivers.statuses.inactive') }}</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.transport.drivers.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
