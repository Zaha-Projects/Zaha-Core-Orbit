@extends('layouts.app')

@php
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path) . '?v=' . $version;
    };
    $latestKpi = $kpis->first();
@endphp

@section('content')
    <div class="kpi-reports-page">
    <div class="card shadow-sm mb-4 kpi-hero-card">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
            <h1 class="h4 mb-2">{{ __('app.roles.reports.kpis.title') }}</h1>
            <p class="text-muted mb-0">{{ __('app.roles.reports.kpis.subtitle') }}</p>
            </div>
            @if ($latestKpi)
                <div class="kpi-period-badge">
                    آخر تحديث: {{ $latestKpi->year }}-{{ str_pad($latestKpi->month, 2, '0', STR_PAD_LEFT) }}
                </div>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($latestKpi)
        <div class="kpi-summary-grid mb-4">
            <article class="kpi-summary-card">
                <div class="kpi-summary-card__label">{{ __('app.roles.reports.kpis.table.plan_commitment') }}</div>
                <div class="kpi-summary-card__value">{{ $latestKpi->plan_commitment_percent ?? '-' }}%</div>
            </article>
            <article class="kpi-summary-card">
                <div class="kpi-summary-card__label">{{ __('app.roles.reports.kpis.table.mobilization_efficiency') }}</div>
                <div class="kpi-summary-card__value">{{ $latestKpi->mobilization_efficiency_percent ?? '-' }}%</div>
            </article>
            <article class="kpi-summary-card">
                <div class="kpi-summary-card__label">{{ __('app.roles.reports.kpis.table.branch_score') }}</div>
                <div class="kpi-summary-card__value">{{ $latestKpi->branch_monthly_score ?? '-' }}%</div>
            </article>
            <article class="kpi-summary-card">
                <div class="kpi-summary-card__label">{{ __('app.roles.reports.kpis.table.followup_score') }}</div>
                <div class="kpi-summary-card__value">{{ $latestKpi->followup_commitment_score ?? '-' }}%</div>
            </article>
        </div>
    @endif

    @if (auth()->user()->hasRole('followup_officer'))
        <div class="card shadow-sm mb-4 kpi-form-card">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ __('app.roles.reports.kpis.form_title') }}</h2>
                <form method="POST" action="{{ route('role.reports.kpis.store') }}" class="row g-3 kpi-form-grid">
                    @csrf
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.reports.kpis.fields.year') }}</label>
                        <input class="form-control" type="number" name="year" value="{{ now()->year }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.reports.kpis.fields.month') }}</label>
                        <input class="form-control" type="number" min="1" max="12" name="month" value="{{ now()->month }}">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.reports.kpis.fields.branch') }}</label>
                        <select class="form-select" name="branch_id">
                            <option value="">{{ __('app.roles.reports.kpis.common.all') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3"><label class="form-label">{{ __('app.roles.reports.kpis.fields.planned_activities_count') }}</label><input class="form-control" type="number" min="0" name="planned_activities_count"></div>
                    <div class="col-6 col-md-3"><label class="form-label">{{ __('app.roles.reports.kpis.fields.unplanned_activities_count') }}</label><input class="form-control" type="number" min="0" name="unplanned_activities_count"></div>
                    <div class="col-6 col-md-3"><label class="form-label">{{ __('app.roles.reports.kpis.fields.modification_rate_percent') }}</label><input class="form-control" type="number" min="0" max="100" name="modification_rate_percent"></div>
                    <div class="col-6 col-md-3"><label class="form-label">{{ __('app.roles.reports.kpis.fields.plan_commitment_percent') }}</label><input class="form-control" type="number" min="0" max="100" name="plan_commitment_percent"></div>
                    <div class="col-6 col-md-4"><label class="form-label">{{ __('app.roles.reports.kpis.fields.mobilization_efficiency_percent') }}</label><input class="form-control" type="number" min="0" max="100" name="mobilization_efficiency_percent"></div>
                    <div class="col-6 col-md-4"><label class="form-label">{{ __('app.roles.reports.kpis.fields.branch_monthly_score') }}</label><input class="form-control" type="number" min="0" max="100" name="branch_monthly_score"></div>
                    <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.reports.kpis.fields.followup_commitment_score') }}</label><input class="form-control" type="number" min="0" max="100" name="followup_commitment_score"></div>
                    <div class="col-12"><label class="form-label">{{ __('app.roles.reports.kpis.fields.notes') }}</label><input class="form-control" name="notes"></div>
                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit">{{ __('app.roles.reports.kpis.actions.save') }}</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card shadow-sm kpi-table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle kpi-table">
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
                                <td>{{ $kpi->branch?->name ?? __('app.roles.reports.kpis.common.all') }}</td>
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
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/reports-kpis.css') }}">
@endpush
