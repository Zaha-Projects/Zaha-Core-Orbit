@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.transport.drivers.create_title');
    $subtitle = __('app.roles.transport.drivers.subtitle');
@endphp


@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
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
@endsection
