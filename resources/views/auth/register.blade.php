@extends('layouts.guest')

@section('content')
    <h1 class="h4 mb-4 text-center">{{ __('app.auth.register_title') }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('/register') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="name">{{ __('app.auth.full_name') }}</label>
            <input class="form-control" id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">{{ __('app.auth.email') }}</label>
            <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">{{ __('app.auth.password') }}</label>
            <input class="form-control" id="password" type="password" name="password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">{{ __('app.auth.confirm_password') }}</label>
            <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">{{ __('app.auth.submit_register') }}</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}">{{ __('app.auth.have_account') }}</a>
    </div>
@endsection
