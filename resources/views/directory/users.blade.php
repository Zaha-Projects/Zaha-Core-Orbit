@extends('layouts.app')

@section('title', 'دليل المستخدمين')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/pages/directory-users.min.css') }}">
@endpush

@section('content')
@php
    $roleIcons = [
        'super_admin' => 'fa-crown',
        'executive_manager' => 'fa-user-tie',
        'branch_manager' => 'fa-building-user',
        'supervisor' => 'fa-user-check',
        'relations_manager' => 'fa-handshake',
        'relations_officer' => 'fa-comments',
        'volunteer_coordinator' => 'fa-people-group',
        'programs_manager' => 'fa-diagram-project',
        'evaluation_officer' => 'fa-clipboard-check',
        'evaluation_followup_viewer' => 'fa-chart-line',
        'maintenance_officer' => 'fa-screwdriver-wrench',
        'finance_officer' => 'fa-coins',
        'transport_officer' => 'fa-truck',
        'movement_editor' => 'fa-route',
    ];

    $fallbackBranchColors = ['#2563EB', '#0EA5E9', '#22C55E', '#F59E0B', '#8B5CF6', '#14B8A6', '#F97316', '#E11D48', '#06B6D4', '#A855F7'];
    $branchColor = fn ($branch) => $branch?->color_hex ?: $fallbackBranchColors[((int) ($branch?->id ?? 1) - 1) % count($fallbackBranchColors)];
    $branchLabel = fn ($branch) => $branch ? ($branch->city ?: $branch->name) : 'غير محدد';
    $roleIcon = fn ($role) => $roleIcons[$role->name] ?? 'fa-user-shield';
    $softColor = function (?string $hex, float $alpha = .12): string {
        $hex = ltrim((string) $hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '2563EB';
        }
        return sprintf('rgba(%d, %d, %d, %.2f)', hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $alpha);
    };

    $selectedBranch = $branches->firstWhere('id', (int) ($filters['branch_id'] ?? 0));
    $selectedRole = $roles->firstWhere('name', $filters['role'] ?? null);
@endphp

