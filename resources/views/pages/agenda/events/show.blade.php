@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.show_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
    $statusLabel = function (?string $status): string {
        if (!$status) return '-';
        $translated = __('app.roles.relations.agenda.status_labels.' . $status);

        return $translated !== 'app.roles.relations.agenda.status_labels.' . $status ? $translated : $status;
    };
@endphp

@section('content')
    <div class="event-module">
        <div class="event-header mb-3 d-flex justify-content-between align-items-start">
            <div>
                <h1 class="h4 mb-1">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('role.relations.agenda.index') }}">{{ __('app.common.back') }}</a>
        </div>

        <div class="card event-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>{{ __('app.roles.relations.agenda.fields.event_name') }}:</strong> {{ $agendaEvent->event_name }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields.event_date') }}:</strong> {{ optional($agendaEvent->event_date)->format('Y-m-d') ?? '-' }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.department') }}:</strong> {{ $agendaEvent->department?->icon }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $agendaEvent->department?->color_hex ?? '#94a3b8' }}"></span> {{ $agendaEvent->department?->name ?? '-' }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields.event_category') }}:</strong> {{ $agendaEvent->eventCategory?->name ?? $agendaEvent->event_category ?? '-' }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.event_type') }}:</strong> {{ __('app.roles.relations.agenda.types.' . $agendaEvent->event_type) }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.plan_type') }}:</strong> {{ __('app.roles.relations.agenda.plans.' . $agendaEvent->plan_type) }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.review_status') }}:</strong> {{ $statusLabel($agendaEvent->status) }}</div>
                    <div class="col-md-12"><strong>{{ __('app.roles.relations.agenda.fields.notes') }}:</strong> {{ $agendaEvent->notes ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card event-card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.fields_ext.partner_department') }}</h2>
                        <ul class="mb-0 ps-3">
                            @forelse($agendaEvent->partnerDepartments as $partnerDepartment)
                                <li>{{ $partnerDepartment->icon }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $partnerDepartment->color_hex ?? '#94a3b8' }}"></span> {{ $partnerDepartment->name }}</li>
                            @empty
                                <li class="text-muted">-</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card event-card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.fields_ext.unit_participation') }}</h2>
                        <ul class="mb-0 ps-3">
                            @forelse($unitParticipations as $unit)
                                <li>{{ $unit['icon'] ?? '' }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $unit['color_hex'] ?? '#94a3b8' }}"></span> {{ $unit['name'] }} - {{ __('app.roles.relations.agenda.participation.' . ($unit['status'] ?? 'unspecified')) }}</li>
                            @empty
                                <li class="text-muted">-</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.fields_ext.branch_participation') }}</h2>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('app.roles.relations.agenda.target_types.branch') }}</th>
                                        <th>{{ __('app.roles.relations.agenda.fields_ext.review_status') }}</th>
                                        <th>📌 {{ __('app.roles.relations.agenda.fields.event_date') }} (مقترح)</th>
                                        <th>✅ {{ __('app.roles.relations.agenda.fields.event_date') }} (تنفيذ)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($branchParticipations as $branch)
                                        <tr>
                                            <td>{{ $branch['icon'] ?? '' }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $branch['color_hex'] ?? '#94a3b8' }}"></span> {{ $branch['name'] }}</td>
                                            <td>{{ __('app.roles.relations.agenda.participation.' . ($branch['status'] ?? 'unspecified')) }}</td>
                                            <td>{{ optional($branch['proposed_date'])->format('Y-m-d') ?? '-' }}</td>
                                            <td>{{ optional($branch['actual_execution_date'])->format('Y-m-d') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-muted">-</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.title') }}</h2>
                        <ul class="mb-0 ps-3">
                            @forelse($agendaEvent->monthlyActivities as $activity)
                                <li>{{ $activity->title }} ({{ optional($activity->activity_date)->format('Y-m-d') ?? '-' }})</li>
                            @empty
                                <li class="text-muted">-</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
