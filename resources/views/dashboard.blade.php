@extends('layouts.app')

@section('title', __('app.common.dashboard'))

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

    <section class="row g-3">
        <div class="col-12">
            <div class="card p-3" id="calendarSection">
                <h2 class="h4 mb-3">{{ __('app.roles.relations.agenda.calendar.calendar_view') }}</h2>
                <div id="calendar"></div>
                <div id="calendarFallback" class="alert alert-warning mt-3 d-none">
                    {{ app()->getLocale() === 'ar' ? 'تعذر تحميل التقويم.' : 'Calendar failed to load.' }}
                </div>
            </div>
        </div>
    </section>
@endsection
