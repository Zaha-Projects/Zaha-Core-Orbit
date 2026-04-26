@extends('layouts.app')


@php
    $title = __('app.roles.relations.agenda.edit_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp

@section('content')
    @include('pages.agenda.events._form', [
        'formAction' => route('role.relations.agenda.update', $agendaEvent),
        'formMethod' => 'PUT',
        'submitLabel' => __('app.roles.relations.agenda.actions.update'),
        'title' => $title,
        'subtitle' => $subtitle,
        'branchParticipations' => $branchParticipations,
        'headerBadge' => 'الإصدار V' . (int) ($agendaEvent->version ?? 1),
    ])

    <div class="event-module mt-3 agenda-form-page">
        <div class="card event-card">
            <div class="card-body">
                <div class="agenda-form-section">
                    <div class="agenda-form-section__head">
                        <h2 class="agenda-form-section__title">مشاركة الوحدات</h2>
                        <p class="agenda-form-section__text">يمكن تحديث حالة مشاركة كل وحدة مرتبطة بهذه الفعالية من هنا.</p>
                    </div>
                    <div class="row g-3">
                        @foreach ($departmentUnits as $unit)
                            @php
                                $canEditUnit = auth()->user()->hasRole('relations_manager') || auth()->user()->hasRole($unit->role_name);
                            @endphp
                            <div class="col-12 col-md-6">
                                <form method="POST" action="{{ route('role.relations.agenda.unit_participation.update', $agendaEvent) }}" class="agenda-form-section h-100">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="unit_key" value="{{ $unit->unit_key }}">
                                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                                        <div>
                                            <div class="fw-semibold">{{ $unit->name }}</div>
                                            <div class="small text-muted">تحديث حالة مشاركة الوحدة في النشاط.</div>
                                        </div>
                                        @if (!$canEditUnit)
                                            <span class="badge text-bg-light">{{ __('app.roles.relations.agenda.participation.view_only') }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <select class="form-select form-select-sm" name="status" {{ $canEditUnit ? '' : 'disabled' }}>
                                            <option value="unspecified" {{ ($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'unspecified' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.participation.unspecified') }}</option>
                                            <option value="participant" {{ ($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'participant' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.participation.participant') }}</option>
                                            <option value="not_participant" {{ ($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'not_participant' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.participation.not_participant') }}</option>
                                        </select>
                                        @if ($canEditUnit)
                                            <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.relations.agenda.actions.save') }}</button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
