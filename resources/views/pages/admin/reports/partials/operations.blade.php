<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card h-100 stretch stretch-full"><div class="card-body">
            <h2 class="h6">{{ __('app.reports.operations.title') }}</h2>
            <p class="text-muted small">{{ __('app.reports.operations.subtitle') }}</p>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.operations.agenda') }}</span><strong>{{ $reportData['operations']['agenda_events'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.operations.monthly_activities') }}</span><strong>{{ $reportData['operations']['monthly_activities'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.operations.bookings') }}</span><strong>{{ $reportData['operations']['bookings'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.operations.maintenance_requests') }}</span><strong>{{ $reportData['operations']['maintenance_requests'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.operations.trips') }}</span><strong>{{ $reportData['operations']['trips'] }}</strong></li>
            </ul>
        </div></div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100 stretch stretch-full"><div class="card-body">
            <h2 class="h6">مؤشرات احتياجات التنفيذ</h2>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>أنشطة فيها احتياجات تنفيذ محفوظة</span><strong>{{ $reportData['execution_needs']['with_payload'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>أنشطة فيها متابعة بعد التنفيذ</span><strong>{{ $reportData['execution_needs']['with_followup'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>احتياجات تم تأمينها</span><strong>{{ $reportData['execution_needs']['secured_count'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>احتياجات لم يتم تأمينها</span><strong>{{ $reportData['execution_needs']['not_secured_count'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>متوسط فعالية التأمين /10</span><strong>{{ $reportData['execution_needs']['avg_effectiveness'] ?? '-' }}</strong></li>
            </ul>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-4">
        <div class="card h-100 stretch stretch-full"><div class="card-body">
            <h2 class="h6">{{ __('app.reports.status.maintenance') }}</h2>
            <ul class="list-group list-group-flush">
                @forelse ($reportData['statuses']['maintenance'] as $item)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $reportStatusLabel($item->status) }}</span><strong>{{ $item->total }}</strong></li>
                @empty <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li> @endforelse
            </ul>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100 stretch stretch-full"><div class="card-body">
            <h2 class="h6">{{ __('app.reports.status.agenda_approvals') }}</h2>
            <ul class="list-group list-group-flush">
                @forelse ($reportData['statuses']['agenda_approvals'] as $item)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $reportDecisionLabel($item->decision) }}</span><strong>{{ $item->total }}</strong></li>
                @empty <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li> @endforelse
            </ul>
        </div></div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="card h-100 stretch stretch-full"><div class="card-body">
            <h2 class="h6">{{ __('app.reports.status.bookings') }}</h2>
            <ul class="list-group list-group-flush">
                @forelse ($reportData['statuses']['bookings'] as $item)
                    <li class="list-group-item d-flex justify-content-between"><span>{{ $reportStatusLabel($item->status) }}</span><strong>{{ $item->total }}</strong></li>
                @empty <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li> @endforelse
            </ul>
        </div></div>
    </div>
</div>
