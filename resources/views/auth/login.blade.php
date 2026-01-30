@extends('layouts.guest')

@section('content')
    <h1 class="h4 mb-4 text-center">{{ __('app.auth.login_title') }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">{{ __('app.auth.email') }}</label>
            <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">{{ __('app.auth.password') }}</label>
            <input class="form-control" id="password" type="password" name="password" required>
        </div>
        <div class="mb-3 form-check">
            <input class="form-check-input" id="remember" type="checkbox" name="remember">
            <label class="form-check-label" for="remember">{{ __('app.auth.remember') }}</label>
        </div>
        <button class="btn btn-primary w-100" type="submit">{{ __('app.auth.submit_login') }}</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('register') }}">{{ __('app.auth.new_account') }}</a>
    </div>
@endsection
