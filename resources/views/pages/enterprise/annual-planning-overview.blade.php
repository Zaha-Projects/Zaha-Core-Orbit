@extends('layouts.new-theme-dashboard')

@section('page_title', __('app.enterprise.annual_overview.title'))
@section('page_breadcrumb', __('app.enterprise.annual_overview.title'))

@section('theme_sidebar_links')
    <li class="side-item {{ request()->routeIs('role.enterprise.dashboard') ? 'selected' : '' }}">
        <a href="{{ route('role.enterprise.dashboard') }}"><i class="fas fa-chart-line"></i><span>{{ __('app.enterprise.dashboard.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.enterprise.annual_planning') ? 'selected' : '' }}">
        <a href="{{ route('role.enterprise.annual_planning') }}"><i class="fas fa-calendar"></i><span>{{ __('app.enterprise.annual_overview.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.reports.enterprise.branch_performance') ? 'selected' : '' }}">
        <a href="{{ route('role.reports.enterprise.branch_performance') }}"><i class="fas fa-building"></i><span>{{ __('app.enterprise.branch_report.title') }}</span></a>
    </li>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">{{ __('app.enterprise.annual_overview.title_with_year', ['year' => $year]) }}</h4>

            <div class="row g-3">
                @for ($month = 1; $month <= 12; $month++)
                    @php
                        $monthEvents = $events->get($month, collect());
                        $participationCount = $monthEvents->sum(
                            fn ($event) => $event->participations
                                ->where('entity_type', 'branch')
                                ->where('participation_status', 'participant')
                                ->count()
                        );
                    @endphp

                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="border rounded p-3 h-100 annual-planning-card">
                            <h6 class="mb-2">
                                {{ __('app.enterprise.annual_overview.month_label', ['month' => $month, 'count' => $monthEvents->count()]) }}
                            </h6>

                            @if ($monthEvents->isEmpty())
                                <p class="text-muted small mb-2">{{ __('app.enterprise.annual_overview.no_events') }}</p>
                            @else
                                <ul class="small mb-2 annual-planning-list">
                                    @foreach ($monthEvents->take(5) as $event)
                                        <li class="d-flex justify-content-between align-items-start gap-2">
                                            <span>{{ $event->event_name }}</span>
                                            <span class="badge bg-light text-dark">{{ $event->status }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="text-muted small">
                                {{ __('app.enterprise.annual_overview.participation', ['count' => $participationCount]) }}
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
@endsection


@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/enterprise-dashboard.css') }}">
@endpush
