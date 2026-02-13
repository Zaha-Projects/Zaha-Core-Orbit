@extends('layouts.app')

@php
    $title = __('app.roles.super_admin.departments.title');
    $subtitle = __('app.roles.super_admin.departments.subtitle');
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
                    <form method="GET" action="{{ route('role.super_admin.departments') }}" class="row g-3 align-items-end">
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.departments.filters.search') }}</label>
                            <input class="form-control" name="search" value="{{ $search ?? '' }}" placeholder="{{ __('app.roles.super_admin.departments.filters.search_placeholder') }}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">{{ __('app.roles.super_admin.departments.filters.sort') }}</label>
                            <select class="form-select" name="sort">
                                <option value="name_asc" @selected(($sort ?? 'name_asc') === 'name_asc')>{{ __('app.roles.super_admin.departments.filters.sort_name_asc') }}</option>
                                <option value="name_desc" @selected(($sort ?? 'name_asc') === 'name_desc')>{{ __('app.roles.super_admin.departments.filters.sort_name_desc') }}</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex justify-content-end">
                            <button class="btn btn-outline-primary w-100" type="submit">{{ __('app.roles.super_admin.departments.filters.apply') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.departments.create_title') }}</h2>
                    <form method="POST" action="{{ route('role.super_admin.departments.store') }}" class="row g-3">
                        <input type="hidden" name="search" value="{{ $search ?? '' }}">
                        <input type="hidden" name="sort" value="{{ $sort ?? 'name_asc' }}">
                        @csrf
                        <div class="col-12 col-md-8">
                            <label class="form-label">{{ __('app.roles.super_admin.departments.fields.name') }}</label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-4 d-flex align-items-end justify-content-end">
                            <button class="btn btn-primary" type="submit">
                                {{ __('app.roles.super_admin.departments.actions.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.departments.list_title') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('app.roles.super_admin.departments.table.name') }}</th>
                                    <th class="text-end">{{ __('app.roles.super_admin.departments.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($departments as $department)
                                    <tr>
                                        <td>{{ $department->name }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-department-{{ $department->id }}">
                                                {{ __('app.roles.super_admin.departments.actions.edit') }}
                                            </button>
                                            <form class="d-inline" method="POST" action="{{ route('role.super_admin.departments.destroy', [$department, 'search' => $search ?? '', 'sort' => $sort ?? 'name_asc']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    {{ __('app.roles.super_admin.departments.actions.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="edit-department-{{ $department->id }}">
                                        <td colspan="2">
                                            <form method="POST" action="{{ route('role.super_admin.departments.update', [$department, 'search' => $search ?? '', 'sort' => $sort ?? 'name_asc']) }}" class="row g-3">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                                                <input type="hidden" name="sort" value="{{ $sort ?? 'name_asc' }}">
                                                <div class="col-12 col-md-8">
                                                    <label class="form-label">{{ __('app.roles.super_admin.departments.fields.name') }}</label>
                                                    <input class="form-control" name="name" value="{{ $department->name }}" required>
                                                </div>
                                                <div class="col-12 col-md-4 d-flex align-items-end justify-content-end">
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                                        {{ __('app.roles.super_admin.departments.actions.save') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-muted">{{ __('app.roles.super_admin.departments.table.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
