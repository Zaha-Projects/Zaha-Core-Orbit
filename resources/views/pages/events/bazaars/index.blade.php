@extends('layouts.app')

@section('title', __('bazaars.title'))

@php
    $canCreate = auth()->user()?->can('bazaars.create') ?? false;
    $statusLabel = fn (string $status) => __('bazaars.statuses.'.$status);
    $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();
@endphp

@section('content')
<div
    class="event-module bazaar-browse-module"
    dir="rtl"
    data-calendar-endpoint="{{ route('events.bazaars.calendar') }}"
    data-create-url="{{ $canCreate ? route('events.bazaars.create') : '' }}"
    data-month="{{ $filters['month'] }}"
>
    <div class="card event-card mb-4">
        <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="bazaar-title-icon"><i class="fas fa-store"></i></span>
                    <h1 class="h4 mb-0">{{ __('bazaars.title') }}</h1>
                </div>
                <p class="text-muted mb-0">{{ __('bazaars.subtitle') }}</p>
            </div>
            @can('bazaars.create')
                <a class="btn btn-bazaar" href="{{ route('events.bazaars.create') }}">
                    <i class="fas fa-plus"></i> {{ __('bazaars.create') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="event-section-title"><i class="fas fa-filter"></i> {{ __('bazaars.filters') }}</h2>
            <form method="GET" action="{{ route('events.bazaars.index') }}" class="row event-form-grid g-3">
                <div class="col-12 col-lg-3">
                    <label class="form-label">{{ __('bazaars.search') }}</label>
                    <input class="form-control" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('bazaars.search_placeholder') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">{{ __('bazaars.branch') }}</label>
                    <select class="form-select" name="branch_id">
                        <option value="">{{ __('bazaars.all_branches') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (int) $filters['branch_id'] === $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">{{ __('bazaars.status') }}</label>
                    <select class="form-select" name="status">
                        <option value="">{{ __('bazaars.all_statuses') }}</option>
                        @foreach(\App\Modules\Events\Models\Bazaar::STATUSES as $status)
                            <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>{{ $statusLabel($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">{{ __('bazaars.month') }}</label>
                    <input class="form-control" type="month" name="month" value="{{ $filters['month'] }}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">{{ __('bazaars.location_type') }}</label>
                    <select class="form-select" name="location_type">
                        <option value="">{{ __('bazaars.all_locations') }}</option>
                        <option value="inside" {{ $filters['location_type'] === 'inside' ? 'selected' : '' }}>{{ __('bazaars.inside') }}</option>
                        <option value="outside" {{ $filters['location_type'] === 'outside' ? 'selected' : '' }}>{{ __('bazaars.outside') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label">{{ __('bazaars.rental_type') }}</label>
                    <select class="form-select" name="rental_type">
                        <option value="">{{ __('bazaars.all_renters') }}</option>
                        <option value="individual" {{ $filters['rental_type'] === 'individual' ? 'selected' : '' }}>{{ __('bazaars.individual') }}</option>
                        <option value="organization" {{ $filters['rental_type'] === 'organization' ? 'selected' : '' }}>{{ __('bazaars.organization') }}</option>
                    </select>
                </div>
                <div class="col-12 col-lg-auto event-actions align-self-end">
                    <button class="btn btn-bazaar">{{ __('bazaars.apply') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('events.bazaars.index') }}">{{ __('bazaars.reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    <div class="agenda-view-switch mb-3" role="tablist">
        <button type="button" class="btn btn-sm btn-bazaar active" data-view-toggle="table">{{ __('bazaars.cards') }}</button>
        <button type="button" class="btn btn-sm btn-outline-bazaar" data-view-toggle="calendar">{{ __('bazaars.calendar') }}</button>
    </div>

    <div class="agenda-view-pane" data-view-pane="table">
        <div class="card event-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                    <h2 class="event-section-title mb-0">{{ $monthDate->translatedFormat('F Y') }}</h2>
                    <span class="text-muted small">{{ __('bazaars.results', ['count' => $bazaars->total()]) }}</span>
                </div>
                <div class="bazaar-cards-grid">
                    @forelse($bazaars as $item)
                        <article class="bazaar-browse-card">
                            <div class="module-card-header">
                                <div class="d-flex justify-content-between gap-2">
                                    <h3 class="h6 mb-0"><i class="fas fa-store bazaar-accent"></i> {{ $item->name }}</h3>
                                    <span class="bazaar-status bazaar-status--{{ $item->status }}">{{ $statusLabel($item->status) }}</span>
                                </div>
                            </div>
                            <div class="module-card-body">
                                <div class="bazaar-meta">
                                    <span><i class="far fa-calendar"></i> {{ $item->bazaar_date?->translatedFormat('d F Y') }}</span>
                                    <span><i class="far fa-clock"></i> {{ substr($item->starts_at, 0, 5) }}–{{ substr($item->ends_at, 0, 5) }}</span>
                                    <span><i class="fas fa-location-dot"></i> {{ $item->location_name }}</span>
                                    <span><i class="fas fa-building"></i> {{ $item->branch?->name ?? '—' }}</span>
                                    <span><i class="fas fa-table-cells-large"></i> {{ __('bazaars.tables', ['count' => $item->planned_table_count]) }}</span>
                                </div>
                            </div>
                            <div class="module-card-footer event-actions">
                                <a class="btn btn-sm btn-outline-bazaar" href="{{ route('events.bazaars.show', $item) }}">
                                    <i class="fas fa-eye"></i> {{ __('bazaars.view') }}
                                </a>
                                @can('bazaars.edit')
                                    @if($item->isPlanningEditable())
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('events.bazaars.edit', $item) }}">{{ __('bazaars.edit') }}</a>
                                    @endif
                                @endcan
                            </div>
                        </article>
                    @empty
                        <div class="bazaar-empty">
                            <i class="fas fa-store-slash"></i>
                            <h3 class="h6">{{ __('bazaars.empty_title') }}</h3>
                            <p>{{ __('bazaars.empty_text') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $bazaars->links('pagination::bootstrap-5') }}</div>
    </div>

    <div class="agenda-view-pane d-none" data-view-pane="calendar">
        <div class="card event-card">
            <div class="card-body">
                <div class="agenda-calendar-toolbar mb-3 d-flex justify-content-between align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" data-calendar-nav="prev">{{ __('bazaars.previous') }}</button>
                    <h2 class="h6 mb-0" data-calendar-title></h2>
                    <button class="btn btn-sm btn-outline-secondary" data-calendar-nav="next">{{ __('bazaars.next') }}</button>
                </div>
                <div class="agenda-calendar-weekdays" data-calendar-weekdays></div>
                <div class="agenda-calendar-grid" data-calendar-grid>
                    <div class="bazaar-calendar-state">{{ __('bazaars.calendar_loading') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/event-ui-shared.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/monthly-activities-index.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/bazaar-index.css') }}">
@endpush

@push('scripts')
    <script type="application/json" id="bazaar-calendar-labels">{!! json_encode([
        'weekdays' => __('app.roles.relations.agenda.calendar.weekdays'),
        'statuses' => __('bazaars.statuses'),
        'loading' => __('bazaars.calendar_loading'),
        'error' => __('bazaars.calendar_error'),
        'create' => __('bazaars.create_on', ['date' => '__DATE__']),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script src="{{ \App\Support\AssetVersion::url('assets/js/ui-shared.js') }}"></script>
    <script src="{{ \App\Support\AssetVersion::url('assets/js/bazaar-index.js') }}"></script>
@endpush
