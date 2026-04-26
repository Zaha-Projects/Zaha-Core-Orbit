@extends('layouts.new-theme-dashboard')

@section('theme_sidebar_links')
    <li class="side-item {{ request()->routeIs('role.super_admin.roles*') ? 'selected' : '' }}">
        <a href="{{ route('role.super_admin.roles') }}"><i class="fas fa-user-shield"></i><span>{{ __('app.roles.super_admin.roles.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.super_admin.users*') ? 'selected' : '' }}">
        <a href="{{ route('role.super_admin.users') }}"><i class="fas fa-users"></i><span>{{ __('app.roles.super_admin.users.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.super_admin.workflows*') ? 'selected' : '' }}">
        <a href="{{ route('role.super_admin.workflows') }}"><i class="fas fa-diagram-project"></i><span>{{ __('app.roles.super_admin.workflows.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.super_admin.branches*') ? 'selected' : '' }}">
        <a href="{{ route('role.super_admin.branches') }}"><i class="fas fa-building"></i><span>{{ __('app.roles.super_admin.branches.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.super_admin.approvals*') ? 'selected' : '' }}">
        <a href="{{ route('role.super_admin.approvals') }}"><i class="fas fa-list-check"></i><span>{{ __('app.roles.super_admin.approvals.title') }}</span></a>
    </li>
@endsection

@php
    $title = __('app.roles.super_admin.branches.title');
    $subtitle = __('app.roles.super_admin.branches.subtitle');
@endphp


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
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.branches.create_title') }}</h2>
                    <form method="POST" action="{{ route('role.super_admin.branches.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.branches.fields.name') }}</label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.branches.fields.city') }}</label>
                            <input class="form-control" name="city" value="{{ old('city') }}">
                            @error('city')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('app.roles.super_admin.branches.fields.address') }}</label>
                            <input class="form-control" name="address" value="{{ old('address') }}">
                            @error('address')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">اللون</label>
                            <input class="form-control form-control-color" type="color" name="color_hex" value="{{ old('color_hex', '#2563EB') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">الأيقونة</label>
                            <input class="form-control" name="icon" value="{{ old('icon', '📍') }}" maxlength="32">
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">
                                {{ __('app.roles.super_admin.branches.actions.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.branches.list_title') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('app.roles.super_admin.branches.table.name') }}</th>
                                    <th>{{ __('app.roles.super_admin.branches.table.city') }}</th>
                                    <th>اللون/الأيقونة</th>
                                    <th>{{ __('app.roles.super_admin.branches.table.address') }}</th>
                                    <th class="text-end">{{ __('app.roles.super_admin.branches.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($branches as $branch)
                                    <tr>
                                        <td>{{ $branch->name }}</td>
                                        <td>{{ $branch->city ?? __('app.roles.super_admin.branches.table.unassigned') }}</td>
                                        <td><span>{{ $branch->icon ?? '📍' }}</span> <span class="d-inline-block rounded-circle align-middle" style="width:12px;height:12px;background:{{ $branch->color_hex ?? '#2563EB' }}"></span></td>
                                        <td>{{ $branch->address ?? __('app.roles.super_admin.branches.table.unassigned') }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-branch-{{ $branch->id }}">
                                                {{ __('app.roles.super_admin.branches.actions.edit') }}
                                            </button>
                                            <form class="d-inline" method="POST" action="{{ route('role.super_admin.branches.destroy', $branch) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    {{ __('app.roles.super_admin.branches.actions.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="edit-branch-{{ $branch->id }}">
                                        <td colspan="5">
                                            <form method="POST" action="{{ route('role.super_admin.branches.update', $branch) }}" class="row g-3">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">{{ __('app.roles.super_admin.branches.fields.name') }}</label>
                                                    <input class="form-control" name="name" value="{{ $branch->name }}" required>
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">{{ __('app.roles.super_admin.branches.fields.city') }}</label>
                                                    <input class="form-control" name="city" value="{{ $branch->city }}">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('app.roles.super_admin.branches.fields.address') }}</label>
                                                    <input class="form-control" name="address" value="{{ $branch->address }}">
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">اللون</label>
                                                    <input class="form-control form-control-color" type="color" name="color_hex" value="{{ $branch->color_hex ?? '#2563EB' }}">
                                                </div>
                                                <div class="col-12 col-md-6">
                                                    <label class="form-label">الأيقونة</label>
                                                    <input class="form-control" name="icon" value="{{ $branch->icon ?? '📍' }}" maxlength="32">
                                                </div>
                                                <div class="col-12 d-flex justify-content-end">
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                                        {{ __('app.roles.super_admin.branches.actions.save') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">{{ __('app.roles.super_admin.branches.table.empty') }}</td>
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
