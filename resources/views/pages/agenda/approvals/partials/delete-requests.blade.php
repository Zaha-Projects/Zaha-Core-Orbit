<div class="wf-card card mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">طلبات حذف الأجندة السنوية</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>الأجندة</th>
                        <th>الفرع</th>
                        <th>طالب الحذف</th>
                        <th>سبب الحذف</th>
                        <th>الحالة</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deleteRequests ?? [] as $deleteRequest)
                        <tr>
                            <td>{{ $deleteRequest->agendaEvent?->event_name ?? '#' . $deleteRequest->entity_id }}</td>
                            <td>{{ $deleteRequest->agendaEvent?->department?->name ?? '-' }}</td>
                            <td>
                                {{ $deleteRequest->requester?->name ?? '-' }}
                                <br><small>{{ optional($deleteRequest->requested_at)->format('Y-m-d H:i') }}</small>
                            </td>
                            <td>{{ $deleteRequest->reason ?: '-' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $deleteRequest->status }}</span>
                                <br><small>{{ $deleteRequest->workflowInstance?->currentStep?->name_ar }}</small>
                            </td>
                            <td>
                                @if(in_array($deleteRequest->status, ['pending', 'in_progress', 'changes_requested'], true))
                                    <form method="POST" action="{{ route('role.relations.approvals.delete_requests.update', $deleteRequest) }}" class="d-flex gap-1 flex-wrap">
                                        @csrf
                                        @method('PUT')
                                        <input name="comment" class="form-control form-control-sm" placeholder="ملاحظة اختيارية">
                                        <button name="decision" value="approved" class="btn btn-sm btn-success">اعتماد</button>
                                        <button name="decision" value="rejected" class="btn btn-sm btn-danger">رفض</button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">لا توجد طلبات حذف.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ ($deleteRequests ?? null)?->links() }}</div>
    </div>
</div>
