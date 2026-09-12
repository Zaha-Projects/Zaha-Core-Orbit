@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h1 class="h4 mb-1">{{ $guidance->title }}</h1>
            <div class="text-muted">{{ __('ramadan_iftars.hints.version', ['number' => $guidance->version_number]) }} · {{ optional($guidance->published_at)->format('Y-m-d') }}</div>
        </div>
        <div class="card-body">
            <div class="mb-4" style="white-space: pre-wrap">{{ $guidance->content }}</div>

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('events.ramadan.guidance.accept') }}">
                @csrf
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="accept_guidance" name="accept_guidance" required>
                    <label class="form-check-label" for="accept_guidance">{{ __('ramadan_iftars.hints.guidance_acceptance') }}</label>
                </div>
                <button class="btn btn-primary" type="submit">{{ __('ramadan_iftars.actions.accept_guidance') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
