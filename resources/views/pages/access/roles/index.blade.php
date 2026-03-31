@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $title = __('app.roles.super_admin.roles.title');
    $subtitle = __('app.roles.super_admin.roles.subtitle');
    $permissionsByModule = $permissions;
    $translateModule = function (?string $module) {
        $moduleKey = (string) $module;
        $translated = __('app.acl.modules.' . $moduleKey);

        return $translated !== 'app.acl.modules.' . $moduleKey
            ? $translated
            : Str::headline($moduleKey);
    };
    $translatePermission = function ($permission) {
        $name = is_string($permission) ? $permission : (string) $permission->name;
        $ar = is_string($permission) ? null : $permission->name_ar;
        $en = is_string($permission) ? null : $permission->name_en;

        $label = app()->getLocale() === 'ar' ? ($ar ?: $name) : ($en ?: $name);

        return trim(preg_replace('/[\r\n\t]+/u', ' ', (string) $label));
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

            <div class="col-12">
                <label class="form-label">{{ __('Permissions') }}</label>
                <div class="accordion" id="create-role-permissions-modules">
                    @foreach ($permissionsByModule as $module => $modulePermissions)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="create-role-module-heading-{{ $loop->index }}">
                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#create-role-module-body-{{ $loop->index }}" aria-expanded="false" aria-controls="create-role-module-body-{{ $loop->index }}">
                                    {{ $translateModule($module) }}
                                </button>
                            </h2>
                            <div id="create-role-module-body-{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="create-role-module-heading-{{ $loop->index }}" data-bs-parent="#create-role-permissions-modules">
                                <div class="accordion-body pt-2">
                                    @foreach ($modulePermissions as $permission)
                                        <div class="form-check form-switch mb-1">
                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="new-perm-{{ $permission->id }}">
                                            <label class="form-check-label small" for="new-perm-{{ $permission->id }}">{{ $translatePermission($permission) }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>
    </div></div>

    @foreach ($roles as $role)
        @php($rolePermissionNames = $role->permissions->pluck('name')->toArray())
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-transparent">
                <button class="btn w-100 text-start d-flex justify-content-between align-items-center p-0" type="button" data-bs-toggle="collapse" data-bs-target="#role-card-{{ $role->id }}" aria-expanded="false" aria-controls="role-card-{{ $role->id }}">
                    <span>
                        <span class="fw-semibold">{{ $role->display_name }}</span>
                        <span class="text-muted small d-block">{{ $role->name }}</span>
                    </span>
                    <span class="badge bg-light text-dark">{{ count($rolePermissionNames) }} {{ __('Permissions') }}</span>
                </button>
            </div>
            <div class="collapse" id="role-card-{{ $role->id }}">
                <div class="card-body">
                    <form method="POST" action="{{ route('role.super_admin.roles.update', $role) }}">@csrf @method('PUT')
                        <div class="row g-2 mb-2">
                            <div class="col-md-3"><input class="form-control" name="name" value="{{ $role->name }}" required></div>
                            <div class="col-md-3"><input class="form-control" name="name_ar" value="{{ $role->name_ar }}"></div>
                            <div class="col-md-3"><input class="form-control" name="name_en" value="{{ $role->name_en }}"></div>
                            <div class="col-md-3 text-end"><button class="btn btn-outline-primary btn-sm">{{ __('Save') }}</button></div>
                        </div>
                        <div class="accordion" id="role-permissions-modules-{{ $role->id }}">
                            @foreach ($permissionsByModule as $module => $modulePermissions)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="role-{{ $role->id }}-module-heading-{{ $loop->index }}">
                                        <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#role-{{ $role->id }}-module-body-{{ $loop->index }}" aria-expanded="false" aria-controls="role-{{ $role->id }}-module-body-{{ $loop->index }}">
                                            {{ $translateModule($module) }}
                                        </button>
                                    </h2>
                                    <div id="role-{{ $role->id }}-module-body-{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="role-{{ $role->id }}-module-heading-{{ $loop->index }}" data-bs-parent="#role-permissions-modules-{{ $role->id }}">
                                        <div class="accordion-body pt-2">
                                            @foreach ($modulePermissions as $permission)
                                                <div class="form-check form-switch mb-1">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm-{{ $role->id }}-{{ $permission->id }}" @checked(in_array($permission->name, $rolePermissionNames, true))>
                                                    <label class="form-check-label small" for="perm-{{ $role->id }}-{{ $permission->id }}">{{ $translatePermission($permission) }}</label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                    @if($role->name !== 'super_admin')
                        <form method="POST" action="{{ route('role.super_admin.roles.destroy', $role) }}" class="text-end mt-2">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm">{{ __('Delete role / حذف الدور') }}</button></form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div></div>
@endsection
