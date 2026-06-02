@extends('layouts.app')

@section('title', 'دليل المستخدمين')

@push('styles')
<style>
    .directory-page {
        --directory-primary: #1f4ba6;
        --directory-primary-2: #2563eb;
        --directory-soft: #eef5ff;
        --directory-ink: #172033;
        --directory-muted: #64748b;
        --directory-border: rgba(15, 23, 42, .09);
        color: var(--directory-ink);
    }

    .directory-hero {
        background:
            radial-gradient(circle at 10% 12%, rgba(37, 99, 235, .2), transparent 28%),
            radial-gradient(circle at 88% 18%, rgba(20, 184, 166, .18), transparent 30%),
            linear-gradient(135deg, #ffffff 0%, #f4f8ff 100%);
        border: 1px solid var(--directory-border);
        border-radius: 1.5rem;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .07);
        padding: 1.35rem;
    }

    .directory-hero-icon,
    .directory-user-avatar,
    .directory-role-icon {
        align-items: center;
        display: inline-flex;
        justify-content: center;
    }

    .directory-hero-icon {
        background: linear-gradient(135deg, var(--directory-primary), var(--directory-primary-2));
        border-radius: 1.15rem;
        box-shadow: 0 14px 28px rgba(37, 99, 235, .22);
        color: #fff;
        height: 3.2rem;
        width: 3.2rem;
    }

    .directory-filter-card,
    .directory-table-card {
        border: 1px solid var(--directory-border);
        border-radius: 1.35rem;
        box-shadow: 0 16px 38px rgba(15, 23, 42, .06);
        overflow: visible;
    }

    .directory-filter-card .card-body { padding: 1.2rem; }

    .directory-select .dropdown-toggle {
        align-items: center;
        background: #fff;
        border: 1px solid rgba(31, 75, 166, .16);
        border-radius: 1rem;
        color: #1e293b;
        display: flex;
        font-weight: 800;
        gap: .65rem;
        justify-content: space-between;
        min-height: 3.15rem;
        padding: .65rem .9rem;
        text-align: start;
        width: 100%;
    }

    .directory-select .dropdown-toggle::after { margin-inline-start: auto; }

    .directory-select .dropdown-toggle:hover,
    .directory-select .dropdown-toggle:focus {
        border-color: rgba(37, 99, 235, .42);
        box-shadow: 0 0 0 .22rem rgba(37, 99, 235, .1);
    }

    .directory-select-menu {
        border: 1px solid rgba(31, 75, 166, .12);
        border-radius: 1.1rem;
        box-shadow: 0 22px 50px rgba(15, 23, 42, .16);
        max-height: 23rem;
        min-width: 100%;
        overflow: hidden;
        padding: .65rem;
    }

    .directory-select-search {
        border: 1px solid #dbeafe;
        border-radius: .85rem;
        margin-bottom: .55rem;
        min-height: 2.6rem;
    }

    .directory-select-options {
        max-height: 18.5rem;
        overflow-y: auto;
        padding-inline-end: .2rem;
    }

    .directory-select-option {
        align-items: center;
        border: 0;
        border-radius: .85rem;
        color: #24324b;
        display: flex;
        gap: .65rem;
        padding: .65rem .7rem;
        white-space: normal;
        width: 100%;
    }

    .directory-select-option:hover,
    .directory-select-option:focus,
    .directory-select-option.is-selected {
        background: linear-gradient(135deg, #eff6ff 0%, #f8fbff 100%);
        color: var(--directory-primary);
    }

    .directory-select-option.is-selected { font-weight: 900; }

    .directory-branch-dot {
        background: var(--branch-color, #2563eb);
        border: 2px solid rgba(255, 255, 255, .9);
        border-radius: 999px;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--branch-color, #2563eb) 18%, transparent);
        flex: 0 0 auto;
        height: .85rem;
        width: .85rem;
    }

    .directory-role-icon {
        background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(20, 184, 166, .16));
        border-radius: .8rem;
        color: var(--directory-primary);
        flex: 0 0 auto;
        height: 2rem;
        width: 2rem;
    }

    .directory-user-avatar {
        background: linear-gradient(135deg, #eff6ff, #e0f2fe);
        border: 1px solid #dbeafe;
        border-radius: 1rem;
        color: var(--directory-primary);
        flex: 0 0 auto;
        height: 2.75rem;
        width: 2.75rem;
    }

    .directory-user-name { font-weight: 900; }
    .directory-user-email { color: var(--directory-muted); font-size: .84rem; }

    .directory-branch-badge {
        align-items: center;
        background: var(--branch-bg, #eff6ff);
        border: 1px solid color-mix(in srgb, var(--branch-color, #2563eb) 28%, #ffffff);
        border-radius: 999px;
        color: #1e293b;
        display: inline-flex;
        font-weight: 800;
        gap: .45rem;
        margin-bottom: .25rem;
        padding: .38rem .7rem;
    }

    .directory-role-badge {
        align-items: center;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        box-shadow: 0 6px 14px rgba(15, 23, 42, .04);
        color: #334155;
        display: inline-flex;
        font-weight: 800;
        gap: .45rem;
        margin-bottom: .25rem;
        padding: .32rem .65rem;
    }

    .directory-role-badge i { color: var(--directory-primary); }

    .directory-table-card .table { margin-bottom: 0; }
    .directory-table-card thead th {
        background: #f8fbff;
        color: #475569;
        font-size: .84rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .directory-table-card tbody tr { transition: background .16s ease, transform .16s ease; }
    .directory-table-card tbody tr:hover {
        background: #fbfdff;
        transform: translateY(-1px);
    }

    .directory-pagination-bar {
        align-items: center;
        background: linear-gradient(135deg, #f8fafc 0%, #eef5ff 100%);
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: space-between;
        margin-top: 1rem;
        padding: 1rem 1.15rem;
    }

    .directory-pagination-summary {
        color: #64748b;
        font-size: .9rem;
        font-weight: 700;
    }

    .directory-pagination-bar nav { margin: 0; }

    .directory-pagination-bar .pagination {
        align-items: center;
        flex-wrap: wrap;
        gap: .35rem;
        justify-content: center;
        margin: 0;
    }

    .directory-pagination-bar .page-link {
        align-items: center;
        border: 1px solid #dbe4ef;
        border-radius: .75rem;
        color: #1f4ba6;
        display: inline-flex;
        font-weight: 800;
        justify-content: center;
        min-height: 2.35rem;
        min-width: 2.35rem;
        padding: .5rem .75rem;
        transition: all .18s ease;
    }

    .directory-pagination-bar .page-link:hover,
    .directory-pagination-bar .page-link:focus {
        background: #eaf2ff;
        border-color: #bcd3ff;
        box-shadow: 0 8px 18px rgba(31, 75, 166, .12);
        color: #123a86;
        transform: translateY(-1px);
    }

    .directory-pagination-bar .page-item.active .page-link {
        background: linear-gradient(135deg, #1f4ba6 0%, #2563eb 100%);
        border-color: #1f4ba6;
        box-shadow: 0 10px 20px rgba(31, 75, 166, .22);
        color: #fff;
    }

    .directory-pagination-bar .page-item.disabled .page-link {
        background: #f1f5f9;
        border-color: #e2e8f0;
        color: #94a3b8;
        opacity: .85;
    }

    [data-theme="dark"] .directory-hero,
    [data-theme="dark"] .directory-filter-card,
    [data-theme="dark"] .directory-table-card,
    [data-theme="dark"] .directory-select .dropdown-toggle,
    [data-theme="dark"] .directory-select-menu,
    [data-theme="dark"] .directory-role-badge {
        background: var(--surface-bg);
        border-color: var(--border-color);
        color: var(--body-color);
    }

    [data-theme="dark"] .directory-table-card thead th,
    [data-theme="dark"] .directory-pagination-bar {
        background: var(--surface-soft);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .directory-pagination-summary,
    [data-theme="dark"] .directory-user-email { color: var(--muted-color); }

    @media (max-width: 575.98px) {
        .directory-pagination-bar { justify-content: center; text-align: center; }
        .directory-pagination-summary { width: 100%; }
    }
</style>
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
                                <span class="directory-branch-dot" style="--branch-color: {{ $branchColor($selectedBranch) }}"></span>
                                <span>{{ $branchLabel($selectedBranch) }}</span>
                            @else
                                <span class="directory-branch-dot" style="--branch-color: #94a3b8"></span>
                                <span>كل الفروع</span>
                            @endif
                        </button>
                        <div class="dropdown-menu directory-select-menu text-end">
                            <input class="form-control directory-select-search" type="search" placeholder="ابحث باسم الفرع..." data-directory-select-search>
                            <div class="directory-select-options">
                                <button class="dropdown-item directory-select-option {{ empty($filters['branch_id'] ?? '') ? 'is-selected' : '' }}" type="button" data-value="" data-label="كل الفروع">
                                    <span class="directory-branch-dot" style="--branch-color: #94a3b8"></span>
                                    <span>كل الفروع</span>
                                </button>
                                @foreach ($branches as $branch)
                                    @php($color = $branchColor($branch))
                                    <button class="dropdown-item directory-select-option {{ (string)($filters['branch_id'] ?? '') === (string)$branch->id ? 'is-selected' : '' }}" type="button" data-value="{{ $branch->id }}" data-label="{{ $branchLabel($branch) }}">
                                        <span class="directory-branch-dot" style="--branch-color: {{ $color }}"></span>
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
                                        <span class="directory-branch-badge" style="--branch-color: {{ $primaryColor }}; --branch-bg: {{ $softColor($primaryColor, .13) }}">
                                            <span class="directory-branch-dot" style="--branch-color: {{ $primaryColor }}"></span>
                                            {{ $branchLabel($directoryUser->branch) }}
                                        </span>
                                    @else
                                        <span class="text-muted">غير محدد</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse ($directoryUser->assignedBranches as $branch)
                                        @php($color = $branchColor($branch))
                                        <span class="directory-branch-badge" style="--branch-color: {{ $color }}; --branch-bg: {{ $softColor($color, .11) }}">
                                            <span class="directory-branch-dot" style="--branch-color: {{ $color }}"></span>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-directory-select]').forEach((selectRoot) => {
        const key = selectRoot.dataset.directorySelect;
        const input = document.querySelector(`[data-directory-select-input="${key}"]`);
        const label = selectRoot.querySelector('[data-directory-select-label]');
        const search = selectRoot.querySelector('[data-directory-select-search]');
        const options = Array.from(selectRoot.querySelectorAll('[data-value]'));

        options.forEach((option) => {
            option.addEventListener('click', () => {
                if (input) input.value = option.dataset.value || '';
                if (label) {
                    label.innerHTML = option.innerHTML;
                }
                options.forEach((item) => item.classList.toggle('is-selected', item === option));
            });
        });

        search?.addEventListener('input', () => {
            const needle = search.value.trim().toLowerCase();
            options.forEach((option) => {
                const haystack = (option.dataset.label || option.textContent || '').toLowerCase();
                option.classList.toggle('d-none', needle !== '' && ! haystack.includes(needle));
            });
        });
    });
});
</script>
@endpush
