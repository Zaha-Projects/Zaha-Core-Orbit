@section('page_title', $title)
@section('page_breadcrumb', $title)

<div class="event-module">
    <div class="event-card card stretch-full">
        <div class="card-body p-4">
            <div class="event-header">
                <div>
                    <h1 class="h4 mb-2">{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                </div>
            </div>

            <div class="event-kpi-grid mb-4">
                <div class="event-kpi-card">
                    <div class="text-muted small">{{ __('app.common.open_section') }}</div>
                    <div class="event-kpi-value">{{ collect($actions)->whereNotNull('link')->count() }}</div>
                </div>
                <div class="event-kpi-card">
                    <div class="text-muted small">{{ __('app.common.total') }}</div>
                    <div class="event-kpi-value">{{ count($actions) }}</div>
                </div>
            </div>

            <div class="row g-3">
                @foreach ($actions as $action)
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="event-card p-3 h-100 d-flex flex-column">
                            <h2 class="h6 mb-2">{{ $action['title'] }}</h2>
                            <p class="text-muted mb-0 flex-grow-1">{{ $action['description'] }}</p>
                            @if (!empty($action['link']))
                                <a class="btn btn-primary mt-3" href="{{ $action['link'] }}">
                                    {{ __('app.common.open_section') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
