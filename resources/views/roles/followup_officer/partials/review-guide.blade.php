@once
    @push('styles')
        <style>
            .fu-guide{border:0;border-radius:20px;box-shadow:0 8px 28px rgba(0,169,196,.08);overflow:hidden}.fu-guide-heading{background:linear-gradient(135deg,#e8fbfe,#f8feff)}.fu-guide-icon{width:48px;height:48px;display:grid;place-items:center;border-radius:15px;background:#e8fbfe;color:#008ca3;font-size:1.15rem;flex:0 0 auto}.fu-guide-step{height:100%;padding:1rem;border:1px solid #dff5f8;border-radius:16px;background:#fff}.fu-guide-number{display:inline-grid;place-items:center;width:28px;height:28px;border-radius:50%;background:#00a9c4;color:#fff;font-weight:800}.fu-guide-note{border-inline-start:4px solid #00a9c4;background:#f2fdff;border-radius:10px;padding:.8rem 1rem}
        </style>
    @endpush
@endonce

<section class="card fu-guide mb-4" aria-labelledby="followup-review-guide-title">
    <div class="card-body p-0">
        <div class="fu-guide-heading p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="fu-guide-icon"><i class="fas fa-info-circle" aria-hidden="true"></i></div>
                <div>
                    <h2 id="followup-review-guide-title" class="h5 fw-bold mb-1">{{ __('evaluation.followup.guide.title') }}</h2>
                    <p class="text-muted mb-0">{{ __('evaluation.followup.guide.intro') }}</p>
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="row g-3">
                @foreach ([
                    ['review', 'fa-clipboard-list'],
                    ['verify', 'fa-search'],
                    ['evaluate', 'fa-star'],
                ] as $index => [$step, $icon])
                    <div class="col-lg-4">
                        <div class="fu-guide-step">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="fu-guide-number">{{ $index + 1 }}</span>
                                <div class="fu-guide-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></div>
                            </div>
                            <h3 class="h6 fw-bold">{{ __('evaluation.followup.guide.steps.'.$step.'.title') }}</h3>
                            <p class="small text-muted mb-0">{{ __('evaluation.followup.guide.steps.'.$step.'.description') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="fu-guide-note mt-3 d-flex gap-2 align-items-start">
                <i class="fas fa-lightbulb mt-1 text-info" aria-hidden="true"></i>
                <div><strong>{{ __('evaluation.followup.guide.note_title') }}</strong> {{ __('evaluation.followup.guide.note') }}</div>
            </div>
        </div>
    </div>
</section>
