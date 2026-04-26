@php
    $canAccessMonthlyApprovals = auth()->user()
        && (
            auth()->user()->can('monthly_activities.approve')
            || app(\App\Services\DynamicWorkflowService::class)->userMayParticipateInWorkflow('monthly_activities', auth()->user())
        );
@endphp

@canany(['monthly_activities.view','monthly_plan.view'])
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.activities.*') && request('scope') !== 'all_branches' ? 'active' : '' }}" href="{{ route('role.relations.activities.index') }}">
        <span class="nxl-micon"><i class="feather-layers"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.programs.monthly_activities.title') }}</span>
    </a>
</li>
@endcanany
@can('monthly_activities.view_other_branches')
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.activities.*') && request('scope') === 'all_branches' ? 'active' : '' }}" href="{{ route('role.relations.activities.index', ['scope' => 'all_branches']) }}">
        <span class="nxl-micon"><i class="feather-grid"></i></span>
        <span class="nxl-mtext">{{ __('app.acl.permissions.monthly_activities_view_other_branches') }}</span>
    </a>
</li>
@endcan
@if($canAccessMonthlyApprovals)
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.programs.approvals.*') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index') }}">
        <span class="nxl-micon"><i class="feather-check-square"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.programs.monthly_activities.approvals.title') }}</span>
    </a>
</li>
@endif
