@extends('layouts.app')

@section('title', $activity->title)

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/followup-plan-details.min.css') }}">
@endpush

@php
    $payload = (array) ($activity->post_execution_payload ?? []);
    $teams = collect($payload['teams'] ?? []);
    $ceremonyItems = collect($payload['ceremony_items'] ?? []);
    $actualAttendance = $activity->actual_attendance ?? data_get($payload, 'actual_attendance');
@endphp

@section('content')
<div class="container-fluid py-4 followup-plan-page">
    <header class="followup-plan-hero mb-4">
        <div>
            <span class="followup-plan-eyebrow"><i class="fas fa-clipboard-check"></i> مراجعة الخطة الشهرية</span>
            <h1>{{ $activity->title }}</h1>
            <p><i class="fas fa-building"></i> {{ $activity->branch?->name ?: '—' }}</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="followup-plan-status">{{ $activity->status }}</span>
            <a class="btn btn-light" href="{{ url()->previous() }}"><i class="fas fa-arrow-right"></i> {{ __('app.common.back') }}</a>
        </div>
    </header>

    <section class="followup-attendance-grid mb-4">
        <article><span>عدد الحضور المتوقع</span><strong>{{ $activity->expected_attendance_range_label ?: '—' }}</strong></article>
        <article class="is-primary"><span>عدد الحضور الفعلي</span><strong>{{ $actualAttendance ?? '—' }}</strong></article>
        <article><span>عدد الفرق المنفذة</span><strong>{{ $teams->count() }}</strong></article>
        <article><span>تاريخ التنفيذ الفعلي</span><strong>{{ optional($activity->actual_date)->format('Y-m-d') ?: data_get($payload, 'actual_date', '—') }}</strong></article>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="card followup-plan-card mb-4">
                <div class="card-header"><i class="fas fa-circle-info"></i> معلومات النشاط</div>
                <div class="card-body followup-info-grid">
                    <div><span>التاريخ المقترح</span><strong>{{ optional($activity->proposed_date)->translatedFormat('d M Y') ?: '—' }}</strong></div>
                    <div><span>نوع النشاط</span><strong>{{ $activity->eventType?->name ?: '—' }}</strong></div>
                    <div><span>الوقت</span><strong>{{ optional($activity->time_from)->format('H:i') ?: '—' }}</strong></div>
                    <div><span>الموقع</span><strong>{{ $activity->location_details ?: $activity->internal_location ?: $activity->outside_place_name ?: '—' }}</strong></div>
                    <div><span>الفئة المستهدفة</span><strong>{{ $activity->targetGroup?->name ?? $activity->target_group ?? '—' }}</strong></div>
                    <div><span>العلاقة / الجهة</span><strong>{{ $activity->responsible_party ?: $activity->agendaEvent?->department?->name ?: '—' }}</strong></div>
                    <div class="is-wide"><span>الوصف</span><strong>{{ $activity->description ?: $activity->short_description ?: '—' }}</strong></div>
                </div>
            </section>

            <section class="card followup-plan-card mb-4">
                <div class="card-header"><i class="fas fa-users-gear"></i> بيانات الفرق بعد التنفيذ</div>
                <div class="card-body">
                    <div class="followup-team-list">
                        @forelse($teams as $index => $team)
                            <article class="followup-team-card">
                                <div class="followup-team-card__head"><strong>{{ $team['team_name'] ?? 'الفريق رقم '.($index + 1) }}</strong><span>الفريق {{ $index + 1 }}</span></div>
                                <div class="followup-team-metrics">
                                    <div><span>العدد المخطط</span><strong>{{ $team['planned_members_count'] ?? '—' }}</strong></div>
                                    <div><span>الحضور الفعلي</span><strong>{{ $team['actual_attendance_count'] ?? '—' }}</strong></div>
                                    <div><span>حضور جميع الأعضاء</span><strong>{{ \App\Support\PostExecutionFieldPresenter::value("teams.$index.all_members_attended", $team['all_members_attended'] ?? null) }}</strong></div>
                                </div>
                                <p><span>المهام المنجزة</span>{{ $team['accomplished_tasks'] ?? '—' }}</p>
                            </article>
                        @empty
                            <div class="followup-empty">لا توجد بيانات فرق مسجلة بعد التنفيذ.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            @if($ceremonyItems->isNotEmpty())
                <section class="card followup-plan-card mb-4">
                    <div class="card-header"><i class="fas fa-list-check"></i> فقرات الفعالية</div>
                    <div class="card-body followup-ceremony-list">
                        @foreach($ceremonyItems as $item)
                            <div><strong>{{ $item['order'] ?? $loop->iteration }}. {{ $item['name'] ?? '—' }}</strong><span>{{ \App\Support\PostExecutionFieldPresenter::value('was_implemented', $item['was_implemented'] ?? null) }}</span><p>{{ $item['feedback'] ?? '—' }}</p></div>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="card followup-plan-card">
                <div class="card-header"><i class="fas fa-shield-check"></i> التحقق من بيانات ما بعد التنفيذ</div>
                <div class="card-body followup-verification-grid">
                    @forelse ($activity->postExecutionVerifications as $item)
                        <article>
                            <strong>{{ \App\Support\PostExecutionFieldPresenter::label($item->field_key, $item->field_label) }}</strong>
                            <p>{{ \App\Support\PostExecutionFieldPresenter::value($item->field_key, data_get($item->original_value, 'value')) }}</p>
                            <span class="badge {{ $item->status === 'correct' ? 'bg-success' : ($item->status === 'incorrect' ? 'bg-danger' : 'bg-warning text-dark') }}">{{ __('evaluation.statuses.' . $item->status) }}</span>
                        </article>
                    @empty
                        <div class="followup-empty">{{ __('evaluation.followup.no_results') }}</div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="col-xl-4">
            <section class="card followup-plan-card followup-attachments-card">
                <div class="card-header"><i class="fas fa-paperclip"></i> المرفقات</div>
                <div class="card-body">
                    @forelse ($activity->attachments as $attachment)
                        @php($isExternal = \Illuminate\Support\Str::startsWith((string) $attachment->file_path, ['http://', 'https://']))
                        <article class="followup-attachment">
                            <span class="followup-attachment__icon"><i class="fas fa-file-arrow-down"></i></span>
                            <div><strong>{{ $attachment->title ?: basename($attachment->file_path) }}</strong><small>{{ $attachment->file_type ?: 'ملف مرفق' }}</small></div>
                            <a class="btn btn-sm btn-outline-primary" href="{{ $isExternal ? $attachment->file_path : route('role.programs.attachments.download', [$attachment, 'download' => 1]) }}" {{ $isExternal ? 'target=_blank rel=noopener' : '' }}><i class="fas fa-download"></i> تنزيل</a>
                        </article>
                    @empty
                        <p class="followup-empty mb-0">لا توجد مرفقات.</p>
                    @endforelse

                    @if ($activity->activityEvaluation)
                        <a class="btn btn-primary w-100 mt-3" href="{{ route('evaluations.show', $activity->activityEvaluation) }}">{{ __('evaluation.followup.view_form') }}</a>
                    @endif
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
