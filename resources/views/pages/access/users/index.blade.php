@extends('layouts.app')

@php
    $title = __('app.roles.super_admin.users.title');
    $subtitle = __('app.roles.super_admin.users.subtitle');
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
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.users.create_title') }}</h2>
                    <form method="POST" action="{{ route('role.super_admin.users.store') }}" class="row g-3">
                        @csrf
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.name') }}</label>
                            <input class="form-control" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.email') }}</label>
                            <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.phone') }}</label>
                            <input class="form-control" name="phone" value="{{ old('phone') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.password') }}</label>
                            <input class="form-control" type="password" name="password" required>
                            @error('password')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.branch') }}</label>
                            <select class="form-select" name="branch_id">
                                <option value="">{{ __('app.roles.super_admin.users.fields.branch_placeholder') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.center') }}</label>
                            <select class="form-select" name="center_id">
                                <option value="">{{ __('app.roles.super_admin.users.fields.center_placeholder') }}</option>
                                @foreach ($centers as $center)
                                    <option value="{{ $center->id }}">{{ $center->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.role') }}</label>
                            <select class="form-select" name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.status') }}</label>
                            <select class="form-select" name="status" required>
                                <option value="active">{{ __('app.roles.super_admin.users.status.active') }}</option>
                                <option value="inactive">{{ __('app.roles.super_admin.users.status.inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">
                                {{ __('app.roles.super_admin.users.actions.create') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('app.roles.super_admin.users.list_title') }}</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('app.roles.super_admin.users.table.name') }}</th>
                                    <th>{{ __('app.roles.super_admin.users.table.email') }}</th>
                                    <th>{{ __('app.roles.super_admin.users.table.branch') }}</th>
                                    <th>{{ __('app.roles.super_admin.users.table.center') }}</th>
                                    <th>{{ __('app.roles.super_admin.users.table.role') }}</th>
                                    <th>{{ __('app.roles.super_admin.users.table.status') }}</th>
                                    <th class="text-end">{{ __('app.roles.super_admin.users.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->branch?->name ?? __('app.roles.super_admin.users.table.unassigned') }}</td>
                                        <td>{{ $user->center?->name ?? __('app.roles.super_admin.users.table.unassigned') }}</td>
                                        <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                                        <td>{{ $user->status }}</td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#edit-user-{{ $user->id }}">
                                                {{ __('app.roles.super_admin.users.actions.edit') }}
                                            </button>
                                            <form class="d-inline" method="POST" action="{{ route('role.super_admin.users.destroy', $user) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    {{ __('app.roles.super_admin.users.actions.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr class="collapse" id="edit-user-{{ $user->id }}">
                                        <td colspan="7">
                                            <form method="POST" action="{{ route('role.super_admin.users.update', $user) }}" class="row g-3">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.name') }}</label>
                                                    <input class="form-control" name="name" value="{{ $user->name }}" required>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.email') }}</label>
                                                    <input class="form-control" type="email" name="email" value="{{ $user->email }}" required>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.phone') }}</label>
                                                    <input class="form-control" name="phone" value="{{ $user->phone }}">
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.branch') }}</label>
                                                    <select class="form-select" name="branch_id">
                                                        <option value="">{{ __('app.roles.super_admin.users.fields.branch_placeholder') }}</option>
                                                        @foreach ($branches as $branch)
                                                            <option value="{{ $branch->id }}" @selected($user->branch_id === $branch->id)>
                                                                {{ $branch->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.center') }}</label>
                                                    <select class="form-select" name="center_id">
                                                        <option value="">{{ __('app.roles.super_admin.users.fields.center_placeholder') }}</option>
                                                        @foreach ($centers as $center)
                                                            <option value="{{ $center->id }}" @selected($user->center_id === $center->id)>
                                                                {{ $center->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.role') }}</label>
                                                    <select class="form-select" name="role" required>
                                                        @foreach ($roles as $role)
                                                            <option value="{{ $role->name }}" @selected($user->roles->contains('name', $role->name))>
                                                                {{ $role->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.status') }}</label>
                                                    <select class="form-select" name="status" required>
                                                        <option value="active" @selected($user->status === 'active')>
                                                            {{ __('app.roles.super_admin.users.status.active') }}
                                                        </option>
                                                        <option value="inactive" @selected($user->status === 'inactive')>
                                                            {{ __('app.roles.super_admin.users.status.inactive') }}
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">{{ __('app.roles.super_admin.users.fields.password_optional') }}</label>
                                                    <input class="form-control" type="password" name="password">
                                                </div>
                                                <div class="col-12 d-flex justify-content-end">
                                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                                        {{ __('app.roles.super_admin.users.actions.save') }}
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">{{ __('app.roles.super_admin.users.table.empty') }}</td>
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
