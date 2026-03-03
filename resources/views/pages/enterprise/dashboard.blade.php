@extends('layouts.app')

@section('content')
<div class="enterprise-dashboard">
    <form class="card card-body mb-3" method="GET">
        <div class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">Year</label><select class="form-select" name="year">@foreach($years as $year)<option value="{{ $year }}" @selected(($filters['year'] ?? now()->year)==$year)>{{ $year }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Month</label><input class="form-control" type="number" min="1" max="12" name="month" value="{{ $filters['month'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label">Status</label><input class="form-control" name="status" value="{{ $filters['status'] ?? '' }}"></div>
            <div class="col-md-2"><label class="form-label">Branch</label><select class="form-select" name="branch_id"><option value="">All</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(($filters['branch_id'] ?? '')==$b->id)>{{ $b->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Apply</button></div>
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
