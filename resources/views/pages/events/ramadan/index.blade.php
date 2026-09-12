@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><h1 class="h3 mb-0">{{ __('ramadan_iftars.titles.index') }}</h1><div class="d-flex flex-wrap gap-2">
        @can('ramadan_iftars.approve')<a class="btn btn-outline-primary" href="{{ route('events.ramadan.approvals.index') }}">{{ __('ramadan_iftars.navigation.approvals') }}</a>@endcan
        @can('ramadan_iftars.monitor.review')<a class="btn btn-outline-dark" href="{{ route('events.ramadan.monitoring-reviews.index') }}">{{ __('ramadan_iftars.navigation.monitoring_reviews') }}</a>@endcan
        @can('ramadan_iftars.create')<a class="btn btn-primary" href="{{ route('events.ramadan.iftars.create') }}">{{ __('ramadan_iftars.actions.create') }}</a>@endcan
    </div></div>
    <div class="card"><div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>{{ __('ramadan_iftars.fields.id') }}</th><th>{{ __('ramadan_iftars.fields.title') }}</th><th>{{ __('ramadan_iftars.fields.branch') }}</th><th>{{ __('ramadan_iftars.fields.planned_date') }}</th><th>{{ __('ramadan_iftars.fields.relations_officer') }}</th><th>{{ __('ramadan_iftars.fields.planning_status') }}</th><th>{{ __('ramadan_iftars.fields.execution_status') }}</th><th>{{ __('ramadan_iftars.fields.closure_status') }}</th><th>{{ __('ramadan_iftars.sections.attendance') }}</th><th>{{ __('ramadan_iftars.sections.meals') }}</th><th>{{ __('ramadan_iftars.fields.current_step') }}</th></tr></thead>
        <tbody>@forelse($iftars as $iftar)<tr>
            <td>{{ $iftar->id }}</td><td><a href="{{ route('events.ramadan.iftars.show', $iftar) }}">{{ $iftar->title }}</a></td><td>{{ optional($iftar->branch)->name }}</td>
            <td>{{ optional($iftar->planned_date)->format('Y-m-d') }}</td><td>{{ optional($iftar->relationsOfficer)->name }}</td>
            <td><span class="badge bg-secondary">{{ __('ramadan_iftars.statuses.planning.'.$iftar->status) }}</span></td><td><span class="badge bg-info text-dark">{{ __('ramadan_iftars.statuses.execution.'.$iftar->execution_status) }}</span></td><td><span class="badge {{ $iftar->closed_at ? 'bg-dark' : 'bg-light text-dark' }}">{{ __('ramadan_iftars.statuses.closure.'.($iftar->closed_at ? 'closed' : 'open')) }}</span></td>
            <td>{{ $iftar->actual_attendance ?? '—' }} / {{ $iftar->expected_attendance }}</td><td>{{ $iftar->actual_meals_count ?? '—' }} / {{ $iftar->planned_meals_count }}</td>
            <td>{{ optional(optional($iftar->workflowInstance)->currentStep)->name_en ?? '—' }}</td>
        </tr>@empty<tr><td colspan="11" class="text-center text-muted py-4">{{ __('ramadan_iftars.empty.iftars') }}</td></tr>@endforelse</tbody>
    </table></div></div><div class="mt-3">{{ $iftars->links() }}</div>
</div>
@endsection
