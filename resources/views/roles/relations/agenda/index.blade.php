@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('role.relations.agenda.create') }}">إضافة فعالية</a>
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
                            <th>التاريخ</th>
                            <th>اسم الفعالية</th>
                            <th>القسم</th>
                            <th>الصنف</th>
                            <th>نوع/خطة</th>
                            <th>حالة الاعتماد</th>
                            <th>الفروع المشاركة</th>
                            <th class="text-end">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            @php
                                $participantCount = $event->participations->where('entity_type', 'branch')->where('participation_status', 'participant')->count();
                            @endphp
                            <tr>
                                <td>{{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ $event->department?->name ?? '-' }}</td>
                                <td>{{ $event->eventCategory?->name ?? $event->event_category ?? '-' }}</td>
                                <td>{{ $event->event_type === 'mandatory' ? 'إجباري' : 'اختياري' }} / {{ $event->plan_type === 'unified' ? 'موحد' : 'غير موحد' }}</td>
                                <td>
                                    <span class="badge text-bg-secondary">{{ $event->relations_approval_status }}</span>
                                    <span class="badge text-bg-info">{{ $event->executive_approval_status }}</span>
                                </td>
                                <td>{{ $participantCount }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">تعديل</a>
                                    <form class="d-inline" method="POST" action="{{ route('role.relations.agenda.submit', $event) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary" type="submit">إرسال للاعتماد</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted">لا توجد فعاليات حالياً.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
