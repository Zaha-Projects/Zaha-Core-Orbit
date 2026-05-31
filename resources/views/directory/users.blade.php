@extends('layouts.app')

@section('title', 'دليل المستخدمين')

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
            <div class="mt-3">{{ $users->links() }}</div>
        </div>
    </div>
</div>
@endsection
