@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/event-ui-shared.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/workflow-ui.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/agenda-approvals.css') }}">
@endpush

@section('content')
    @php
        $activeApprovalTab = in_array(request('tab'), ['delete', 'edit'], true) ? request('tab') : 'approval';
        $approvalStats = [
            [
                'label' => __('workflow_ui.approvals.filters.my_pending'),
                'value' => $events->filter(fn ($event) => (bool) data_get($event, 'workflow_summary.can_current_user_decide', $event->can_current_user_decide ?? false))->count(),
                'tone' => 'blue',
            ],
            [
                'label' => __('app.roles.relations.agenda.status_labels.published'),
                'value' => $events->filter(fn ($event) => in_array((string) data_get($event, 'workflow_summary.status_key'), ['published', 'approved', 'relations_approved'], true))->count(),
                'tone' => 'green',
            ],
            [
                'label' => __('app.roles.relations.approvals.filters.status'),
                'value' => $events->filter(fn ($event) => ! in_array((string) data_get($event, 'workflow_summary.status_key'), ['draft', 'published', 'approved', 'relations_approved'], true))->count(),
                'tone' => 'amber',
            ],
        ];
        $approvalTabs = [
            ['key' => 'approval', 'label' => 'طلبات الاعتماد'],
            ['key' => 'delete', 'label' => 'طلبات الحذف'],
            ['key' => 'edit', 'label' => 'طلبات التعديل'],
        ];
    @endphp

    <div class="workflow-ui agenda-approvals-page">
        @include('pages.agenda.approvals.partials.hero', ['approvalStats' => $approvalStats])

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @include('pages.agenda.approvals.partials.tabs', [
            'approvalTabs' => $approvalTabs,
            'activeApprovalTab' => $activeApprovalTab,
        ])

        @switch($activeApprovalTab)
            @case('delete')
                @include('pages.agenda.approvals.partials.delete-requests', ['deleteRequests' => $deleteRequests ?? null])
                @break

            @case('edit')
                @include('pages.agenda.approvals.partials.edit-requests', ['editRequests' => $editRequests ?? null])
                @break

            @default
                @include('pages.agenda.approvals.partials.approval-list')
        @endswitch
    </div>
@endsection
