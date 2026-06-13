<div class="wf-card card mb-4">
    <div class="card-header approvals-card-header">
        <h2 class="h6 mb-0"><i class="fas fa-chart-pie me-1" aria-hidden="true"></i>إحصائيات إدارية لطلبات الحذف والتعديل</h2>
    </div>
    <div class="card-body">
        <div class="approvals-kpi-row mb-3">
            @foreach(['delete' => 'طلبات الحذف', 'edit' => 'طلبات التعديل'] as $type => $label)
                <div class="approvals-kpi-card"><div class="approvals-kpi-label">إجمالي {{ $label }}</div><div class="approvals-kpi-value">{{ $stats[$type]['total'] ?? 0 }}</div></div>
                <div class="approvals-kpi-card"><div class="approvals-kpi-label">معلقة {{ $label }}</div><div class="approvals-kpi-value">{{ $stats[$type]['pending'] ?? 0 }}</div></div>
                <div class="approvals-kpi-card"><div class="approvals-kpi-label">معتمدة {{ $label }}</div><div class="approvals-kpi-value">{{ $stats[$type]['approved'] ?? 0 }}</div></div>
                <div class="approvals-kpi-card"><div class="approvals-kpi-label">مرفوضة {{ $label }}</div><div class="approvals-kpi-value">{{ $stats[$type]['rejected'] ?? 0 }}</div></div>
            @endforeach
        </div>
        <div class="row g-3">
            <div class="col-lg-6"><h3 class="h6">أحدث طلبات الحذف</h3><ul class="list-group">@forelse(($stats['recent_delete'] ?? []) as $item)<li class="list-group-item d-flex justify-content-between"><span>#{{ $item->id }} — {{ $item->requester?->name ?? '-' }}</span><span>{{ $item->status }}</span></li>@empty<li class="list-group-item text-muted">لا توجد بيانات</li>@endforelse</ul></div>
            <div class="col-lg-6"><h3 class="h6">أحدث طلبات التعديل</h3><ul class="list-group">@forelse(($stats['recent_edit'] ?? []) as $item)<li class="list-group-item d-flex justify-content-between"><span>#{{ $item->id }} — {{ $item->requester?->name ?? '-' }}</span><span>{{ $item->status }}</span></li>@empty<li class="list-group-item text-muted">لا توجد بيانات</li>@endforelse</ul></div>
        </div>
    </div>
    <div class="card-footer approvals-card-footer small text-muted">تتأثر الإحصائيات بالفلاتر الحالية: الفرع، نوع الطلب عبر التبويب، الحالة، والخطوة والتاريخ.</div>
</div>
