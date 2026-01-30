@extends('layouts.app')

@php
    $title = __('app.roles.super_admin.roles.title');
    $subtitle = __('app.roles.super_admin.roles.subtitle');
@endphp

@section('content')
    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('roles.super_admin.partials.sidebar')
        </div>
        <div class="col-12 col-lg-9">
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
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.roles.create_title') }}</h2>
                    <form method="POST" action="{{ route('role.super_admin.roles.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.roles.fields.name') }}</label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">
                                {{ __('app.roles.super_admin.roles.actions.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.roles.permissions_title') }}</h2>
                    <div class="accordion" id="rolesPermissions">
                        @forelse ($roles as $role)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-{{ $role->id }}">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#role-{{ $role->id }}">
                                        {{ $role->name }}
                                    </button>
                                </h2>
                                <div id="role-{{ $role->id }}" class="accordion-collapse collapse" data-bs-parent="#rolesPermissions">
                                    <div class="accordion-body">
                                        <form method="POST" action="{{ route('role.super_admin.roles.update', $role) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="row g-3">
                                                @forelse ($permissions as $permission)
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <div class="form-check">
                                                            <input class="form-check-input"
                                                                type="checkbox"
                                                                name="permissions[]"
                                                                id="perm-{{ $role->id }}-{{ $permission->id }}"
                                                                value="{{ $permission->name }}"
                                                                {{ $role->hasPermissionTo($permission) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="perm-{{ $role->id }}-{{ $permission->id }}">
                                                                {{ $permission->name }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-muted mb-0">{{ __('app.roles.super_admin.roles.no_permissions') }}</p>
                                                @endforelse
                                            </div>
                                            <div class="mt-3 d-flex justify-content-end">
                                                <button class="btn btn-outline-primary btn-sm" type="submit">
                                                    {{ __('app.roles.super_admin.roles.actions.save') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">{{ __('app.roles.super_admin.roles.no_roles') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
