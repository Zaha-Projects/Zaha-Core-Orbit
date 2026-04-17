@php
    $canAccessAgendaApprovals = auth()->user()
        && (
            auth()->user()->can('agenda.approve')
            || app(\App\Services\DynamicWorkflowService::class)->userMayParticipateInWorkflow('agenda', auth()->user())
        );
@endphp

@can('agenda.view')
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.agenda.*') ? 'active' : '' }}" href="{{ route('role.relations.agenda.index') }}">
        <span class="nxl-micon"><i class="feather-calendar"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.relations.agenda.title') }}</span>
    </a>
</li>
@endcan
@if($canAccessAgendaApprovals)
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.approvals.*') ? 'active' : '' }}" href="{{ route('role.relations.approvals.index') }}">
        <span class="nxl-micon"><i class="feather-check-square"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.relations.approvals.title') }}</span>
    </a>
</li>
@endif
