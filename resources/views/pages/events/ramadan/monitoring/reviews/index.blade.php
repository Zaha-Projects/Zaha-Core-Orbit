@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">{{ __('ramadan_iftars.titles.monitoring_review_queue') }}</h1><p class="text-muted mb-0">{{ __('ramadan_iftars.hints.mismatch_documented') }}</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('events.ramadan.iftars.index') }}">{{ __('ramadan_iftars.actions.back') }}</a>
    </div>
    <div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>{{ __('ramadan_iftars.fields.title') }}</th><th>{{ __('ramadan_iftars.fields.branch') }}</th><th>{{ __('ramadan_iftars.fields.method') }}</th><th>{{ __('ramadan_iftars.fields.monitor') }}</th><th>{{ __('ramadan_iftars.fields.submitted_at') }}</th><th>{{ __('ramadan_iftars.fields.actions') }}</th></tr></thead>
        <tbody>@forelse($reports as $report)<tr><td>{{ optional($report->ramadanIftar)->title }}</td><td>{{ optional(optional($report->ramadanIftar)->branch)->name }}</td><td>{{ optional($report->monitoringMethod)->name_en ?: optional($report->monitoringMethod)->name_ar }}</td><td>{{ optional($report->monitor)->name }}</td><td>{{ optional($report->submitted_at)->format('Y-m-d H:i') }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('events.ramadan.monitoring-reviews.show', $report) }}">{{ __('ramadan_iftars.actions.review_monitoring') }}</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">{{ __('ramadan_iftars.empty.reviews') }}</td></tr>@endforelse</tbody>
    </table></div></div><div class="mt-3">{{ $reports->links() }}</div>
</div>
@endsection
