@extends('layouts.app')

@section('title', 'الملف الشخصي')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-4">
        <div>
            <p class="text-muted mb-1">حسابي</p>
            <h1 class="h3 mb-0">الملف الشخصي</h1>
        </div>
        <a class="btn btn-outline-primary" href="{{ route('directory.users.index') }}">
            <i class="fas fa-address-book me-1"></i> دليل المستخدمين
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <div>
                            <h2 class="h5 mb-1">{{ $user->name }}</h2>
                            <div class="text-muted small">{{ $user->email }}</div>
                        </div>
                    </div>

                    <dl class="row small mb-0">
                        <dt class="col-5">الهاتف</dt>
                        <dd class="col-7">{{ $user->phone ?: 'غير محدد' }}</dd>

                        <dt class="col-5">الفرع الرئيسي</dt>
                        <dd class="col-7">{{ $user->branch?->name ?: 'غير محدد' }}</dd>

                        <dt class="col-5">الأدوار</dt>
                        <dd class="col-7">
                            @forelse ($user->roles as $role)
                                <span class="badge text-bg-light border mb-1">{{ $role->display_name }}</span>
                            @empty
                                <span class="text-muted">لا يوجد</span>
                            @endforelse
                        </dd>

                        <dt class="col-5">الفروع المكلف بها</dt>
                        <dd class="col-7">
                            @forelse ($user->assignedBranches as $branch)
                                <span class="badge text-bg-primary-subtle text-primary border mb-1">{{ $branch->city ?: $branch->name }}</span>
                            @empty
                                <span class="text-muted">{{ $user->branch?->city ?: 'لا يوجد' }}</span>
                            @endforelse
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">أنشطة أنشأتها</div><div class="h3 mb-0">{{ $stats['created_monthly_activities'] }}</div></div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">أنشطة فروعك</div><div class="h3 mb-0">{{ $stats['assigned_branch_activities'] }}</div></div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">أنشطة مكتملة</div><div class="h3 mb-0">{{ $stats['completed_branch_activities'] }}</div></div></div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small">إجراءات Workflow</div><div class="h3 mb-0">{{ $stats['workflow_actions'] }}</div></div></div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">التقييمات</h2>
                        <span class="badge text-bg-info">Cached 15 min</span>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><div class="border rounded-3 p-3"><div class="small text-muted">مكلف بها</div><strong>{{ $evaluations['assigned_count'] }}</strong></div></div>
                        <div class="col-md-3"><div class="border rounded-3 p-3"><div class="small text-muted">مكتملة</div><strong>{{ $evaluations['completed_count'] }}</strong></div></div>
                        <div class="col-md-3"><div class="border rounded-3 p-3"><div class="small text-muted">متوسط الدرجة</div><strong>{{ $evaluations['average_score'] ?: '0' }}</strong></div></div>
                        <div class="col-md-3"><div class="border rounded-3 p-3"><div class="small text-muted">إجابات التقييم</div><strong>{{ $evaluations['responses_count'] }}</strong></div></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>النشاط</th><th>الدرجة</th><th>آخر تحديث</th></tr></thead>
                            <tbody>
                                @forelse ($evaluations['latest'] as $evaluation)
                                    <tr><td>{{ $evaluation['title'] }}</td><td>{{ $evaluation['score'] }}</td><td>{{ $evaluation['date'] }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted text-center py-3">لا توجد تقييمات مكتملة.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">تعديل بياناتي</h2>
                    <form method="POST" action="{{ route('profile.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label class="form-label">الاسم</label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهاتف</label>
                            <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الفرع</label>
                            <input class="form-control" value="{{ $user->branch?->name ?: 'غير محدد' }}" disabled>
                            <div class="form-text">لا يمكن تعديل الفرع من الملف الشخصي.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">كلمة السر الجديدة</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تأكيد كلمة السر</label>
                            <input type="password" class="form-control" name="password_confirmation" autocomplete="new-password">
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn-primary" type="submit">حفظ التعديلات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
