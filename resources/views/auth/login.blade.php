@extends('layouts.auth-cover')

@section('content')
    <h2 class="fs-18 fw-bold mb-2">{{ __('app.auth.login_title') }}</h2>
    <p class="text-muted fs-12 mb-4">تسجيل دخول رسمي للوصول إلى بيئة زها الإدارية وإدارة الأعمال اليومية.</p>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('/login') }}" class="w-100 auth-login-form">
        @csrf
        <div class="mb-4">
            <label class="form-label" for="email">{{ __('app.auth.email') }}</label>
            <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">{{ __('app.auth.password') }}</label>
            <input class="form-control" id="password" type="password" name="password" required>
        </div>
        <div class="mb-4 form-check">
            <input class="form-check-input" id="remember" type="checkbox" name="remember">
            <label class="form-check-label" for="remember">{{ __('app.auth.remember') }}</label>
        </div>
        <button class="btn btn-lg btn-primary w-100" type="submit">{{ __('app.auth.submit_login') }}</button>
    </form>

@endsection
