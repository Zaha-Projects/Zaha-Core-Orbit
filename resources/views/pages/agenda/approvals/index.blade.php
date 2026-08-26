@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/event-ui-shared.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/workflow-ui.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/pages/relations-agenda-approvals.min.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/agenda-event-show.min.css') }}">
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

    <div class="workflow-ui agenda-approvals-page relations-agenda-approvals-page">
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

    <div class="modal fade" id="agendaDetailsModal" tabindex="-1" aria-labelledby="agendaDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content agenda-details-modal__content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="agendaDetailsModalLabel">تفاصيل الأجندة</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="agenda-details-modal__body" data-agenda-details-body>
                        <div class="agenda-details-modal__loading">جاري تحميل تفاصيل الأجندة...</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-primary" data-agenda-details-open href="#" target="_blank" rel="noopener">فتح في صفحة مستقلة</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/relations-agenda-approvals.min.js') }}"></script>
@endpush
