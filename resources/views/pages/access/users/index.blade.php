@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $title = __('app.roles.super_admin.users.title');
    $subtitle = __('app.roles.super_admin.users.subtitle');
    $permissionsByModule = $permissions->groupBy('module');
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

        $label = app()->getLocale() === 'ar'
            ? ($ar ?: __('app.acl.permissions.' . str_replace('.', '_', $name), [], 'ar'))
            : ($en ?: __('app.acl.permissions.' . str_replace('.', '_', $name), [], 'en'));

        return trim(preg_replace('/[\r\n\t]+/u', ' ', (string) $label));
    };
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
                            <label class="form-label">{{ __('app.roles.super_admin.users.fields.role') }}</label>
                            <select class="form-select" name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="small text-muted mb-2">Role permissions are inherited automatically. Select only additional permissions here. / صلاحيات الدور تورث تلقائياً، اختر هنا الإضافات فقط.</div>
                            <div class="accordion" id="create-user-permissions-accordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="create-user-overrides-heading">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#create-user-overrides-body" aria-expanded="true" aria-controls="create-user-overrides-body">
                                            Permissions Override (صلاحيات إضافية)
                                        </button>
                                    </h2>
                                    <div id="create-user-overrides-body" class="accordion-collapse collapse show" aria-labelledby="create-user-overrides-heading" data-bs-parent="#create-user-permissions-accordion">
                                        <div class="accordion-body">
                                            <div class="accordion" id="create-user-overrides-modules">
                                                @foreach ($permissionsByModule as $module => $modulePermissions)
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="create-user-overrides-module-heading-{{ $loop->index }}">
                                                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#create-user-overrides-module-body-{{ $loop->index }}" aria-expanded="false" aria-controls="create-user-overrides-module-body-{{ $loop->index }}">
                                                                {{ $translateModule($module) }}
                                                            </button>
                                                        </h2>
                                                        <div id="create-user-overrides-module-body-{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="create-user-overrides-module-heading-{{ $loop->index }}" data-bs-parent="#create-user-overrides-modules">
                                                            <div class="accordion-body pt-2">
                                                                @foreach ($modulePermissions as $permission)
                                                                    <div class="form-check form-switch mb-1">
                                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="create-perm-{{ $permission->id }}">
                                                                        <label class="form-check-label small" for="create-perm-{{ $permission->id }}">{{ $translatePermission($permission) }}</label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="create-user-denied-heading">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#create-user-denied-body" aria-expanded="false" aria-controls="create-user-denied-body">
                                            Denied Permissions (صلاحيات ممنوعة)
                                        </button>
                                    </h2>
                                    <div id="create-user-denied-body" class="accordion-collapse collapse" aria-labelledby="create-user-denied-heading" data-bs-parent="#create-user-permissions-accordion">
                                        <div class="accordion-body">
                                            <div class="accordion" id="create-user-denied-modules">
                                                @foreach ($permissionsByModule as $module => $modulePermissions)
                                                    <div class="accordion-item">
                                                        <h2 class="accordion-header" id="create-user-denied-module-heading-{{ $loop->index }}">
                                                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#create-user-denied-module-body-{{ $loop->index }}" aria-expanded="false" aria-controls="create-user-denied-module-body-{{ $loop->index }}">
                                                                {{ $translateModule($module) }}
                                                            </button>
                                                        </h2>
                                                        <div id="create-user-denied-module-body-{{ $loop->index }}" class="accordion-collapse collapse" aria-labelledby="create-user-denied-module-heading-{{ $loop->index }}" data-bs-parent="#create-user-denied-modules">
                                                            <div class="accordion-body pt-2">
                                                                @foreach ($modulePermissions as $permission)
                                                                    <div class="form-check form-switch mb-1">
                                                                        <input class="form-check-input" type="checkbox" name="denied_permissions[]" value="{{ $permission->name }}" id="create-deny-{{ $permission->id }}">
                                                                        <label class="form-check-label small" for="create-deny-{{ $permission->id }}">{{ $translatePermission($permission) }}</label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                                    @php($userAllPermissionNames = $user->getAllPermissions()->pluck('name')->toArray())
                                    @php($userDeniedPermissionNames = $user->deniedPermissions->pluck('name')->toArray())
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
                                            <form class="d-inline js-user-delete-form" method="POST" action="{{ route('role.super_admin.users.destroy', $user) }}">
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
                                                                {{ $role->display_name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>


                                                <div class="col-12">
                                                    <div class="small text-muted">Role Permissions (صلاحيات الدور):
                                                        @php($rolePermissions = optional($user->roles->first())->permissions ?? collect())
                                                        @forelse($rolePermissions as $rp)
                                                            <span class="badge bg-light text-dark">{{ $translatePermission($rp) }}</span>
                                                        @empty
                                                            <span>-</span>
                                                        @endforelse
                                                    </div>
                                                    <div class="small text-muted mt-1">Direct Overrides (الإضافات المباشرة):
                                                        @forelse($user->getDirectPermissions() as $dp)
                                                            <span class="badge bg-warning text-dark">{{ $translatePermission($dp) }}</span>
                                                        @empty
                                                            <span>-</span>
                                                        @endforelse
                                                    </div>
                                                    <div class="small text-muted mt-1">Denied (ممنوع):
                                                        @forelse($user->deniedPermissions as $dp)
                                                            <span class="badge bg-danger">{{ $translatePermission($dp) }}</span>
                                                        @empty
                                                            <span>-</span>
                                                        @endforelse
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label">Permissions Override (صلاحيات إضافية)</label>
                                                    <div class="row g-2">
                                                        @foreach ($permissionsByModule as $module => $modulePermissions)
                                                            <div class="col-12 col-lg-6">
                                                                <div class="border rounded p-2">
                                                                    <div class="fw-semibold small mb-1">{{ $translateModule($module) }}</div>
                                                                    @foreach ($modulePermissions as $permission)
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="edit-user-{{ $user->id }}-perm-{{ $permission->id }}" {{ in_array($permission->name, $userAllPermissionNames, true) ? 'checked' : '' }}>
                                                                            <label class="form-check-label small" for="edit-user-{{ $user->id }}-perm-{{ $permission->id }}">{{ $translatePermission($permission) }}</label>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>


                                                <div class="col-12">
                                                    <label class="form-label">Denied Permissions (صلاحيات ممنوعة)</label>
                                                    <div class="row g-2">
                                                        @foreach ($permissionsByModule as $module => $modulePermissions)
                                                            <div class="col-12 col-lg-6"><div class="border rounded p-2"><div class="fw-semibold small mb-1">{{ $translateModule($module) }}</div>
                                                                @foreach ($modulePermissions as $permission)
                                                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="denied_permissions[]" value="{{ $permission->name }}" id="edit-user-{{ $user->id }}-deny-{{ $permission->id }}" {{ in_array($permission->name, $userDeniedPermissionNames, true) ? 'checked' : '' }}><label class="form-check-label small" for="edit-user-{{ $user->id }}-deny-{{ $permission->id }}">{{ $translatePermission($permission) }}</label></div>
                                                                @endforeach
                                                            </div></div>
                                                        @endforeach
                                                    </div>
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


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.querySelectorAll('.js-user-delete-form').forEach((form) => {
    form.addEventListener('submit', function (event) {
        event.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: @json(__('app.roles.super_admin.users.delete_confirm.title')),
            text: @json(__('app.roles.super_admin.users.delete_confirm.text')),
            showCancelButton: true,
            confirmButtonText: @json(__('app.roles.super_admin.users.delete_confirm.confirm')),
            cancelButtonText: @json(__('app.roles.super_admin.users.delete_confirm.cancel')),
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
