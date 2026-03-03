<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">@yield('page_title', __('app.common.dashboard'))</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@yield('page_breadcrumb_home', 'Home')</a></li>
            <li class="breadcrumb-item">@yield('page_breadcrumb', __('app.common.dashboard'))</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
            @hasSection('page_header_actions')
                @yield('page_header_actions')
            @else
                <div class="reportrange-picker d-flex align-items-center px-3" style="height:40px;">JAN 24, 26 - FEB 22, 26</div>
                <div class="dropdown filter-dropdown">
                    <a class="btn btn-md btn-light-brand" data-bs-toggle="dropdown" data-bs-offset="0, 10" data-bs-auto-close="outside">
                        <i class="feather-filter me-2"></i>
                        <span>{{ __('app.common.filter') }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end"><a href="javascript:void(0);" class="dropdown-item">{{ __('app.common.static_filter') }}</a></div>
                </div>
            @endif
        </div>
    </div>
</div>
