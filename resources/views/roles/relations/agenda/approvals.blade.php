@extends('layouts.app')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">اعتمادات أجندة زها</h1>
            <p class="text-muted mb-0">اعتماد العلاقات ثم اعتماد المدير التنفيذي.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>الفعالية</th>
                            <th>التاريخ</th>
                            <th>الحالة</th>
                            <th>اعتماد العلاقات</th>
                            <th>اعتماد التنفيذي</th>
                            <th class="text-end">إجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            @php $latestApproval = $event->approvals->last(); @endphp
                            <tr>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                <td>{{ $event->status }}</td>
                                <td>{{ $event->relations_approval_status }}</td>
                                <td>{{ $event->executive_approval_status }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#approval-{{ $event->id }}">
                                        مراجعة
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="approval-{{ $event->id }}">
                                <td colspan="6">
                                    <form method="POST" action="{{ route('role.relations.approvals.update', $event) }}" class="row g-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-12 col-md-4">
                                            <label class="form-label">القرار</label>
                                            <select class="form-select" name="decision" required>
                                                <option value="approved">موافقة</option>
                                                <option value="changes_requested">تعديل مطلوب</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-8">
                                            <label class="form-label">ملاحظة</label>
                                            <input class="form-control" name="comment" value="{{ $latestApproval?->comment }}">
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-outline-primary btn-sm" type="submit">حفظ القرار</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted">لا توجد فعاليات للمراجعة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
