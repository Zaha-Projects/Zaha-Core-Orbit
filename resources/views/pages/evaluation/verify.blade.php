@extends('layouts.app')

@section('title', __('evaluation.verification.title'))

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/evaluation-workflow-forms.css') }}">
@endpush

@section('content')
@php
    $verificationTotal = $activity->postExecutionVerifications->count();
    $verificationReviewed = $activity->postExecutionVerifications->whereIn('status', ['correct', 'incorrect'])->count();
    $verificationIncorrect = $activity->postExecutionVerifications->where('status', 'incorrect')->count();
    $verificationRemaining = max(0, $verificationTotal - $verificationReviewed);
    $verificationProgress = $verificationTotal > 0 ? round(($verificationReviewed / $verificationTotal) * 100) : 0;
    $verificationReady = $verificationTotal > 0 && $verificationRemaining === 0;
@endphp
<div class="container-fluid py-4 evaluation-workflow-page">
    <header class="evaluation-hero mb-4">
        <div>
            <span class="evaluation-eyebrow"><i class="fas fa-clipboard-check"></i> {{ __('evaluation.verification.eyebrow') }}</span>
            <h1>{{ __('evaluation.verification.title') }}</h1>
            <p>{{ __('evaluation.verification.subtitle') }}</p>
        </div>
        <div class="evaluation-activity-summary">
            <strong>{{ $activity->title }}</strong>
            <span><i class="fas fa-building"></i> {{ $activity->branch?->name ?: '—' }}</span>
            <span><i class="fas fa-calendar"></i> {{ optional($activity->proposed_date)->translatedFormat('d M Y') }}</span>
        </div>
    </header>

    <section class="verification-overview mb-4" aria-label="{{ __('evaluation.verification.progress_title') }}">
        <div class="verification-overview__copy"><span class="verification-overview__icon"><i class="fas fa-search" aria-hidden="true"></i></span><div><h2>{{ __('evaluation.verification.progress_title') }}</h2><p>{{ __('evaluation.verification.progress_help') }}</p></div></div>
        <div class="verification-overview__stats"><span><strong>{{ $verificationReviewed }}</strong>{{ __('evaluation.verification.reviewed') }}</span><span><strong>{{ $verificationRemaining }}</strong>{{ __('evaluation.verification.remaining') }}</span><span class="is-incorrect"><strong>{{ $verificationIncorrect }}</strong>{{ __('evaluation.statuses.incorrect') }}</span></div>
        <div class="verification-overview__progress"><div class="d-flex justify-content-between small fw-bold mb-2"><span>{{ __('evaluation.form.completion') }}</span><span>{{ $verificationProgress }}%</span></div><div class="progress" role="progressbar" aria-valuenow="{{ $verificationProgress }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ $verificationProgress }}%"></div></div></div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm" role="alert">
            <strong>{{ __('evaluation.validation.fix_errors') }}</strong>
            <ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('evaluations.verification.update', $activity) }}" data-verification-form>
        @csrf
        @method('PUT')

        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 fw-bold mb-1">{{ __('evaluation.verification.fields_title') }}</h2>
                <p class="text-muted mb-0">{{ __('evaluation.verification.fields_help') }}</p>
            </div>
            <span class="badge rounded-pill bg-primary-subtle text-primary px-3 py-2">
                {{ trans_choice('evaluation.verification.fields_count', $verificationTotal, ['count' => $verificationTotal]) }}
            </span>
        </div>

        <div class="verification-list">
            @foreach ($activity->postExecutionVerifications as $item)
                @php
                    $selectedStatus = old("items.{$item->id}.status", $item->status === 'incorrect' ? 'incorrect' : 'correct');
                    $fieldLabel = \App\Support\PostExecutionFieldPresenter::label($item->field_key, $item->field_label);
                    $submittedValue = data_get($item->original_value, 'value');
                    $correctedValue = old("items.{$item->id}.corrected_value", data_get($item->corrected_value, 'value'));
                @endphp
                <article class="verification-card {{ $selectedStatus === 'incorrect' ? 'is-incorrect' : 'is-correct' }}" data-verification-item>
                    <div class="verification-card__heading">
                        <span class="verification-index">{{ $loop->iteration }}</span>
                        <div>
                            <h3>{{ $fieldLabel }}</h3>
                            <span>{{ __('evaluation.submitted_value') }}</span>
                        </div>
                    </div>
                    <div class="verification-card__content">
                    <div class="submitted-value"><span class="submitted-value__label"><i class="fas fa-database" aria-hidden="true"></i> {{ __('evaluation.submitted_value') }}</span><strong>{{ \App\Support\PostExecutionFieldPresenter::value($item->field_key, $submittedValue) }}</strong></div>
                    <div class="row g-3 align-items-end verification-decision-panel">
                        <div class="col-lg-4">
                            <label class="form-label" for="verification-status-{{ $item->id }}">{{ __('evaluation.verification.decision') }}</label>
                            <select id="verification-status-{{ $item->id }}" class="form-select" name="items[{{ $item->id }}][status]" required data-verification-status>
                                <option value="correct" {{ $selectedStatus === 'correct' ? 'selected' : '' }}>{{ __('evaluation.statuses.correct') }}</option>
                                <option value="incorrect" {{ $selectedStatus === 'incorrect' ? 'selected' : '' }}>{{ __('evaluation.statuses.incorrect') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-4" data-correction-field>
                            <label class="form-label" for="corrected-value-{{ $item->id }}">{{ __('evaluation.corrected_value') }}</label>
                            <input id="corrected-value-{{ $item->id }}" class="form-control" name="items[{{ $item->id }}][corrected_value]" value="{{ \App\Support\PostExecutionFieldPresenter::inputValue($correctedValue) }}" placeholder="{{ __('evaluation.verification.corrected_placeholder') }}">
                        </div>
                        <div class="col-lg-4" data-correction-field>
                            <label class="form-label" for="verification-note-{{ $item->id }}">{{ __('evaluation.notes') }}</label>
                            <input id="verification-note-{{ $item->id }}" class="form-control" name="items[{{ $item->id }}][note]" value="{{ old("items.{$item->id}.note", $item->note) }}" placeholder="{{ __('evaluation.verification.note_placeholder') }}">
                        </div>
                    </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="evaluation-actions mt-4">
            <a class="btn btn-light" href="{{ route('followup.awaiting-evaluation') }}"><i class="fas fa-arrow-right"></i> {{ __('app.common.back') }}</a>
            <div class="evaluation-actions__status {{ $verificationReady ? 'is-ready' : '' }}"><i class="fas {{ $verificationReady ? 'fa-check-circle' : 'fa-info-circle' }}" aria-hidden="true"></i><span>{{ $verificationReady ? __('evaluation.verification.ready_help') : __('evaluation.verification.pending_help', ['count' => $verificationRemaining]) }}</span></div>
            <div class="d-flex gap-2 evaluation-actions__buttons">
                <button class="btn btn-primary px-4" type="submit"><i class="fas fa-save"></i> {{ __('evaluation.verification.save') }}</button>
                @if($verificationReady)
                <a class="btn btn-success px-4" href="{{ route('evaluations.create', $activity) }}"><i class="fas fa-star"></i> {{ __('evaluation.followup.start_evaluation') }}</a>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/evaluation-verification-form.js') }}" defer></script>
@endpush
