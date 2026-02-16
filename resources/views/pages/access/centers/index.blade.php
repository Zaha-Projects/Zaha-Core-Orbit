@extends('layouts.app')

@php
    $title = __('app.roles.super_admin.centers.title');
    $subtitle = __('app.roles.super_admin.centers.subtitle');
@endphp

@section('sidebar')
    @include('pages.access.partials.sidebar')
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-12">
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
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.centers.create_title') }}</h2>
                    <form method="POST" action="{{ route('role.super_admin.centers.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.centers.fields.branch') }}</label>
                            <select class="form-select" name="branch_id" required>
                                <option value="">{{ __('app.roles.super_admin.centers.fields.branch_placeholder') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.centers.fields.name') }}</label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">
                                {{ __('app.roles.super_admin.centers.actions.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.centers.list_title') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('app.roles.super_admin.centers.table.name') }}</th>
                                    <th>{{ __('app.roles.super_admin.centers.table.branch') }}</th>
                                    <th class="text-end">{{ __('app.roles.super_admin.centers.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($centers as $center)
                                    <tr>
                                        <td>{{ $center->name }}</td>
                                        <td>{{ $center->branch?->name ?? __('app.roles.super_admin.centers.table.unassigned') }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-center-{{ $center->id }}">
                                                {{ __('app.roles.super_admin.centers.actions.edit') }}
                                            </button>
                                            <form class="d-inline" method="POST" action="{{ route('role.super_admin.centers.destroy', $center) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    {{ __('app.roles.super_admin.centers.actions.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="edit-center-{{ $center->id }}">
                                        <td colspan="3">
                                            <form method="POST" action="{{ route('role.super_admin.centers.update', $center) }}" class="row g-3">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">{{ __('app.roles.super_admin.centers.fields.branch') }}</label>
                                                    <select class="form-select" name="branch_id" required>
                                                        <option value="">{{ __('app.roles.super_admin.centers.fields.branch_placeholder') }}</option>
                                                        @foreach ($branches as $branch)
                                                            <option value="{{ $branch->id }}" @selected($center->branch_id === $branch->id)>
                                                                {{ $branch->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">{{ __('app.roles.super_admin.centers.fields.name') }}</label>
                                                    <input class="form-control" name="name" value="{{ $center->name }}" required>
                                                </div>
                                                <div class="col-12 d-flex justify-content-end">
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                                        {{ __('app.roles.super_admin.centers.actions.save') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-muted">{{ __('app.roles.super_admin.centers.table.empty') }}</td>
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
