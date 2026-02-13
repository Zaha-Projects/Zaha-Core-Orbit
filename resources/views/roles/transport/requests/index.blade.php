@extends('layouts.app')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">طلبات الحركة (Form 3)</h1>
            <p class="text-muted mb-0">تقديم طلبات الحركة ومتابعة قبول/رفض/إغلاق الطلب من مأمور الحركة، ثم تقييم الخدمة بعد الإغلاق.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">إرسال طلب حركة جديد</h2>
            <form method="POST" action="{{ route('role.transport.requests.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label">تاريخ الطلب</label>
                    <input class="form-control" type="date" name="request_date" value="{{ old('request_date') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">اليوم</label>
                    <input class="form-control" name="day_name" value="{{ old('day_name') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">الوجهة</label>
                    <input class="form-control" name="destination" value="{{ old('destination') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">الفريق المرافق</label>
                    <input class="form-control" name="accompanying_team" value="{{ old('accompanying_team') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">وقت الانطلاق</label>
                    <input class="form-control" type="time" name="departure_time" value="{{ old('departure_time') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">وقت العودة</label>
                    <input class="form-control" type="time" name="return_time" value="{{ old('return_time') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">ملاحظات عامة</label>
                    <textarea class="form-control" rows="2" name="general_notes">{{ old('general_notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">قائمة الطلبات</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>مقدم الطلب</th>
                            <th>الفرع</th>
                            <th>التاريخ</th>
                            <th>الوجهة</th>
                            <th>الحالة</th>
                            <th>السائق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $transportRequest)
                            @php($trip = $transportRequest->trips->first())
                            <tr>
                                <td>{{ $transportRequest->id }}</td>
                                <td>{{ $transportRequest->requester?->name ?? '-' }}</td>
                                <td>{{ $transportRequest->branch?->name ?? '-' }}</td>
                                <td>{{ optional($transportRequest->request_date)->format('Y-m-d') }}</td>
                                <td>{{ $trip?->destination ?? '-' }}</td>
                                <td>{{ $transportRequest->status }}</td>
                                <td>{{ $transportRequest->driver?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td colspan="7">
                                    <div class="row g-3">
                                        @if ($isTransportOfficer)
                                            <div class="col-12 col-lg-8">
                                                <form method="POST" action="{{ route('role.transport.requests.process', $transportRequest) }}" class="row g-2 border rounded p-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">الحالة</label>
                                                        <select class="form-select" name="status" required>
                                                            @foreach (['in_review' => 'قيد المراجعة', 'approved' => 'مقبول', 'rejected' => 'مرفوض', 'closed' => 'مغلق'] as $statusValue => $statusLabel)
                                                                <option value="{{ $statusValue }}" @selected($transportRequest->status === $statusValue)>{{ $statusLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">تعيين سائق</label>
                                                        <select class="form-select" name="driver_id">
                                                            <option value="">بدون</option>
                                                            @foreach ($drivers as $driver)
                                                                <option value="{{ $driver->id }}" @selected((int) $transportRequest->driver_id === (int) $driver->id)>{{ $driver->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">الوجهة</label>
                                                        <input class="form-control" name="destination" value="{{ $trip?->destination }}">
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">الفريق المرافق</label>
                                                        <input class="form-control" name="accompanying_team" value="{{ $trip?->accompanying_team }}">
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">الانطلاق</label>
                                                        <input class="form-control" type="time" name="departure_time" value="{{ $trip?->departure_time }}">
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">العودة</label>
                                                        <input class="form-control" type="time" name="return_time" value="{{ $trip?->return_time }}">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">ملاحظات مأمور الحركة</label>
                                                        <textarea class="form-control" rows="2" name="movement_officer_notes">{{ $transportRequest->movement_officer_notes }}</textarea>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">تعليق الإجراء</label>
                                                        <textarea class="form-control" rows="2" name="comment"></textarea>
                                                    </div>
                                                    <div class="col-12 d-flex justify-content-end">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">حفظ قرار مأمور الحركة</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif

                                        @if (auth()->id() === $transportRequest->requester_id && $transportRequest->status === 'closed')
                                            <div class="col-12 col-lg-4">
                                                <form method="POST" action="{{ route('role.transport.requests.feedback', $transportRequest) }}" class="border rounded p-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <h3 class="h6">تقييم مقدم الطلب</h3>
                                                    <div class="mb-2">
                                                        <label class="form-label">الالتزام بالوقت (1-5)</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="punctuality_score" value="{{ $transportRequest->feedback?->punctuality_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">النظافة (1-5)</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="cleanliness_score" value="{{ $transportRequest->feedback?->cleanliness_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">سلوك السائق (1-5)</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="driver_behavior_score" value="{{ $transportRequest->feedback?->driver_behavior_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">التقييم العام (1-5)</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="overall_score" value="{{ $transportRequest->feedback?->overall_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">ملاحظات</label>
                                                        <textarea class="form-control" rows="2" name="comment">{{ $transportRequest->feedback?->comment }}</textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-success btn-sm">حفظ التقييم</button>
                                                </form>
                                            </div>
                                        @endif

                                        <div class="col-12">
                                            <div class="small text-muted">
                                                سجل الإجراءات:
                                                @forelse ($transportRequest->actions as $action)
                                                    <span class="badge bg-light text-dark border me-1 mb-1">
                                                        {{ $action->action_type }} بواسطة {{ $action->actor?->name ?? 'مستخدم' }}
                                                    </span>
                                                @empty
                                                    لا يوجد.
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">لا توجد طلبات حركة حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
