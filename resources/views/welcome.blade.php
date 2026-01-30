@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h1 class="h4 mb-3">{{ __('app.welcome.title') }}</h1>
            <p class="text-muted mb-4">{{ __('app.welcome.subtitle') }}</p>
            <div class="d-flex justify-content-center gap-3">
                <a class="btn btn-primary" href="{{ route('login') }}">{{ __('app.welcome.login_cta') }}</a>
                <a class="btn btn-outline-secondary" href="{{ route('register') }}">{{ __('app.welcome.register_cta') }}</a>
            </div>
        </div>
    </div>
@endsection
