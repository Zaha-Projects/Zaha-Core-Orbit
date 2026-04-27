@extends('layouts.app')

@section('title', __('app.common.dashboard'))

@php
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path) . '?v=' . $version;
    };
@endphp

@section('content')
    <section class="mb-4">
        <div class="card p-4">
            <h1 class="h4 mb-2">{{ __('app.common.dashboard') }}</h1>
            <p class="text-muted mb-0">{{ __('app.dashboard.no_role_message') }}</p>
        </div>
    </section>

    <section id="cardsSection" class="row g-3 mb-4">
        @forelse($cards ?? [] as $card)
            <div class="col-md-6 col-xl-3">
                <a href="{{ $card['url'] }}" class="text-decoration-none text-reset">
                    <div class="kpi-card stat-card-blue h-100">
                        <div class="kpi-head">
                            <p class="kpi-label mb-0">{{ $card['title'] }}</p>
                            <span class="kpi-icon"><i class="{{ $card['icon'] }}"></i></span>
                        </div>
                        <h3 class="kpi-value">{{ $loop->iteration }}</h3>
                        <p class="kpi-delta">{{ $card['description'] }}</p>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">{{ __('app.dashboard.no_role_message') }}</div>
            </div>
        @endforelse
    </section>

    @if(($calendarEvents ?? collect())->isNotEmpty())
        <section class="row g-3">
            <div class="col-12 col-xxl-8">
                <div class="card dashboard-calendar-card p-3 p-lg-4" id="calendarSection">
                    <div class="dashboard-calendar-header mb-3 mb-lg-4">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <h2 class="h5 mb-0">{{ __('app.roles.relations.agenda.calendar.calendar_view') }}</h2>
                            <span class="dashboard-calendar-chip">{{ ($calendarEvents ?? collect())->count() }} فعالية سنوية</span>
                        </div>
                        <p class="dashboard-calendar-intro mb-0">تقويم عام يلخّص أهم الفعاليات القادمة خلال العام.</p>
                    </div>
                    <div id="calendar"></div>
                    <div id="calendarFallback" class="alert alert-warning mt-3 d-none">
                        {{ app()->getLocale() === 'ar' ? 'تعذر تحميل التقويم.' : 'Calendar failed to load.' }}
                    </div>
                </div>
            </div>
        </section>
        <script type="application/json" id="dashboard-calendar-events-json">@json($calendarEvents)</script>
    @endif
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/dashboard-calendar.css') }}">
@endpush
