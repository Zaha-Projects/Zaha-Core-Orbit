@extends('layouts.app')

@php
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path) . '?v=' . $version;
    };
@endphp

@section('content')
    <div class="kpi-reports-page">
    <div class="card shadow-sm mb-4 kpi-hero-card">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
            <div>
            <h1 class="h4 mb-2">{{ __('app.roles.reports.kpis.title') }}</h1>
            <p class="text-muted mb-0">{{ __('app.roles.reports.kpis.subtitle') }}</p>
            </div>
        </div>
    </div>

    <div class="alert alert-warning border-0 shadow-sm">
        <i class="fas fa-box-archive me-1"></i>
        إدخال يدوي سابق: هذه المؤشرات أُدخلت يدوياً وتُعرض للاطلاع التاريخي فقط. تم إيقاف الإدخال اليدوي وسيتم استبدالها بمؤشرات محسوبة تلقائياً.
    </div>

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
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/reports-kpis.min.css') }}">
@endpush
