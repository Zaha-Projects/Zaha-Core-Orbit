@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $title = __('app.roles.super_admin.users.title');
    $subtitle = __('app.roles.super_admin.users.subtitle');
    $assignmentRoles = ['branch_coordinator', 'evaluation_officer', 'evaluation_followup_viewer'];
    $createAssignedBranchIds = collect(old('assigned_branch_ids', []))->map(fn ($id) => (int) $id)->all();
    $permissionsByModule = $permissions->groupBy('module');
    $roleLabel = fn ($role) => $role?->display_name ?: Str::headline((string) ($role?->name ?? ''));
    $statusLabel = fn (?string $status) => __('app.roles.super_admin.users.status.' . ($status ?: 'inactive'));
    $translateModule = function (?string $module) {
        $moduleKey = (string) $module;
        $translated = __('app.acl.modules.' . $moduleKey);
        return $translated !== 'app.acl.modules.' . $moduleKey ? $translated : Str::headline($moduleKey);
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

@push('styles')
<style>
    .users-admin-page .users-hero { border: 0; border-radius: 22px; background: linear-gradient(135deg, rgba(37,99,235,.12), rgba(20,184,166,.08)); }
    .users-admin-page .user-status-dot { display: inline-flex; align-items: center; gap: .45rem; font-weight: 700; }
    .users-admin-page .user-status-dot::before { content: ''; width: .72rem; height: .72rem; border-radius: 999px; box-shadow: 0 0 0 4px rgba(148,163,184,.14); }
    .users-admin-page .user-status-dot.is-active::before { background: #16a34a; box-shadow: 0 0 0 4px rgba(22,163,74,.14); }
    .users-admin-page .user-status-dot.is-inactive::before { background: #dc2626; box-shadow: 0 0 0 4px rgba(220,38,38,.14); }
    .users-admin-page .user-role-badge { border: 1px solid #bfdbfe; background: #eff6ff; color: #1d4ed8; }
    .users-admin-page .assigned-branches { color: #64748b; font-size: .82rem; line-height: 1.7; }
    .users-admin-page .table thead th { background: #f8fafc; color: #0f172a; font-weight: 800; white-space: nowrap; }
    .users-admin-page .table td { vertical-align: middle; }
    .users-admin-page .password-input-group .btn { border-color: #d7dee8; }
    .users-admin-page .modal-content { border: 0; border-radius: 22px; box-shadow: 0 24px 70px rgba(15,23,42,.16); }
    .users-admin-page .modal-header { background: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%); border-bottom: 1px solid #e2e8f0; }
    .users-admin-page .form-select, .users-admin-page .form-control { min-height: 42px; }
    .users-admin-page .permission-scroll { max-height: 300px; overflow: auto; }
</style>
@endpush

@section('content')
<div class="users-admin-page">
    <div class="card users-hero shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h1 class="h4 mb-2">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
            @can('users.manage')
                <button class="btn btn-primary rounded-pill" type="button" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="fas fa-user-plus me-1"></i>{{ __('app.roles.super_admin.users.create_title') }}
                </button>
            @endcan
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">يرجى تصحيح الأخطاء التالية:</div>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @can('users.manage')
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5">{{ __('app.roles.super_admin.users.create_title') }}</h2>
                        <div class="small text-muted">إنشاء مستخدم جديد وتحديد الدور والفروع والصلاحيات.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('role.super_admin.users.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.name') }}</label>
                                <input class="form-control" name="name" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.email') }}</label>
                                <input class="form-control" type="email" name="email" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.phone') }}</label>
                                <input class="form-control" name="phone" value="{{ old('phone') }}" dir="ltr">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.status') }}</label>
                                <select class="form-select" name="status" required>
                                    <option value="active" @selected(old('status', 'active') === 'active')>{{ __('app.roles.super_admin.users.status.active') }}</option>
                                    <option value="inactive" @selected(old('status') === 'inactive')>{{ __('app.roles.super_admin.users.status.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.role') }}</label>
                                <select class="form-select" name="role" data-user-role-select required>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $roleLabel($role) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.branch') }}</label>
                                <select class="form-select" name="branch_id">
                                    <option value="">{{ __('app.roles.super_admin.users.fields.branch_placeholder') }}</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((int) old('branch_id') === $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.password') }}</label>
                                <div class="input-group password-input-group" data-password-tools>
                                    <input class="form-control" type="password" name="password" autocomplete="new-password" required>
                                    <button class="btn btn-outline-secondary" type="button" data-password-toggle title="إظهار/إخفاء"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-outline-primary" type="button" data-password-generate>توليد</button>
                                </div>
                            </div>
                            <div class="col-12" data-branch-assignment-wrapper hidden>
                                <label class="form-label">{{ __('app.roles.super_admin.users.fields.assigned_branches') }}</label>
                                <div class="form-text mb-2">{{ __('app.roles.super_admin.users.fields.assigned_branches_help') }}</div>
                                <div class="border rounded-3 p-3"><div class="row g-2">
                                    @foreach ($branches as $branch)
                                        <div class="col-12 col-md-6 col-xl-4"><label class="form-check border rounded-3 px-3 py-2 h-100"><input class="form-check-input me-2" type="checkbox" name="assigned_branch_ids[]" value="{{ $branch->id }}" @checked(in_array($branch->id, $createAssignedBranchIds, true))><span class="form-check-label">{{ $branch->name }}</span></label></div>
                                    @endforeach
                                </div></div>
                            </div>
                            <div class="col-12">
                                <div class="accordion" id="createPermissionsAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#createPermissionsBody">صلاحيات إضافية / ممنوعة</button></h2>
                                        <div id="createPermissionsBody" class="accordion-collapse collapse" data-bs-parent="#createPermissionsAccordion"><div class="accordion-body permission-scroll">
                                            <div class="row g-3">
                                                <div class="col-12 col-lg-6"><h3 class="h6">صلاحيات إضافية</h3>
                                                    @foreach($permissionsByModule as $module => $modulePermissions)
                                                        <div class="fw-semibold small mt-2">{{ $translateModule($module) }}</div>
                                                        @foreach($modulePermissions as $permission)
                                                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="create-perm-{{ $permission->id }}"><label class="form-check-label small" for="create-perm-{{ $permission->id }}">{{ $translatePermission($permission) }}</label></div>
                                                        @endforeach
                                                    @endforeach
                                                </div>
                                                <div class="col-12 col-lg-6"><h3 class="h6">صلاحيات ممنوعة</h3>
                                                    @foreach($permissionsByModule as $module => $modulePermissions)
                                                        <div class="fw-semibold small mt-2">{{ $translateModule($module) }}</div>
                                                        @foreach($modulePermissions as $permission)
                                                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="denied_permissions[]" value="{{ $permission->name }}" id="create-deny-{{ $permission->id }}"><label class="form-check-label small" for="create-deny-{{ $permission->id }}">{{ $translatePermission($permission) }}</label></div>
                                                        @endforeach
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>{{ __('app.roles.super_admin.users.actions.create') }}</button></div>
                </form>
            </div>
        </div>
    </div>
    @endcan

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h6 mb-0">{{ __('app.roles.super_admin.users.list_title') }}</h2>
                <span class="badge bg-light text-dark border">{{ $users->count() }} مستخدم</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead><tr><th>{{ __('app.roles.super_admin.users.table.name') }}</th><th>{{ __('app.roles.super_admin.users.table.email') }}</th><th>{{ __('app.roles.super_admin.users.table.branch') }}</th><th>{{ __('app.roles.super_admin.users.table.center') }}</th><th>{{ __('app.roles.super_admin.users.table.role') }}</th><th>{{ __('app.roles.super_admin.users.table.status') }}</th><th>{{ __('app.roles.super_admin.users.table.actions') }}</th></tr></thead>
                    <tbody>
                        @forelse ($users as $user)
                            @php
                                $primaryRole = $user->roles->first();
                                $editAssignedBranchIds = $user->assignedBranches->pluck('id')->map(fn ($id) => (int) $id)->all();
                                $userAllPermissionNames = $user->getAllPermissions()->pluck('name')->all();
                                $userDeniedPermissionNames = $user->deniedPermissions->pluck('name')->all();
                            @endphp
                            <tr>
                                <td><div class="fw-semibold">{{ $user->name }}</div><div class="small text-muted">#{{ $user->id }}</div></td>
                                <td dir="ltr">{{ $user->email }}</td>
                                <td>
                                    <div>{{ $user->branch?->name ?? __('app.roles.super_admin.users.table.unassigned') }}</div>
                                    @if($user->assignedBranches->isNotEmpty())<div class="assigned-branches">{{ __('app.roles.super_admin.users.fields.assigned_branches_short') }}: {{ $user->assignedBranches->pluck('name')->implode('، ') }}</div>@endif
                                </td>
                                <td>{{ $user->branch?->name ?? __('app.roles.super_admin.users.table.unassigned') }}</td>
                                <td><span class="badge rounded-pill user-role-badge">{{ $roleLabel($primaryRole) }}</span></td>
                                <td><span class="user-status-dot {{ $user->status === 'active' ? 'is-active' : 'is-inactive' }}">{{ $statusLabel($user->status) }}</span></td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a class="btn btn-sm btn-outline-info" href="{{ route('role.super_admin.users.show', $user) }}"><i class="fas fa-eye me-1"></i>عرض</a>
                                        @can('users.manage')<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $user->id }}"><i class="fas fa-pen-to-square me-1"></i>{{ __('app.roles.super_admin.users.actions.edit') }}</button>@endcan
                                        @can('users.manage')<form method="POST" action="{{ route('role.super_admin.users.destroy', $user) }}" onsubmit="return confirm('{{ __('app.roles.super_admin.users.delete_confirm.title') }}')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash me-1"></i>{{ __('app.roles.super_admin.users.actions.delete') }}</button></form>@endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('app.roles.super_admin.users.table.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @can('users.manage')
        @foreach ($users as $user)
            @php
                $primaryRole = $user->roles->first();
                $editAssignedBranchIds = $user->assignedBranches->pluck('id')->map(fn ($id) => (int) $id)->all();
                $userAllPermissionNames = $user->getAllPermissions()->pluck('name')->all();
                $userDeniedPermissionNames = $user->deniedPermissions->pluck('name')->all();
            @endphp
            <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
                    <div class="modal-header"><div><h2 class="modal-title h5">تعديل {{ $user->name }}</h2><div class="small text-muted">تعديل بيانات الحساب والدور والصلاحيات.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <form method="POST" action="{{ route('role.super_admin.users.update', $user) }}">
                        @csrf @method('PUT')
                        <div class="modal-body"><div class="row g-3">
                            <div class="col-12 col-md-6"><label class="form-label">{{ __('app.roles.super_admin.users.fields.name') }}</label><input class="form-control" name="name" value="{{ $user->name }}" required></div>
                            <div class="col-12 col-md-6"><label class="form-label">{{ __('app.roles.super_admin.users.fields.email') }}</label><input class="form-control" type="email" name="email" value="{{ $user->email }}" required></div>
                            <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.super_admin.users.fields.phone') }}</label><input class="form-control" name="phone" value="{{ $user->phone }}" dir="ltr"></div>
                            <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.super_admin.users.fields.status') }}</label><select class="form-select" name="status"><option value="active" @selected($user->status === 'active')>{{ __('app.roles.super_admin.users.status.active') }}</option><option value="inactive" @selected($user->status === 'inactive')>{{ __('app.roles.super_admin.users.status.inactive') }}</option></select></div>
                            <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.super_admin.users.fields.role') }}</label><select class="form-select" name="role" data-user-role-select>@foreach($roles as $role)<option value="{{ $role->name }}" @selected($primaryRole?->name === $role->name)>{{ $roleLabel($role) }}</option>@endforeach</select></div>
                            <div class="col-12 col-md-6"><label class="form-label">{{ __('app.roles.super_admin.users.fields.branch') }}</label><select class="form-select" name="branch_id"><option value="">{{ __('app.roles.super_admin.users.fields.branch_placeholder') }}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((int) $user->branch_id === $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                            <div class="col-12 col-md-6"><label class="form-label">{{ __('app.roles.super_admin.users.fields.password_optional') }}</label><div class="input-group password-input-group" data-password-tools><input class="form-control" type="password" name="password" autocomplete="new-password"><button class="btn btn-outline-secondary" type="button" data-password-toggle title="إظهار/إخفاء"><i class="fas fa-eye"></i></button><button class="btn btn-outline-primary" type="button" data-password-generate>توليد</button></div></div>
                            <div class="col-12" data-branch-assignment-wrapper hidden><label class="form-label">{{ __('app.roles.super_admin.users.fields.assigned_branches') }}</label><div class="form-text mb-2">{{ __('app.roles.super_admin.users.fields.assigned_branches_help') }}</div><div class="border rounded-3 p-3"><div class="row g-2">@foreach($branches as $branch)<div class="col-12 col-md-6 col-xl-4"><label class="form-check border rounded-3 px-3 py-2 h-100"><input class="form-check-input me-2" type="checkbox" name="assigned_branch_ids[]" value="{{ $branch->id }}" @checked(in_array($branch->id, $editAssignedBranchIds, true))><span class="form-check-label">{{ $branch->name }}</span></label></div>@endforeach</div></div></div>
                            <div class="col-12"><div class="accordion" id="editPermissionsAccordion{{ $user->id }}"><div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#editPermissionsBody{{ $user->id }}">صلاحيات الدور والإضافات</button></h2><div id="editPermissionsBody{{ $user->id }}" class="accordion-collapse collapse" data-bs-parent="#editPermissionsAccordion{{ $user->id }}"><div class="accordion-body permission-scroll"><div class="mb-2 small text-muted">صلاحيات الدور تورث تلقائيًا، ويمكن إضافة أو منع صلاحيات محددة.</div><div class="row g-3"><div class="col-12 col-lg-6"><h3 class="h6">صلاحيات إضافية</h3>@foreach($permissionsByModule as $module => $modulePermissions)<div class="fw-semibold small mt-2">{{ $translateModule($module) }}</div>@foreach($modulePermissions as $permission)<div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="edit-{{ $user->id }}-perm-{{ $permission->id }}" @checked(in_array($permission->name, $userAllPermissionNames, true))><label class="form-check-label small" for="edit-{{ $user->id }}-perm-{{ $permission->id }}">{{ $translatePermission($permission) }}</label></div>@endforeach @endforeach</div><div class="col-12 col-lg-6"><h3 class="h6">صلاحيات ممنوعة</h3>@foreach($permissionsByModule as $module => $modulePermissions)<div class="fw-semibold small mt-2">{{ $translateModule($module) }}</div>@foreach($modulePermissions as $permission)<div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="denied_permissions[]" value="{{ $permission->name }}" id="edit-{{ $user->id }}-deny-{{ $permission->id }}" @checked(in_array($permission->name, $userDeniedPermissionNames, true))><label class="form-check-label small" for="edit-{{ $user->id }}-deny-{{ $permission->id }}">{{ $translatePermission($permission) }}</label></div>@endforeach @endforeach</div></div></div></div></div></div></div>
                        </div></div>
                        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">إلغاء</button><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i>{{ __('app.roles.super_admin.users.actions.save') }}</button></div>
                    </form>
                </div></div>
            </div>
        @endforeach
    @endcan
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const assignmentRoles = @json($assignmentRoles);
    document.querySelectorAll('[data-user-role-select]').forEach((select) => {
        const form = select.closest('form');
        const wrapper = form?.querySelector('[data-branch-assignment-wrapper]');
        const toggle = () => {
            if (!wrapper) return;
            const show = assignmentRoles.includes(select.value);
            wrapper.hidden = !show;
            wrapper.querySelectorAll('input').forEach((input) => { input.disabled = !show; });
        };
        select.addEventListener('change', toggle);
        toggle();
    });
    const randomPassword = () => {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        return Array.from({length: 14}, () => chars[Math.floor(Math.random() * chars.length)]).join('');
    };
    document.querySelectorAll('[data-password-tools]').forEach((group) => {
        const input = group.querySelector('input');
        group.querySelector('[data-password-toggle]')?.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            group.querySelector('[data-password-toggle] i')?.classList.toggle('fa-eye-slash', input.type === 'text');
        });
        group.querySelector('[data-password-generate]')?.addEventListener('click', () => {
            input.value = randomPassword();
            input.type = 'text';
            input.dispatchEvent(new Event('input', {bubbles: true}));
        });
    });
    @if($errors->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createUserModal'))?.show();
    @endif
});
</script>
@endpush
