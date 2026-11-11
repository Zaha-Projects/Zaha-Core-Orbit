@extends('layouts.app')

@section('title', __('movement.requests.review_title'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-clipboard-check', 'title' => __('movement.requests.review_title'), 'subtitle' => __('movement.requests.review_subtitle')])
    @include('pages.movement.partials.alerts')

    <div class="card event-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-8 col-md-4">
                    <label class="form-label" for="review-status">{{ __('movement.requests.status') }}</label>
                    <select class="form-select" id="review-status" name="status">
                        <option value="">{{ __('movement.request_statuses.submitted') }} / {{ __('movement.request_statuses.under_review') }}</option>
                        @foreach(array_diff(\App\Modules\Movement\Models\MovementRequest::STATUSES, ['draft']) as $option)
                            <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ __('movement.request_statuses.'.$option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-2"><button class="btn btn-outline-primary w-100">{{ __('movement.requests.filter') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card event-card"><div class="card-body">
        @include('pages.movement.requests._list', ['requests' => $requests, 'showRequester' => true, 'emptyText' => __('movement.requests.review_empty')])
    </div></div>
    <div class="mt-3">{{ $requests->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

@include('pages.movement.partials.assets')
