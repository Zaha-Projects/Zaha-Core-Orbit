<div class="card stretch stretch-full mt-4">
    <div class="card-body enterprise-dashboard">
        <h2 class="h5 mb-3">{{ __('app.enterprise.analytics_title') }}</h2>
        <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('role.super_admin.reports') }}">
            <input type="hidden" name="tab" value="enterprise">
            <div class="col-md-3">
                <label class="form-label">{{ __('app.enterprise.year') }}</label>
                <select class="form-select" name="year">
                    @foreach ($years as $option)
                        <option value="{{ $option }}" @selected(($enterpriseFilters['year'] ?? now()->year) == $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">{{ __('app.enterprise.apply') }}</button></div>
        </form>
        @include('pages.enterprise.partials.kpis')
        @include('pages.enterprise.partials.charts')
        @include('pages.enterprise.partials.branch-performance')
    </div>
</div>
