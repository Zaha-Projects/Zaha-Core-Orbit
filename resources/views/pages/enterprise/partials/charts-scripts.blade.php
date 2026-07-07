@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.min.js"></script>
<script type="application/json" id="enterprise-charts-data-json">@json([
    'monthlyTrend' => [
        'labels' => array_keys($analytics['monthlyTrend']->toArray()),
        'data' => array_values($analytics['monthlyTrend']->toArray()),
        'label' => __('app.enterprise.charts.events'),
    ],
    'approvalRatio' => [
        'labels' => [__('app.enterprise.charts.approved'), __('app.enterprise.charts.rejected')],
        'data' => [$analytics['approvalRatio']['approved'], $analytics['approvalRatio']['rejected']],
    ],
])</script>
<script src="{{ \App\Support\AssetVersion::url('assets/js/pages/pages-enterprise-charts.min.js') }}"></script>
@endpush
