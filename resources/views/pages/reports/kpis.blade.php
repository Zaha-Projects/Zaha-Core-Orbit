@extends('layouts.app')


@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ __('app.roles.reports.kpis.title') }}</h1>
            <p class="text-muted mb-0">{{ __('app.roles.reports.kpis.subtitle') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (auth()->user()->hasRole('followup_officer'))
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ __('app.roles.reports.kpis.form_title') }}</h2>
                <form method="POST" action="{{ route('role.reports.kpis.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.year') }}</label><input class="form-control" type="number" name="year" value="{{ now()->year }}" required></div>
                    <div class="col-12 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.month') }}</label><input class="form-control" type="number" min="1" max="12" name="month" value="{{ now()->month }}" required></div>
                    <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.reports.kpis.fields.branch') }}</label><select class="form-select" name="branch_id"><option value="">{{ __('app.roles.reports.kpis.common.all') }}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.reports.kpis.fields.center') }}</label><select class="form-select" name="center_id"><option value="">{{ __('app.roles.reports.kpis.common.all') }}</option>@foreach($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select></div>
                    <div class="col-6 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.planned_activities_count') }}</label><input class="form-control" type="number" min="0" name="planned_activities_count" required></div>
                    <div class="col-6 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.unplanned_activities_count') }}</label><input class="form-control" type="number" min="0" name="unplanned_activities_count" required></div>
                    <div class="col-6 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.modification_rate_percent') }}</label><input class="form-control" type="number" min="0" max="100" name="modification_rate_percent"></div>
                    <div class="col-6 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.plan_commitment_percent') }}</label><input class="form-control" type="number" min="0" max="100" name="plan_commitment_percent"></div>
                    <div class="col-6 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.mobilization_efficiency_percent') }}</label><input class="form-control" type="number" min="0" max="100" name="mobilization_efficiency_percent"></div>
                    <div class="col-6 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.branch_monthly_score') }}</label><input class="form-control" type="number" min="0" max="100" name="branch_monthly_score"></div>
                    <div class="col-12 col-md-2"><label class="form-label">{{ __('app.roles.reports.kpis.fields.followup_commitment_score') }}</label><input class="form-control" type="number" min="0" max="100" name="followup_commitment_score"></div>
                    <div class="col-12 col-md-10"><label class="form-label">{{ __('app.roles.reports.kpis.fields.notes') }}</label><input class="form-control" name="notes"></div>
                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit">{{ __('app.roles.reports.kpis.actions.save') }}</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.reports.kpis.table.period') }}</th>
                            <th>{{ __('app.roles.reports.kpis.table.branch_center') }}</th>
                            <th>{{ __('app.roles.reports.kpis.table.planned_unplanned') }}</th>
                            <th>{{ __('app.roles.reports.kpis.table.modification_rate') }}</th>
                            <th>{{ __('app.roles.reports.kpis.table.plan_commitment') }}</th>
                            <th>{{ __('app.roles.reports.kpis.table.mobilization_efficiency') }}</th>
                            <th>{{ __('app.roles.reports.kpis.table.branch_score') }}</th>
                            <th>{{ __('app.roles.reports.kpis.table.followup_score') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kpis as $kpi)
                            <tr>
                                <td>{{ $kpi->year }}-{{ str_pad($kpi->month, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $kpi->branch?->name ?? __('app.roles.reports.kpis.common.all') }} / {{ $kpi->center?->name ?? __('app.roles.reports.kpis.common.all') }}</td>
                                <td>{{ $kpi->planned_activities_count }} / {{ $kpi->unplanned_activities_count }}</td>
                                <td>{{ $kpi->modification_rate_percent ?? '-' }}%</td>
                                <td>{{ $kpi->plan_commitment_percent ?? '-' }}%</td>
                                <td>{{ $kpi->mobilization_efficiency_percent ?? '-' }}%</td>
                                <td>{{ $kpi->branch_monthly_score ?? '-' }}%</td>
                                <td>{{ $kpi->followup_commitment_score ?? '-' }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">{{ __('app.roles.reports.kpis.table.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
