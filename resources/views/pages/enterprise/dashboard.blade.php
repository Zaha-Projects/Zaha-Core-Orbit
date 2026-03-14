@extends('layouts.app')

@section('page_title', __('app.enterprise.analytics_title'))
@section('page_breadcrumb', __('app.enterprise.analytics_title'))

@section('content')
    <div class="enterprise-dashboard">
        <form class="card card-body mb-3" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label" for="enterprise-year">{{ __('app.enterprise.year') }}</label>
                    <select class="form-select" id="enterprise-year" name="year">
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected(($filters['year'] ?? now()->year) == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label" for="enterprise-month">{{ __('app.enterprise.filters.month') }}</label>
                    <input
                        class="form-control"
                        id="enterprise-month"
                        type="number"
                        min="1"
                        max="12"
                        name="month"
                        value="{{ $filters['month'] ?? '' }}"
                    >
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label" for="enterprise-status">{{ __('app.enterprise.filters.status') }}</label>
                    <input class="form-control" id="enterprise-status" name="status" value="{{ $filters['status'] ?? '' }}">
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="enterprise-branch">{{ __('app.enterprise.filters.branch') }}</label>
                    <select class="form-select" id="enterprise-branch" name="branch_id">
                        <option value="">{{ __('app.enterprise.filters.all') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-3">
                    <button class="btn btn-primary w-100" type="submit">{{ __('app.enterprise.apply') }}</button>
                </div>
            </div>
        </form>

        @include('pages.enterprise.partials.kpis')
        @include('pages.enterprise.partials.charts')
        @include('pages.enterprise.partials.branch-performance')
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/enterprise-dashboard.css') }}">
@endpush

@include('pages.enterprise.partials.charts-scripts')
