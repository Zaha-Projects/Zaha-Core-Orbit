@php
    $user = auth()->user();
    $isCommunicationHeadOnly = $user?->hasRole('communication_head')
        && ! $user?->hasAnyRole(['super_admin', 'relations_manager', 'relations_officer', 'supervisor', 'branch_coordinator', 'executive_manager']);
    $canAccessMonthlyApprovals = $user
        && ! ($user->hasRole('programs_manager') && ! $user->hasRole('super_admin'))
        && (
            $user->can('monthly_activities.approve')
            || app(\App\Services\DynamicWorkflowService::class)->userMayParticipateInWorkflow('monthly_activities', $user)
        );
@endphp

@if(! $isCommunicationHeadOnly)
    @canany(['monthly_activities.view','monthly_plan.view'])
    <li class="nxl-item">
        <a class="nxl-link {{ request()->routeIs('role.relations.activities.*') && request('scope') !== 'all_branches' ? 'active' : '' }}" href="{{ route('role.relations.activities.index') }}">
            <span class="nxl-micon"><i class="feather-layers"></i></span>
            <span class="nxl-mtext">{{ __('app.roles.programs.monthly_activities.title') }}</span>
        </a>
    </li>
    @endcanany
    @if($user?->hasAnyRole(['relations_manager', 'relations_officer', 'super_admin']))
    <li class="nxl-item">
        <a class="nxl-link {{ request()->routeIs('role.relations.activities.returned_feedback') ? 'active' : '' }}" href="{{ route('role.relations.activities.returned_feedback') }}">
            <span class="nxl-micon"><i class="feather-corner-down-left"></i></span>
            <span class="nxl-mtext">طلبات راجعة للفرع</span>
        </a>
    </li>
    @endif
    @can('monthly_activities.view_other_branches')
    <li class="nxl-item">
        <a class="nxl-link {{ request()->routeIs('role.relations.activities.*') && request('scope') === 'all_branches' ? 'active' : '' }}" href="{{ route('role.relations.activities.index', ['scope' => 'all_branches']) }}">
            <span class="nxl-micon"><i class="feather-grid"></i></span>
            @php($otherBranchesLabel = __('app.acl.permissions.monthly_activities_view_other_branches'))
            <span class="nxl-mtext">{{ $otherBranchesLabel !== 'app.acl.permissions.monthly_activities_view_other_branches' ? $otherBranchesLabel : 'عرض الخطط الشهرية للفروع الأخرى' }}</span>
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
@endif

@if($user?->hasAnyRole(['communication_head', 'super_admin']))
<li class="nxl-item"><a class="nxl-link {{ request()->routeIs('role.programs.communications_requests.index') ? 'active' : '' }}" href="{{ route('role.programs.communications_requests.index') }}"><span class="nxl-micon"><i class="feather-camera"></i></span><span class="nxl-mtext">قرارات قسم الاتصال</span></a></li>
@endif
@if($user?->hasAnyRole(['communication_head', 'super_admin', 'executive_manager']) || $user?->can('departments.view'))
<li class="nxl-item"><a class="nxl-link {{ request()->routeIs('role.programs.communications_requests.board') ? 'active' : '' }}" href="{{ route('role.programs.communications_requests.board') }}"><span class="nxl-micon"><i class="feather-columns"></i></span><span class="nxl-mtext">متابعة الاتصال</span></a></li>
@endif
