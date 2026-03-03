<div class="enterprise-kpi-grid mb-3">
    <div class="kpi">{{ __('app.enterprise.kpis.total_events') }} <strong>{{ $analytics['kpis']['totalEvents'] }}</strong></div>
    <div class="kpi">{{ __('app.enterprise.kpis.approved') }} <strong>{{ $analytics['kpis']['approvedEvents'] }}</strong></div>
    <div class="kpi">{{ __('app.enterprise.kpis.rejected') }} <strong>{{ $analytics['kpis']['rejectedEvents'] }}</strong></div>
    <div class="kpi">{{ __('app.enterprise.kpis.pending') }} <strong>{{ $analytics['kpis']['pendingApprovals'] }}</strong></div>
    <div class="kpi">{{ __('app.enterprise.kpis.executed') }} <strong>{{ $analytics['kpis']['executedActivities'] }}</strong></div>
    <div class="kpi">{{ __('app.enterprise.kpis.branch_participation') }} <strong>{{ $analytics['kpis']['branchParticipationRate'] }}%</strong></div>
    <div class="kpi">{{ __('app.enterprise.kpis.plan_adherence') }} <strong>{{ $analytics['kpis']['planAdherence'] }}%</strong></div>
</div>
