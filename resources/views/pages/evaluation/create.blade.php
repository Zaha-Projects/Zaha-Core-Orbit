@extends('layouts.app')

@section('title', app()->isLocale('ar') ? $form->name_ar : $form->name_en)

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/evaluation-workflow-forms.css') }}">
@endpush

@section('content')
<div class="container-fluid py-4 evaluation-workflow-page">
    <header class="evaluation-hero mb-4">
        <div>
            <span class="evaluation-eyebrow"><i class="fas fa-star"></i> {{ __('evaluation.form.eyebrow') }}</span>
            <h1>{{ app()->isLocale('ar') ? $form->name_ar : $form->name_en }}</h1>
            <p>{{ __('evaluation.form.subtitle') }}</p>
        </div>
        <div class="evaluation-activity-summary">
            <strong>{{ $monthlyActivity->title }}</strong>
            <span><i class="fas fa-building"></i> {{ $monthlyActivity->branch?->name ?: '—' }}</span>
            <span><i class="fas fa-list-check"></i> {{ trans_choice('evaluation.form.questions_count', $form->questions->count(), ['count' => $form->questions->count()]) }}</span>
        </div>
    </header>

    <section class="evaluation-form-overview mb-4" aria-labelledby="evaluation-form-guide-title">
        <div class="evaluation-form-overview__heading"><span><i class="fas fa-lightbulb" aria-hidden="true"></i></span><div><h2 id="evaluation-form-guide-title">{{ __('evaluation.form.guide_title') }}</h2><p>{{ __('evaluation.form.guide_help') }}</p></div></div>
        <div class="evaluation-form-overview__items">
            <div><i class="fas fa-list-check" aria-hidden="true"></i><span><strong>{{ $form->questions->count() }}</strong>{{ __('evaluation.form.criteria') }}</span></div>
            <div><i class="fas fa-check-double" aria-hidden="true"></i><span><strong data-answered-count>0</strong>{{ __('evaluation.form.answered') }}</span></div>
            <div><i class="fas fa-scale-balanced" aria-hidden="true"></i><span>{{ __('evaluation.form.range_help') }}</span></div>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-danger shadow-sm"><strong>{{ __('evaluation.validation.fix_errors') }}</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('evaluations.store', $monthlyActivity) }}" data-evaluation-form>
        @csrf
        <input type="hidden" name="evaluation_form_id" value="{{ $form->id }}">

        <div class="evaluation-progress evaluation-progress--prominent mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2"><span><i class="fas fa-chart-line me-1" aria-hidden="true"></i> {{ __('evaluation.form.completion') }}</span><strong data-progress-label>0%</strong></div>
            <div class="progress"><div class="progress-bar" data-progress-bar style="width: 0%"></div></div>
        </div>

        <div class="evaluation-questions">
            @foreach ($form->questions as $question)
                <article class="evaluation-question-card" data-question-card>
                    <div class="question-number">{{ $loop->iteration }}</div>
                    <div class="question-content">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <label class="question-title" for="question-{{ $question->id }}">{{ app()->isLocale('ar') ? ($question->question_ar ?: $question->question) : ($question->question_en ?: $question->question) }}</label>
                                @if (app()->isLocale('ar') ? $question->description_ar : $question->description_en)
                                    <p class="question-description">{{ app()->isLocale('ar') ? $question->description_ar : $question->description_en }}</p>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">@if($question->is_required)<span class="question-required">{{ __('evaluation.form.required') }}</span>@endif<span class="score-range"><i class="fas fa-gauge-high" aria-hidden="true"></i> {{ __('evaluation.form.allowed_range', ['min' => $question->minimum_score, 'max' => $question->maximum_score]) }}</span></div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label" for="question-{{ $question->id }}">{{ __('evaluation.form.score') }} @if($question->is_required)<span class="text-danger">*</span>@endif</label>
                                <input id="question-{{ $question->id }}" type="number" class="form-control form-control-lg @error("answers.{$question->id}.score") is-invalid @enderror" name="answers[{{ $question->id }}][score]" value="{{ old("answers.{$question->id}.score") }}" min="{{ $question->minimum_score }}" max="{{ $question->maximum_score }}" step="0.01" {{ $question->is_required ? 'required' : '' }} data-score-input>
                                @error("answers.{$question->id}.score")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="question-note-{{ $question->id }}">{{ __('evaluation.form.question_note') }}</label>
                                <textarea id="question-note-{{ $question->id }}" class="form-control" rows="2" name="answers[{{ $question->id }}][note]" placeholder="{{ __('evaluation.form.question_note_placeholder') }}">{{ old("answers.{$question->id}.note") }}</textarea>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <section class="evaluation-notes-card mt-4"><div class="evaluation-notes-card__icon"><i class="fas fa-message" aria-hidden="true"></i></div><div class="flex-grow-1"><label class="form-label fw-bold" for="evaluation-notes">{{ __('evaluation.form.general_notes') }}</label><p class="small text-muted mb-2">{{ __('evaluation.form.general_notes_help') }}</p><textarea id="evaluation-notes" class="form-control" rows="4" name="notes" placeholder="{{ __('evaluation.form.general_notes_placeholder') }}">{{ old('notes') }}</textarea></div></section>

        <div class="evaluation-actions mt-4">
            <a class="btn btn-light" href="{{ route('followup.awaiting-evaluation') }}"><i class="fas fa-arrow-right"></i> {{ __('app.common.back') }}</a>
            <div class="evaluation-actions__status"><i class="fas fa-shield-alt" aria-hidden="true"></i><span>{{ __('evaluation.form.submit_help') }}</span></div>
            <button class="btn btn-primary btn-lg px-5" type="submit"><i class="fas fa-paper-plane"></i> {{ __('evaluation.form.submit') }}</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/evaluation-form.js') }}" defer></script>
@endpush
