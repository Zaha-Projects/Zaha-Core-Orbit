<div class="wf-card card mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">طلبات تعديل الأجندة السنوية</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>الأجندة</th>
                        <th>طالب التعديل</th>
                        <th>التغييرات</th>
                        <th>الحالة</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($editRequests ?? [] as $editRequest)
                        <tr>
                            <td>{{ $editRequest->agendaEvent?->event_name ?? '#' . $editRequest->entity_id }}</td>
                            <td>
                                {{ $editRequest->requester?->name ?? '-' }}
                                <br><small>{{ optional($editRequest->requested_at)->format('Y-m-d H:i') }}</small>
                            </td>
                            <td>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-xs mb-0">
                                        <thead><tr><th>الحقل</th><th>القيمة القديمة</th><th>القيمة الجديدة</th></tr></thead>
                                        <tbody>
                                            @forelse(($editRequest->changed_values ?? []) as $field => $change)
                                                <tr>
                                                    <td>{{ $field }}</td>
                                                    <td>{{ is_array($change['old'] ?? null) ? json_encode($change['old'], JSON_UNESCAPED_UNICODE) : ($change['old'] ?? '-') }}</td>
                                                    <td>{{ is_array($change['new'] ?? null) ? json_encode($change['new'], JSON_UNESCAPED_UNICODE) : ($change['new'] ?? '-') }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="3" class="text-center text-muted">لا توجد تغييرات معروضة.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $editRequest->status }}</span>
                                <br><small>{{ $editRequest->workflowInstance?->currentStep?->name_ar }}</small>
                            </td>
                            <td>
                                @if(in_array($editRequest->status, ['pending', 'in_progress', 'changes_requested'], true))
                                    <form method="POST" action="{{ route('role.relations.approvals.edit_requests.update', $editRequest) }}" class="d-flex gap-1 flex-wrap">
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
                        <tr><td colspan="5" class="text-center text-muted">لا توجد طلبات تعديل.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ ($editRequests ?? null)?->links() }}</div>
    </div>
</div>
