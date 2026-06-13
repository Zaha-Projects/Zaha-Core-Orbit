<div class="card mb-3">
    <div class="card-header fw-semibold">{{ $title }}</div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>#</th><th>النشاط</th><th>الفرع</th><th>مقدم الطلب</th><th>الحالة</th><th>الخطوة الحالية</th><th>المعتمد الحالي</th><th>تاريخ الطلب</th></tr></thead>
        <tbody>@forelse($requests as $request)<tr><td>{{ $request->id }}</td><td>{{ $request->monthlyActivity?->title ?? '-' }}</td><td>{{ $request->monthlyActivity?->branch?->name ?? '-' }}</td><td>{{ $request->requester?->name ?? '-' }}</td><td>{{ $request->status }}</td><td>{{ $request->workflowInstance?->currentStep?->name_ar ?? '-' }}</td><td>{{ $request->currentApprover?->name ?? '-' }}</td><td>{{ optional($request->requested_at)->format('Y-m-d H:i') }}</td></tr>@empty<tr><td colspan="8" class="text-muted text-center py-3">لا توجد طلبات.</td></tr>@endforelse</tbody>
    </table></div>
</div>
