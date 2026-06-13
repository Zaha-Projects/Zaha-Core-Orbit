@extends('layouts.app')

@php
    $labels = [
        'total_delete_requests' => 'إجمالي طلبات الحذف', 'pending_delete_requests' => 'طلبات الحذف المعلقة',
        'approved_delete_requests' => 'طلبات الحذف الموافق عليها', 'rejected_delete_requests' => 'طلبات الحذف المرفوضة',
        'total_edit_requests' => 'إجمالي طلبات التعديل', 'pending_edit_requests' => 'طلبات التعديل المعلقة',
        'approved_edit_requests' => 'طلبات التعديل الموافق عليها', 'rejected_edit_requests' => 'طلبات التعديل المرفوضة',
        'soft_deleted_monthly_activities' => 'أنشطة محذوفة ناعمًا', 'activities_with_versions' => 'أنشطة لديها إصدارات',
    ];
@endphp

@section('content')
<div class="container-fluid py-4">
    <h1 class="h4 mb-1">تقارير طلبات تغيير الأنشطة الشهرية</h1>
    <p class="text-muted">إحصاءات وملخصات طلبات الحذف والتعديل للمديرين.</p>

    <div class="card mb-3"><div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-3"><label class="form-label">الفرع</label><select name="branch_id" class="form-select"><option value="">الكل</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)($filters['branch_id'] ?? '') === (string)$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
            <div class="col-12 col-md-2"><label class="form-label">نوع الطلب</label><select name="request_type" class="form-select"><option value="">الكل</option><option value="delete" @selected(($filters['request_type'] ?? '')==='delete')>حذف</option><option value="edit" @selected(($filters['request_type'] ?? '')==='edit')>تعديل</option></select></div>
            <div class="col-12 col-md-2"><label class="form-label">الحالة</label><input name="status" class="form-control" value="{{ $filters['status'] ?? '' }}"></div>
            <div class="col-12 col-md-2"><label class="form-label">الخطوة الحالية</label><input name="current_step" class="form-control" value="{{ $filters['current_step'] ?? '' }}"></div>
            <div class="col-12 col-md-3"><label class="form-label">مقدم الطلب</label><select name="requester_id" class="form-select"><option value="">الكل</option>@foreach($requesters as $requester)<option value="{{ $requester->id }}" @selected((string)($filters['requester_id'] ?? '') === (string)$requester->id)>{{ $requester->name }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label">من تاريخ</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-6 col-md-2"><label class="form-label">إلى تاريخ</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-12 col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100" type="submit">تطبيق</button></div>
        </form>
    </div></div>

    <div class="row g-3 mb-4">
        @foreach($labels as $key => $label)
            <div class="col-6 col-md-3 col-xl-2"><div class="card h-100"><div class="card-body"><div class="small text-muted">{{ $label }}</div><div class="h4 mb-0">{{ $statistics[$key] }}</div></div></div></div>
        @endforeach
    </div>

    @include('pages.monthly_activities.reports.partials.requests_table', ['title' => 'أحدث طلبات الحذف', 'requests' => $recentDeleteRequests])
    @include('pages.monthly_activities.reports.partials.requests_table', ['title' => 'أحدث طلبات التعديل', 'requests' => $recentEditRequests])

    <div class="row g-3">
        @foreach(['طلبات حسب الفرع' => $requestsByBranch, 'طلبات حسب الحالة' => $requestsByStatus, 'طلبات حسب الخطوة/المعتمد الحالي' => $requestsByStep] as $title => $items)
            <div class="col-12 col-lg-4"><div class="card h-100"><div class="card-header fw-semibold">{{ $title }}</div><div class="table-responsive"><table class="table mb-0"><tbody>@forelse($items as $name => $count)<tr><td>{{ $name }}</td><td class="text-end fw-semibold">{{ $count }}</td></tr>@empty<tr><td class="text-muted">لا توجد بيانات.</td></tr>@endforelse</tbody></table></div></div></div>
        @endforeach
    </div>
</div>
@endsection
