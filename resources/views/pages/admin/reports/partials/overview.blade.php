<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card h-100 stretch stretch-full"><div class="card-body">
            <h2 class="h6">{{ __('app.reports.structure.title') }}</h2>
            <p class="text-muted small">{{ __('app.reports.structure.subtitle') }}</p>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.structure.branches') }}</span><strong>{{ $reportData['overview']['branches'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.structure.centers') }}</span><strong>{{ $reportData['overview']['centers'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.structure.users') }}</span><strong>{{ $reportData['overview']['users'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.structure.vehicles') }}</span><strong>{{ $reportData['overview']['vehicles'] }}</strong></li>
            </ul>
        </div></div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card h-100 stretch stretch-full"><div class="card-body">
            <h2 class="h6">{{ __('app.reports.financials.title') }}</h2>
            <p class="text-muted small">{{ __('app.reports.financials.subtitle') }}</p>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.financials.payments') }}</span><strong>{{ $reportData['financials']['payments'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.financials.payments_total') }}</span><strong>{{ number_format($reportData['financials']['payments_total'], 2) }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.financials.donations') }}</span><strong>{{ $reportData['financials']['donations'] }}</strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>{{ __('app.reports.financials.donations_total') }}</span><strong>{{ number_format($reportData['financials']['donations_total'], 2) }}</strong></li>
            </ul>
        </div></div>
    </div>
</div>