<div class="container-fluid py-4 directory-page">
    <div class="directory-hero d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <span class="directory-hero-icon"><i class="fas fa-address-book fa-lg"></i></span>
            <div>
                <p class="text-muted mb-1 fw-bold">دليل عام مرتب حسب الفرع</p>
                <h1 class="h3 mb-1 fw-black">دليل المستخدمين</h1>
                <div class="text-muted small">استعرض المستخدمين حسب الفرع أو الدور مع تمييز بصري للأدوار والفروع.</div>
            </div>
        </div>
        <a class="btn btn-outline-primary rounded-pill px-4" href="{{ route('profile.show') }}">
            <i class="fas fa-user me-1"></i> ملفي الشخصي
        </a>
    </div>

    <div class="card directory-filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('directory.users.index') }}" class="row g-3 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label fw-bold">الفرع</label>
                    <input type="hidden" name="branch_id" value="{{ $filters['branch_id'] ?? '' }}" data-directory-select-input="branch">
                    <div class="dropdown directory-select" data-directory-select="branch">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-directory-select-label>
                            @if ($selectedBranch)
                                <span class="directory-branch-dot" data-branch-color="{{ $branchColor($selectedBranch) }}"></span>
                                <span>{{ $branchLabel($selectedBranch) }}</span>
                            @else
                                <span class="directory-branch-dot" data-branch-color="#94a3b8"></span>
                                <span>كل الفروع</span>
                            @endif
                        </button>
                        <div class="dropdown-menu directory-select-menu text-end">
                            <input class="form-control directory-select-search" type="search" placeholder="ابحث باسم الفرع..." data-directory-select-search>
                            <div class="directory-select-options">
                                <button class="dropdown-item directory-select-option {{ empty($filters['branch_id'] ?? '') ? 'is-selected' : '' }}" type="button" data-value="" data-label="كل الفروع">
                                    <span class="directory-branch-dot" data-branch-color="#94a3b8"></span>
                                    <span>كل الفروع</span>
                                </button>
                                @foreach ($branches as $branch)
                                    @php($color = $branchColor($branch))
                                    <button class="dropdown-item directory-select-option {{ (string)($filters['branch_id'] ?? '') === (string)$branch->id ? 'is-selected' : '' }}" type="button" data-value="{{ $branch->id }}" data-label="{{ $branchLabel($branch) }}">
                                        <span class="directory-branch-dot" data-branch-color="{{ $color }}"></span>
                                        <span class="flex-grow-1">{{ $branchLabel($branch) }}</span>
                                        @if($branch->is_main)
                                            <span class="badge rounded-pill text-bg-success">رئيسي</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <label class="form-label fw-bold">الدور</label>
                    <input type="hidden" name="role" value="{{ $filters['role'] ?? '' }}" data-directory-select-input="role">
                    <div class="dropdown directory-select" data-directory-select="role">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-directory-select-label>
                            @if ($selectedRole)
                                <span class="directory-role-icon"><i class="fas {{ $roleIcon($selectedRole) }}"></i></span>
                                <span>{{ $selectedRole->display_name }}</span>
                            @else
                                <span class="directory-role-icon"><i class="fas fa-users"></i></span>
                                <span>كل الأدوار</span>
                            @endif
                        </button>
                        <div class="dropdown-menu directory-select-menu text-end">
                            <input class="form-control directory-select-search" type="search" placeholder="ابحث باسم الدور..." data-directory-select-search>
                            <div class="directory-select-options">
                                <button class="dropdown-item directory-select-option {{ empty($filters['role'] ?? '') ? 'is-selected' : '' }}" type="button" data-value="" data-label="كل الأدوار">
                                    <span class="directory-role-icon"><i class="fas fa-users"></i></span>
                                    <span>كل الأدوار</span>
                                </button>
                                @foreach ($roles as $role)
                                    <button class="dropdown-item directory-select-option {{ ($filters['role'] ?? '') === $role->name ? 'is-selected' : '' }}" type="button" data-value="{{ $role->name }}" data-label="{{ $role->display_name }}">
                                        <span class="directory-role-icon"><i class="fas {{ $roleIcon($role) }}"></i></span>
                                        <span>{{ $role->display_name }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 d-grid">
                    <button class="btn btn-primary btn-lg rounded-pill" type="submit"><i class="fas fa-filter me-1"></i> فلترة</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card directory-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th class="ps-4">الاسم</th>
                            <th>الدور</th>
                            <th>الفرع</th>
                            <th>الفروع المكلف بها</th>
                            <th>الهاتف</th>
                            <th class="pe-4">البريد الإلكتروني</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $directoryUser)
                            @php($primaryColor = $branchColor($directoryUser->branch))
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="directory-user-avatar">{{ mb_substr($directoryUser->name, 0, 1) }}</span>
                                        <div>
                                            <div class="directory-user-name">{{ $directoryUser->name }}</div>
                                            <div class="directory-user-email">{{ $directoryUser->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @forelse ($directoryUser->roles as $role)
                                        <span class="directory-role-badge"><i class="fas {{ $roleIcon($role) }}"></i>{{ $role->display_name }}</span>
                                    @empty
                                        <span class="text-muted">لا يوجد</span>
                                    @endforelse
                                </td>
                                <td>
                                    @if($directoryUser->branch)
                                        <span class="directory-branch-badge" data-branch-color="{{ $primaryColor }}" data-branch-bg="{{ $softColor($primaryColor, .13) }}">
                                            <span class="directory-branch-dot" data-branch-color="{{ $primaryColor }}"></span>
                                            {{ $branchLabel($directoryUser->branch) }}
                                        </span>
                                    @else
                                        <span class="text-muted">غير محدد</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($directoryUser->assignedBranches as $branch)
                                        @php($color = $branchColor($branch))
                                        <span class="directory-branch-badge" data-branch-color="{{ $color }}" data-branch-bg="{{ $softColor($color, .11) }}">
                                            <span class="directory-branch-dot" data-branch-color="{{ $color }}"></span>
                                            {{ $branchLabel($branch) }}
                                        </span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td dir="ltr">{{ $directoryUser->phone ?: '-' }}</td>
                                <td class="pe-4"><a href="mailto:{{ $directoryUser->email }}">{{ $directoryUser->email }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5">لا يوجد مستخدمون مطابقون للفلاتر.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="directory-pagination-bar mx-3 mb-3" aria-label="تنقل صفحات دليل المستخدمين">
                <div class="directory-pagination-summary">
                    @if ($users->total() > 0)
                        عرض {{ $users->firstItem() }} إلى {{ $users->lastItem() }} من أصل {{ $users->total() }} مستخدم
                    @else
                        لا توجد نتائج للعرض
                    @endif
                </div>
                {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ \App\Support\AssetVersion::url('assets/js/pages/dynamic-colors.min.js') }}"></script>
<script src="{{ \App\Support\AssetVersion::url('assets/js/pages/directory-users.min.js') }}"></script>
@endpush
