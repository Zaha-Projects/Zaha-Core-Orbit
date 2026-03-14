@extends('layouts.app')

@php
    $title = __('app.reports.title');
    $subtitle = __('app.reports.subtitle');
    $reportStatusLabel = function (?string $value): string {
        if (!$value) {
            return '-';
        }

        $translated = __('app.reports.status.value_labels.' . $value);

        return $translated !== 'app.reports.status.value_labels.' . $value ? $translated : $value;
    };

    $reportDecisionLabel = function (?string $value): string {
        if (!$value) {
            return '-';
        }

        $translated = __('app.reports.status.decision_labels.' . $value);

        return $translated !== 'app.reports.status.decision_labels.' . $value ? $translated : $value;
    };
@endphp

@section('page_title', $title)
@section('page_breadcrumb', $title)
@section('enable_header_search', '1')

@section('content')
    <div class="card stretch stretch-full mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.structure.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.structure.subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.branches') }}</span>
                            <strong>{{ $overview['branches'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.centers') }}</span>
                            <strong>{{ $overview['centers'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.users') }}</span>
                            <strong>{{ $overview['users'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.vehicles') }}</span>
                            <strong>{{ $overview['vehicles'] }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.operations.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.operations.subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.agenda') }}</span>
                            <strong>{{ $operations['agenda_events'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.monthly_activities') }}</span>
                            <strong>{{ $operations['monthly_activities'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.bookings') }}</span>
                            <strong>{{ $operations['bookings'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.maintenance_requests') }}</span>
                            <strong>{{ $operations['maintenance_requests'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.trips') }}</span>
                            <strong>{{ $operations['trips'] }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.financials.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.financials.subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.payments') }}</span>
                            <strong>{{ $financials['payments'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.payments_total') }}</span>
                            <strong>{{ number_format($financials['payments_total'], 2) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.donations') }}</span>
                            <strong>{{ $financials['donations'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.donations_total') }}</span>
                            <strong>{{ number_format($financials['donations_total'], 2) }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.narrative.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.narrative.body') }}</p>
                    <ul class="mb-0">
                        <li>{{ __('app.reports.narrative.points.0') }}</li>
                        <li>{{ __('app.reports.narrative.points.1') }}</li>
                        <li>{{ __('app.reports.narrative.points.2') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.status.maintenance') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.status.maintenance_subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($maintenanceStatus as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $reportStatusLabel($item->status) }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.status.agenda_approvals') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.status.agenda_approvals_subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($agendaApprovals as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $reportDecisionLabel($item->decision) }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.status.bookings') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.status.bookings_subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($bookingStatus as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $reportStatusLabel($item->status) }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>



    <div class="card stretch stretch-full mt-4">
        <div class="card-body enterprise-dashboard">
            <h2 class="h5 mb-3">{{ __('app.enterprise.analytics_title') }}</h2>
            <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('role.super_admin.reports') }}">
                <div class="col-md-3">
                    <label class="form-label">{{ __('app.enterprise.year') }}</label>
                    <select class="form-select" name="year">
                        @foreach ($years as $option)
                            <option value="{{ $option }}" @selected(($enterpriseFilters['year'] ?? now()->year) == $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">{{ __('app.enterprise.apply') }}</button>
                </div>
            </form>

            @include('pages.enterprise.partials.kpis')
            @include('pages.enterprise.partials.charts')
            @include('pages.enterprise.partials.branch-performance')
        </div>
    </div>

    <div class="card stretch stretch-full">
        <div class="card-body">
            <h2 class="h5 mb-3">{{ __('app.reports.flowcharts.title') }}</h2>
            <p class="text-muted">{{ __('app.reports.flowcharts.subtitle') }}</p>
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.maintenance') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.maintenance') !!}</pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.agenda') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.agenda') !!}</pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.transport') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.transport') !!}</pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.bookings') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.bookings') !!}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ startOnLoad: true });
    </script>
@endpush


@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/enterprise-dashboard.css') }}">
@endpush

@include('pages.enterprise.partials.charts-scripts')
