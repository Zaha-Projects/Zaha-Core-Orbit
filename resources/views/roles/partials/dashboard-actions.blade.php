@section('page_title', $title)
@section('page_breadcrumb', $title)

<div class="card stretch stretch-full">
    <div class="card-body">
        <h1 class="h4 mb-2">{{ $title }}</h1>
        <p class="text-muted mb-4">{{ $subtitle }}</p>

        <div class="row g-3">
            @foreach ($actions as $action)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="border rounded-3 p-3 h-100 d-flex flex-column">
                        <h2 class="h6 mb-2">{{ $action['title'] }}</h2>
                        <p class="text-muted mb-0 flex-grow-1">{{ $action['description'] }}</p>
                        @if (!empty($action['link']))
                            <a class="btn btn-sm btn-primary mt-3 align-self-start" href="{{ $action['link'] }}">
                                {{ __('app.common.open_section') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
