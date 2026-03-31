@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $title = __('app.roles.super_admin.roles.title');
    $subtitle = __('app.roles.super_admin.roles.subtitle');
    $translatePermission = function ($permission) {
        $name = is_string($permission) ? $permission : (string) $permission->name;
        $ar = is_string($permission) ? null : $permission->name_ar;
        $en = is_string($permission) ? null : $permission->name_en;

        return app()->getLocale() === 'ar' ? ($ar ?: $name) : ($en ?: $name);
    };
@endphp

@section('content')
<div class="row g-4"><div class="col-12">
    <div class="card shadow-sm mb-4"><div class="card-body"><h1 class="h4 mb-2">{{ $title }}</h1><p class="text-muted mb-0">{{ $subtitle }}</p></div></div>
    @if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card shadow-sm mb-4"><div class="card-body">
        <h2 class="h6 mb-3">{{ __('Create Role / إنشاء دور') }}</h2>
        <form method="POST" action="{{ route('role.super_admin.roles.store') }}" class="row g-2">@csrf
            <div class="col-md-3"><input class="form-control" name="name" placeholder="system_name" required></div>
            <div class="col-md-3"><input class="form-control" name="name_ar" placeholder="الاسم بالعربية"></div>
            <div class="col-md-3"><input class="form-control" name="name_en" placeholder="Name in English"></div>
            <div class="col-md-3 text-end"><button class="btn btn-primary">{{ __('Create') }}</button></div>

            <div class="col-12"><label class="form-label">{{ __('Permissions') }}</label>
                <div class="row g-2">
                    @foreach ($permissions as $module => $modulePermissions)
                        <div class="col-md-4"><div class="border rounded p-2"><div class="fw-semibold small mb-1">{{ Str::headline((string) $module) }}</div>
                            @foreach ($modulePermissions as $permission)
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="new-perm-{{ $permission->id }}"><label class="form-check-label small" for="new-perm-{{ $permission->id }}">{{ $translatePermission($permission) }}</label></div>
                            @endforeach
                        </div></div>
                    @endforeach
                </div>
            </div>
        </form>
    </div></div>

    @foreach ($roles as $role)
        <div class="card shadow-sm mb-3"><div class="card-body">
            <form method="POST" action="{{ route('role.super_admin.roles.update', $role) }}">@csrf @method('PUT')
                <div class="row g-2 mb-2">
                    <div class="col-md-3"><input class="form-control" name="name" value="{{ $role->name }}" required></div>
                    <div class="col-md-3"><input class="form-control" name="name_ar" value="{{ $role->name_ar }}"></div>
                    <div class="col-md-3"><input class="form-control" name="name_en" value="{{ $role->name_en }}"></div>
                    <div class="col-md-3 text-end"><button class="btn btn-outline-primary btn-sm">{{ __('Save') }}</button></div>
                </div>
                <div class="text-muted small mb-2">{{ $role->display_name }}</div>
                <div class="row g-2">
                    @foreach ($permissions as $module => $modulePermissions)
                        <div class="col-md-4"><div class="border rounded p-2"><div class="fw-semibold small mb-1">{{ Str::headline((string) $module) }}</div>
                            @foreach ($modulePermissions as $permission)
                                <div class="form-check"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{ $role->id }}-{{ $permission->id }}" @checked($role->hasPermissionTo($permission))><label class="form-check-label small" for="perm-{{ $role->id }}-{{ $permission->id }}">{{ $translatePermission($permission) }}</label></div>
                            @endforeach
                        </div></div>
                    @endforeach
                </div>
            </form>
            @if($role->name !== 'super_admin')
                <form method="POST" action="{{ route('role.super_admin.roles.destroy', $role) }}" class="text-end mt-2">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">{{ __('Delete role / حذف الدور') }}</button></form>
            @endif
        </div></div>
    @endforeach
</div></div>
@endsection
