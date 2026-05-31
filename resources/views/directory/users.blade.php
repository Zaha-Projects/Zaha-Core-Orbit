@extends('layouts.app')

@section('title', 'دليل المستخدمين')

@push('styles')
<style>
    .directory-pagination-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        margin-top: 1rem;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 1rem;
        background: linear-gradient(135deg, #f8fafc 0%, #eef5ff 100%);
    }

    .directory-pagination-summary {
        color: #64748b;
        font-size: .9rem;
        font-weight: 600;
    }

    .directory-pagination-bar nav {
        margin: 0;
    }

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
        font-weight: 700;
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

    [data-theme="dark"] .directory-pagination-bar {
        background: linear-gradient(135deg, rgba(15, 23, 42, .92) 0%, rgba(30, 41, 59, .92) 100%);
        border-color: var(--border-color);
    }

    [data-theme="dark"] .directory-pagination-summary {
        color: var(--muted-color);
    }

    [data-theme="dark"] .directory-pagination-bar .page-link {
        background: var(--surface-bg);
        border-color: var(--border-color);
        color: #93c5fd;
    }

    [data-theme="dark"] .directory-pagination-bar .page-link:hover,
    [data-theme="dark"] .directory-pagination-bar .page-link:focus {
        background: rgba(37, 99, 235, .16);
        border-color: rgba(147, 197, 253, .45);
        color: #bfdbfe;
    }

    [data-theme="dark"] .directory-pagination-bar .page-item.disabled .page-link {
        background: var(--surface-soft);
        color: var(--muted-color);
    }

    @media (max-width: 575.98px) {
        .directory-pagination-bar {
            justify-content: center;
            text-align: center;
        }

        .directory-pagination-summary {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-4">
        <div>
            <p class="text-muted mb-1">دليل عام</p>
            <h1 class="h3 mb-0">دليل المستخدمين</h1>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('profile.show') }}">
            <i class="fas fa-user me-1"></i> ملفي الشخصي
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('directory.users.index') }}" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">الفرع</label>
                    <select class="form-select" name="branch_id">
                        <option value="">كل الفروع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$branch->id)>{{ $branch->city ?: $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">الدور</label>
                    <select class="form-select" name="role">
                        <option value="">كل الأدوار</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ $role->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary" type="submit">فلترة</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الدور</th>
                            <th>الفرع</th>
                            <th>الفروع المكلف بها</th>
                            <th>الهاتف</th>
                            <th>البريد الإلكتروني</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $directoryUser)
                            <tr>
                                <td class="fw-semibold">{{ $directoryUser->name }}</td>
                                <td>
                                    @forelse ($directoryUser->roles as $role)
                                        <span class="badge text-bg-light border mb-1">{{ $role->display_name }}</span>
                                    @empty
                                        <span class="text-muted">لا يوجد</span>
                                    @endforelse
                                </td>
                                <td>{{ $directoryUser->branch?->city ?: 'غير محدد' }}</td>
                                <td>
                                    @forelse ($directoryUser->assignedBranches as $branch)
                                        <span class="badge text-bg-primary-subtle text-primary border mb-1">{{ $branch->city ?: $branch->name }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>{{ $directoryUser->phone ?: '-' }}</td>
                                <td><a href="mailto:{{ $directoryUser->email }}">{{ $directoryUser->email }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا يوجد مستخدمون مطابقون للفلاتر.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="directory-pagination-bar" aria-label="تنقل صفحات دليل المستخدمين">
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
