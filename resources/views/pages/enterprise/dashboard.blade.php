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

    <div class="enterprise-kpi-grid mb-3">
        <div class="kpi">Total events <strong>{{ $analytics['kpis']['totalEvents'] }}</strong></div>
        <div class="kpi">Approved <strong>{{ $analytics['kpis']['approvedEvents'] }}</strong></div>
        <div class="kpi">Rejected <strong>{{ $analytics['kpis']['rejectedEvents'] }}</strong></div>
        <div class="kpi">Pending <strong>{{ $analytics['kpis']['pendingApprovals'] }}</strong></div>
        <div class="kpi">Executed <strong>{{ $analytics['kpis']['executedActivities'] }}</strong></div>
        <div class="kpi">Branch participation <strong>{{ $analytics['kpis']['branchParticipationRate'] }}%</strong></div>
        <div class="kpi">Plan adherence <strong>{{ $analytics['kpis']['planAdherence'] }}%</strong></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8"><div class="card"><div class="card-body"><h5>Monthly events trend</h5><canvas id="monthlyTrendChart"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card"><div class="card-body"><h5>Approval ratio</h5><canvas id="approvalRatioChart"></canvas></div></div></div>
    </div>

    <div class="card mt-3"><div class="card-body"><h5>Branch performance metrics</h5><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Branch</th><th>Total</th><th>Participation %</th><th>Approval %</th><th>Completion %</th></tr></thead><tbody>@foreach($branchMetrics as $row)<tr><td>{{ $row['branch'] }}</td><td>{{ $row['total_events_participated'] }}</td><td>{{ $row['participation_rate'] }}</td><td>{{ $row['approval_success_rate'] }}</td><td>{{ $row['activity_completion_rate'] }}</td></tr>@endforeach</tbody></table></div></div></div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/enterprise-dashboard.css') }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('monthlyTrendChart'), {type:'line',data:{labels:@json(array_keys($analytics['monthlyTrend']->toArray())),datasets:[{label:'Events',data:@json(array_values($analytics['monthlyTrend']->toArray())),borderColor:'#5B5BD6'}]}});
new Chart(document.getElementById('approvalRatioChart'), {type:'doughnut',data:{labels:['Approved','Rejected'],datasets:[{data:[{{ $analytics['approvalRatio']['approved'] }},{{ $analytics['approvalRatio']['rejected'] }}],backgroundColor:['#30c48d','#f96868']}]}});
</script>
@endpush
