@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('monthlyTrendChart'), {type:'line',data:{labels:@json(array_keys($analytics['monthlyTrend']->toArray())),datasets:[{label:@json(__('app.enterprise.charts.events')),data:@json(array_values($analytics['monthlyTrend']->toArray())),borderColor:'#5B5BD6'}]}});
new Chart(document.getElementById('approvalRatioChart'), {type:'doughnut',data:{labels:[@json(__('app.enterprise.charts.approved')),@json(__('app.enterprise.charts.rejected'))],datasets:[{data:[{{ $analytics['approvalRatio']['approved'] }},{{ $analytics['approvalRatio']['rejected'] }}],backgroundColor:['#30c48d','#f96868']}]}});
</script>
@endpush
