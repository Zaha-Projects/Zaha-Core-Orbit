@extends('layouts.app')

@section('content')
    <div class="card stretch stretch-full">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ __('app.dashboard.no_role_title') }}</h1>
            <p class="mb-0 text-muted">{{ __('app.dashboard.no_role_message') }}</p>
        </div>
    </div>
@endsection
