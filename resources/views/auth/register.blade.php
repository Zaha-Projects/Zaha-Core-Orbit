@extends('layouts.guest')

@section('content')
    <h1 class="h4 mb-4 text-center">إنشاء حساب</h1>

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
            <label class="form-label" for="name">الاسم الكامل</label>
            <input class="form-control" id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">كلمة المرور</label>
            <input class="form-control" id="password" type="password" name="password" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">تأكيد كلمة المرور</label>
            <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">تسجيل</button>
    </form>

    <div class="text-center mt-3">
        <a href="{{ route('login') }}">لديك حساب؟ سجل الدخول</a>
    </div>
@endsection
