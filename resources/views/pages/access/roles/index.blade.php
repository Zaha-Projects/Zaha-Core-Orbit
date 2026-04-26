@extends('layouts.new-theme-dashboard')


@php
    use Illuminate\Support\Str;

    $permissionsByModule = $permissions;

    $translateModule = fn($module) =>
        __('app.acl.modules.' . $module) !== 'app.acl.modules.' . $module
            ? __('app.acl.modules.' . $module)
            : Str::headline($module);

    $translatePermission = function ($permission) {
        $name = $permission->name;
        $label = app()->getLocale() === 'ar'
            ? ($permission->name_ar ?: $name)
            : ($permission->name_en ?: $name);

        return trim(preg_replace('/[\r\n\t]+/', ' ', $label));
    };
@endphp

@section('content')

<div class="container">

    {{-- CREATE ROLE --}}
    <div class="card mb-4">
        <div class="card-body">

            <form method="POST" action="{{ route('role.super_admin.roles.store') }}">
                @csrf

                <div class="row mb-3">
                    <div class="col"><input name="name" class="form-control" placeholder="system_name"></div>
                    <div class="col"><input name="name_ar" class="form-control" placeholder="الاسم بالعربية"></div>
                    <div class="col"><input name="name_en" class="form-control" placeholder="Name"></div>
                </div>

                <div class="accordion" id="createAccordion">
                    @foreach ($permissionsByModule as $module => $modulePermissions)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#create-{{ $loop->index }}">
                                    {{ $translateModule($module) }}
                                </button>
                            </h2>

                            <div id="create-{{ $loop->index }}" class="accordion-collapse collapse"
                                 data-bs-parent="#createAccordion">
                                <div class="accordion-body">

                                    @foreach ($modulePermissions as $permission)
                                        <div class="form-check form-switch mb-2">
                                            <input 
                                                type="checkbox"
                                                class="form-check-input"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"
                                                id="create-perm-{{ $permission->id }}"
                                                {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}
                                            >

                                            <label for="create-perm-{{ $permission->id }}">
                                                {{ $translatePermission($permission) }}
                                                <small class="text-muted d-block">{{ $permission->name }}</small>
                                            </label>
                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button class="btn btn-primary mt-3">{{ __('app.roles.super_admin.roles.actions.create') }}</button>
            </form>

        </div>
    </div>

    {{-- ROLES ACCORDION (IMPORTANT 🔥) --}}
    <div class="accordion" id="rolesAccordion">

        @foreach ($roles as $role)

            @php
                $rolePermissionNames = $role->permissions->pluck('name')->toArray();
            @endphp

            <div class="accordion-item">
                
                {{-- ROLE HEADER --}}
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed d-flex justify-content-between"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#role-{{ $role->id }}">
                        
                        <div class="w-100 d-flex justify-content-between">
                            <div>
                                <strong>{{ $role->display_name }}</strong>
                                <div class="text-muted small">{{ $role->name }}</div>
                            </div>

                            <span class="badge bg-light text-dark">
                                {{ count($rolePermissionNames) }} {{ __('app.common.permissions') }}
                            </span>
                        </div>
                    </button>
                </h2>

                {{-- ROLE BODY --}}
                <div id="role-{{ $role->id }}"
                     class="accordion-collapse collapse"
                     data-bs-parent="#rolesAccordion">

                    <div class="accordion-body">

                        <form method="POST" action="{{ route('role.super_admin.roles.update', $role) }}">
                            @csrf
                            @method('PUT')

                            <div class="row mb-3">
                                <div class="col"><input name="name" value="{{ $role->name }}" class="form-control"></div>
                                <div class="col"><input name="name_ar" value="{{ $role->name_ar }}" class="form-control"></div>
                                <div class="col"><input name="name_en" value="{{ $role->name_en }}" class="form-control"></div>
                            </div>

                            {{-- MODULES ACCORDION --}}
                            <div class="accordion" id="modules-{{ $role->id }}">
                                @foreach ($permissionsByModule as $module => $modulePermissions)

                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#module-{{ $role->id }}-{{ $loop->index }}">
                                                {{ $translateModule($module) }}
                                            </button>
                                        </h2>

                                        <div id="module-{{ $role->id }}-{{ $loop->index }}"
                                             class="accordion-collapse collapse"
                                             data-bs-parent="#modules-{{ $role->id }}">

                                            <div class="accordion-body">

                                                @foreach ($modulePermissions as $permission)
                                                    <div class="form-check form-switch mb-2">
                                                        <input 
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            name="permissions[]"
                                                            value="{{ $permission->name }}"
                                                            id="perm-{{ $role->id }}-{{ $permission->id }}"
                                                            {{ in_array($permission->name, $rolePermissionNames) ? 'checked' : '' }}
                                                        >

                                                        <label for="perm-{{ $role->id }}-{{ $permission->id }}">
                                                            {{ $translatePermission($permission) }}
                                                            <small class="text-muted d-block">{{ $permission->name }}</small>
                                                        </label>
                                                    </div>
                                                @endforeach

                                            </div>
                                        </div>
                                    </div>

                                @endforeach
                            </div>

                            <button class="btn btn-success mt-3">{{ __('app.roles.super_admin.roles.actions.save') }}</button>
                        </form>

                    </div>
                </div>

            </div>

        @endforeach

    </div>

</div>

@endsection
