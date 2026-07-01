<section class="agenda-approvals-hero mb-4">
    <div>
        <div class="agenda-approvals-eyebrow">
            <i class="feather-check-circle"></i>
            <span>{{ __('app.roles.relations.approvals.title') }}</span>
        </div>
        <h1>{{ __('app.roles.relations.approvals.title') }}</h1>
        <p>{{ __('app.roles.relations.approvals.subtitle') }}</p>
    </div>

    <div class="agenda-approvals-stats">
        @foreach($approvalStats as $stat)
            <div class="agenda-approval-stat agenda-approval-stat--{{ $stat['tone'] }}">
                <span>{{ $stat['label'] }}</span>
                <strong>{{ $stat['value'] }}</strong>
            </div>
        @endforeach
    </div>
</section>
