@extends('layouts.app')

@section('page_title', __('app.dashboard.no_role_title'))
@section('page_breadcrumb', __('app.dashboard.no_role_title'))
//@section('breadcrumb_current', __('app.dashboard.no_role_title'))

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ __('app.common.dashboard') }}</h1>
            <p class="text-muted mb-0">{{ __('app.dashboard.no_role_message') }}</p>
        </div>
    </div>

    <div class="row g-3">
        @forelse($cards ?? [] as $card)
            <div class="col-12 col-md-6 col-xl-4">
                <a href="{{ $card['url'] }}" class="card shadow-sm h-100 text-decoration-none text-reset">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="{{ $card['icon'] }}"></i>
                            <h2 class="h6 mb-0">{{ $card['title'] }}</h2>
                        </div>
                        <p class="text-muted mb-0 small">{{ $card['description'] }}</p>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">{{ __('app.dashboard.no_role_message') }}</div>
            </div>
        @endforelse
    </div>
@endsection
