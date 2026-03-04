<div class="page-header page-header-clean">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">@yield('page_title', __('app.common.dashboard'))</h5>
        </div>
        <ul class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@yield('page_breadcrumb_home', 'Home')</a></li>
            <li class="breadcrumb-item">@yield('page_breadcrumb', __('app.common.dashboard'))</li>
        </ul>
    </div>

    <div class="page-header-right ms-auto">
        <button class="btn btn-sm btn-light-brand page-header-right-open-toggle d-md-none" type="button" aria-label="Toggle page actions">
            <i class="feather-sliders"></i>
        </button>

        <div class="page-header-right-items">
            <a href="javascript:void(0)" class="page-header-right-close-toggle d-md-none" aria-label="Close page actions">
                <i class="feather-x"></i>
            </a>
            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                @hasSection('page_header_actions')
                    @yield('page_header_actions')
                @endif
            </div>
        </div>
    </div>
</div>
