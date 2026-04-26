@extends('layouts.auth-cover')

@section('content')
    <div class="row justify-content-center align-items-center min-vh-100 py-3">
        <div class="col-12 col-lg-10 col-xl-8">
            <div class="card auth-flow-card border-0 shadow-lg">
                <div class="row g-0">
                    <div class="col-lg-6 order-2 order-lg-1">
                        <div class="auth-flow-form p-4 p-md-5">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ asset('assets/images/zaha-core-orbit-logo.svg') }}" alt="Zaha - Core Orbit" class="auth-flow-logo-main">
                                    <img src="{{ asset('assets/theme/logos/logo2.svg') }}" alt="Zaha Original Logo" class="auth-flow-logo-original">
                                </div>
                            </div>

                            <h1 class="h3 mb-2">{{ __('app.auth.login_title') }}</h1>
                            <p class="text-muted mb-4">أدخل بياناتك للوصول إلى منصة Zaha - Core Orbit.</p>

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
                                <div class="mb-3">
                                    <label class="form-label" for="email">{{ __('app.auth.email') }}</label>
                                    <div class="input-group">
                                        <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="name@zaha-core-orbit.com">
                                        <span class="input-group-text"><i class="feather-mail"></i></span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="password">{{ __('app.auth.password') }}</label>
                                    <div class="input-group">
                                        <input class="form-control" id="password" type="password" name="password" required placeholder="••••••••">
                                        <span class="input-group-text"><i class="feather-lock"></i></span>
                                    </div>
                                </div>
                                <div class="mb-4 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input class="form-check-input" id="remember" type="checkbox" name="remember">
                                        <label class="form-check-label" for="remember">{{ __('app.auth.remember') }}</label>
                                    </div>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="link-primary fw-semibold">نسيت كلمة المرور؟</a>
                                    @endif
                                </div>
                                <button class="btn btn-info w-100 btn-lg text-white" type="submit">{{ __('app.auth.submit_login') }}</button>
                            </form>

                            <div class="text-center mt-4">
                                <a href="{{ url('/') }}" class="text-decoration-none fw-semibold">العودة إلى الرئيسية</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 order-1 order-lg-2 d-none d-lg-flex auth-flow-cover">
                        <div>
                            <h2 class="h2 mb-3">Zaha - Core Orbit</h2>
                            <p class="mb-0">واجهة دخول احترافية متوافقة مع الهوية البصرية لمنصة زها.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
