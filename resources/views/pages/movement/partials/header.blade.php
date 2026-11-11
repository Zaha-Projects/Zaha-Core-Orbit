{{-- Page header card. Params: $icon, $title, optional $subtitle, optional $actionUrl + $actionLabel. --}}
<div class="card event-card mb-4">
    <div class="card-body d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fas {{ $icon }} text-primary"></i>
                <h1 class="h4 mb-0">{{ $title }}</h1>
            </div>
            @isset($subtitle)<p class="text-muted mb-0">{{ $subtitle }}</p>@endisset
        </div>
        @if(! empty($actionUrl))
            <a class="btn btn-primary" href="{{ $actionUrl }}"><i class="fas fa-plus"></i> {{ $actionLabel }}</a>
        @endif
    </div>
</div>
