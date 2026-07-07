@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $roleLabel = fn ($role) => $role?->display_name ?: Str::headline((string) ($role?->name ?? ''));
    $statusLabel = fn (?string $status) => __('app.roles.super_admin.users.status.' . ($status ?: 'inactive'));
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
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/pages/pages-access-users-show.min.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4 admin-user-profile">
    <div class="profile-hero card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <div class="profile-avatar">{{ Str::of($user->name)->substr(0, 1) }}</div>
                    <div>
                        <div class="profile-chip mb-2"><i class="fas fa-id-card"></i> ملف مستخدم إداري</div>
                        <h1 class="h3 fw-bold mb-2">{{ $user->name }}</h1>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="profile-chip"><i class="fas fa-envelope"></i>{{ $user->email }}</span>
                            <span class="profile-chip"><i class="fas fa-location-dot"></i>{{ $user->branch?->name ?? 'غير محدد' }}</span>
                            <span class="profile-chip"><i class="fas fa-user-shield"></i>{{ $user->roles->map(fn($role) => $roleLabel($role))->implode('، ') ?: '-' }}</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('role.super_admin.users') }}" class="btn btn-light rounded-pill"><i class="fas fa-arrow-right me-1"></i>رجوع للمستخدمين</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg"><div class="profile-stat"><div class="profile-stat-label">أنشطة أنشأها</div><div class="profile-stat-value">{{ $stats['created_monthly_activities'] }}</div></div></div>
        <div class="col-6 col-lg"><div class="profile-stat"><div class="profile-stat-label">أنشطة فروعه</div><div class="profile-stat-value">{{ $stats['assigned_branch_activities'] }}</div></div></div>
        <div class="col-6 col-lg"><div class="profile-stat"><div class="profile-stat-label">أنشطة مكتملة</div><div class="profile-stat-value">{{ $stats['completed_branch_activities'] }}</div></div></div>
        <div class="col-6 col-lg"><div class="profile-stat"><div class="profile-stat-label">إجراءات Workflow</div><div class="profile-stat-value">{{ $stats['workflow_actions'] }}</div></div></div>
        <div class="col-6 col-lg"><div class="profile-stat"><div class="profile-stat-label">إشعارات</div><div class="profile-stat-value">{{ $stats['notifications'] }}</div></div></div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card profile-card h-100"><div class="card-body">
                <h2 class="h5 fw-bold mb-3"><i class="fas fa-address-card text-primary me-1"></i> معلومات الحساب</h2>
                <dl>
                    <dt>الاسم</dt><dd>{{ $user->name }}</dd>
                    <dt>البريد الإلكتروني</dt><dd dir="ltr">{{ $user->email }}</dd>
                    <dt>رقم الهاتف</dt><dd dir="ltr">{{ $user->phone ?: '-' }}</dd>
                    <dt>الحالة</dt><dd><span class="status-dot {{ $user->status === 'active' ? 'is-active' : '' }}">{{ $statusLabel($user->status) }}</span></dd>
                    <dt>الفرع / المركز</dt><dd>{{ $user->branch?->name ?? '-' }}</dd>
                    <dt>تاريخ الإنشاء</dt><dd>{{ optional($user->created_at)->format('Y-m-d H:i') ?: '-' }}</dd>
                </dl>
            </div></div>
        </div>
        <div class="col-12 col-xl-8">
            <div class="card profile-card mb-4"><div class="card-body">
                <h2 class="h5 fw-bold mb-3"><i class="fas fa-user-shield text-primary me-1"></i> الأدوار والصلاحيات</h2>
                <div class="mb-3">
                    <div class="fw-semibold mb-2">الأدوار</div>
                    @forelse($user->roles as $role)<span class="badge rounded-pill bg-primary-subtle text-primary border me-1 mb-1">{{ $roleLabel($role) }}</span>@empty <span class="text-muted">-</span>@endforelse
                </div>
                <div class="mb-3">
                    <div class="fw-semibold mb-2">الفروع المعيّنة</div>
                    @forelse($user->assignedBranches as $branch)<span class="badge rounded-pill bg-light text-dark border me-1 mb-1">{{ $branch->name }}</span>@empty <span class="text-muted">-</span>@endforelse
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold mb-2">الصلاحيات المباشرة</div>@forelse($user->getDirectPermissions() as $permission)<span class="badge bg-warning text-dark me-1 mb-1">{{ $translatePermission($permission) }}</span>@empty <span class="text-muted">لا توجد إضافات مباشرة.</span>@endforelse</div></div>
                    <div class="col-md-6"><div class="border rounded-4 p-3 h-100"><div class="fw-semibold mb-2">الصلاحيات الممنوعة</div>@forelse($user->deniedPermissions as $permission)<span class="badge bg-danger me-1 mb-1">{{ $translatePermission($permission) }}</span>@empty <span class="text-muted">لا توجد صلاحيات ممنوعة.</span>@endforelse</div></div>
                </div>
            </div></div>

            <div class="card profile-card"><div class="card-body">
                <h2 class="h5 fw-bold mb-3"><i class="fas fa-list-check text-primary me-1"></i> أحدث الأنشطة التي أنشأها</h2>
                <div class="table-responsive rounded-4 border"><table class="table table-sm align-middle mb-0"><thead><tr><th>النشاط</th><th>الحالة</th><th>آخر تحديث</th></tr></thead><tbody>
                    @forelse($recentActivities as $activity)
                        <tr><td>{{ $activity->title }}</td><td>{{ $activity->status }}</td><td>{{ optional($activity->updated_at)->format('Y-m-d') }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">لا توجد أنشطة منشأة بواسطة هذا المستخدم.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div></div>
        </div>
    </div>
</div>
@endsection
