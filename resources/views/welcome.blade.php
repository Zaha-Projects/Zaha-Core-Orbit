@extends('layouts.auth')

@section('content')
    <section class="welcome-landing text-center">
        <h2 class="welcome-landing__title">{{ __('app.welcome.title') }}</h2>
        <p class="welcome-landing__subtitle">{{ __('app.welcome.subtitle') }}</p>
        <a class="btn btn-primary px-5" href="{{ route('login') }}">{{ __('app.welcome.login_cta') }}</a>
    </section>
@endsection
