@extends('layouts.app')

@section('title', 'الملف الشخصي')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/pages/profile-show.min.css') }}">
@endpush

@section('content')
@php
    $primaryBranch = $user->branch?->name ?: 'غير محدد';
    $branchCities = $user->assignedBranches->pluck('city')->filter()->values();
@endphp
<div class="container-fluid py-4 profile-page">
    <div class="profile-hero mb-4">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-4 align-items-lg-center">
                <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                    <div class="profile-avatar">
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                    <div>
                        <div class="profile-chip mb-2"><i class="fas fa-id-card"></i> حسابي في منصة زها</div>
                        <h1 class="display-6 fw-bold mb-2">{{ $user->name }}</h1>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="profile-chip"><i class="fas fa-envelope"></i>{{ $user->email }}</span>
                            <span class="profile-chip"><i class="fas fa-location-dot"></i>{{ $primaryBranch }}</span>
                        </div>
                    </div>
                </div>
                <a class="btn btn-light btn-lg text-primary fw-bold rounded-pill px-4" href="{{ route('directory.users.index') }}">
                    <i class="fas fa-address-book me-1"></i> دليل المستخدمين
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="fas fa-circle-check me-1"></i>{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card profile-card h-100">
                <div class="card-body">
                    <div class="profile-section-title">
                        <span class="icon"><i class="fas fa-address-card"></i></span>
                        <div>
                            <h2 class="h5 mb-0 fw-bold">بطاقة المستخدم</h2>
                            <div class="small text-muted">بيانات الحساب والصلاحيات</div>
                        </div>
                    </div>

                    <dl class="row small mb-0 profile-meta-list g-3">
                        <dt class="col-5">الهاتف</dt>
                        <dd class="col-7">{{ $user->phone ?: 'غير محدد' }}</dd>

                        <dt class="col-5">الفرع الرئيسي</dt>
                        <dd class="col-7">{{ $primaryBranch }}</dd>

                        <dt class="col-5">الأدوار</dt>
                        <dd class="col-7">
                            @forelse ($user->roles as $role)
                                <span class="badge rounded-pill text-bg-light border mb-1 px-3 py-2">{{ $role->display_name }}</span>
                            @empty
                                <span class="text-muted">لا يوجد</span>
                            @endforelse
                        </dd>

                        <dt class="col-5">الفروع المكلف بها</dt>
                        <dd class="col-7">
                            @forelse ($user->assignedBranches as $branch)
                                <span class="badge rounded-pill text-bg-primary mb-1 px-3 py-2">{{ $branch->city ?: $branch->name }}</span>
                            @empty
                                <span class="text-muted">{{ $user->branch?->city ?: 'لا يوجد' }}</span>
                            @endforelse
                        </dd>
                    </dl>

                    @if ($canManageEmailEditing)
                        <div class="profile-admin-toggle mt-4">
                            <form method="POST" action="{{ route('profile.email_editing.update') }}" class="d-flex flex-column gap-3">
                                @csrf
                                @method('PATCH')
                                <div class="d-flex justify-content-between gap-3 align-items-start">
                                    <div>
                                        <div class="fw-bold text-primary"><i class="fas fa-lock-open me-1"></i>صلاحية تعديل البريد</div>
                                        <div class="small text-muted">زر خاص بالإدارة لتحديد هل يمكن للمستخدمين تعديل بريدهم من البروفايل.</div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input type="hidden" name="allow_profile_email_edit" value="0">
                                        <input class="form-check-input" type="checkbox" role="switch" name="allow_profile_email_edit" value="1" id="allow_profile_email_edit" @checked($emailEditingEnabled)>
                                    </div>
                                </div>
                                <button class="btn btn-primary rounded-pill" type="submit">
                                    {{ $emailEditingEnabled ? 'إبقاء التعديل مفعّلًا' : 'تفعيل تعديل البريد للمستخدمين' }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="alert alert-light border shadow-sm mb-4 small text-muted">
                <i class="fas fa-circle-info me-1"></i>
                سيتم عرض مؤشرات الأداء الشخصية هنا بعد اعتماد نظام مؤشرات الأداء الجديد.
            </div>

            <div class="card profile-card profile-form-card">
                <div class="card-body">
                    <div class="profile-section-title">
                        <span class="icon"><i class="fas fa-user-pen"></i></span>
                        <div>
                            <h2 class="h5 mb-0 fw-bold">تعديل بياناتي</h2>
                            <div class="small text-muted">حدّث بيانات التواصل وكلمة المرور بأمان</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('profile.update') }}" class="row g-3" id="profileForm">
                        @csrf
                        @method('PUT')
                        <div class="col-md-6">
                            <label class="form-label">الاسم</label>
                            <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">رقم الهاتف</label>
                            <input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" inputmode="tel" dir="ltr">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label d-flex justify-content-between align-items-center">
                                <span>البريد الإلكتروني</span>
                                @unless ($canEditProfileEmail)
                                    <span class="badge rounded-pill text-bg-secondary"><i class="fas fa-lock me-1"></i>مقفل</span>
                                @endunless
                            </label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror {{ $canEditProfileEmail ? '' : 'profile-readonly-email' }}" name="email" value="{{ old('email', $user->email) }}" {{ ! $canEditProfileEmail ? 'disabled' : '' }} {{ $canEditProfileEmail ? 'required' : '' }}>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">{{ $canEditProfileEmail ? 'يمكنك تعديل البريد لأن الإدارة فعّلت هذه الصلاحية.' : 'البريد للعرض فقط. يحتاج تفعيلًا من الإدارة لتعديله.' }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الفرع</label>
                            <input class="form-control" value="{{ $primaryBranch }}" disabled>
                            <div class="form-text">لا يمكن تعديل الفرع من الملف الشخصي.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">كلمة السر الجديدة</label>
                            <div class="input-group profile-password-actions">
                                <input type="password" class="form-control @error('password') is-invalid @enderror js-profile-password" name="password" autocomplete="new-password" id="profilePassword">
                                <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="#profilePassword" aria-label="عرض كلمة السر"><i class="fas fa-eye"></i></button>
                                <button class="btn btn-outline-primary js-generate-password" type="button"><i class="fas fa-wand-magic-sparkles me-1"></i>توليد</button>
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">اتركها فارغة إذا لم ترغب بتغيير كلمة المرور.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تأكيد كلمة السر</label>
                            <div class="input-group profile-password-actions">
                                <input type="password" class="form-control js-profile-password-confirmation" name="password_confirmation" autocomplete="new-password" id="profilePasswordConfirmation">
                                <button class="btn btn-outline-secondary js-toggle-password" type="button" data-target="#profilePasswordConfirmation" aria-label="عرض تأكيد كلمة السر"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                            <button class="btn btn-primary btn-lg rounded-pill px-5" type="submit"><i class="fas fa-floppy-disk me-1"></i>حفظ التعديلات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ \App\Support\AssetVersion::url('assets/js/pages/profile-show.min.js') }}"></script>
@endpush
