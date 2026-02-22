@php
    $pageTitle = trim($__env->yieldContent('page_title', __('app.common.dashboard')));
    $pageSubtitle = trim($__env->yieldContent('page_subtitle', ''));
    $breadcrumbCurrent = trim($__env->yieldContent('breadcrumb_current', $pageTitle));
@endphp
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">{{ $pageTitle }}</h5>
            @if ($pageSubtitle !== '')
                <p class="text-muted fs-12 mb-0">{{ $pageSubtitle }}</p>
            @endif
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            <li class="breadcrumb-item">{{ $breadcrumbCurrent }}</li>
        </ul>
    </div>
    <div class="page-header-right ms-auto">
        <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
            <div class="reportrange-picker d-flex align-items-center px-3" style="height:40px;">JAN 24, 26 - FEB 22, 26</div>
            <div class="dropdown filter-dropdown">
                <a class="btn btn-md btn-light-brand" data-bs-toggle="dropdown" data-bs-offset="0, 10" data-bs-auto-close="outside">
                    <i class="feather-filter me-2"></i>
                    <span>Filter</span>
                </a>
                <div class="dropdown-menu dropdown-menu-end"><a href="javascript:void(0);" class="dropdown-item">Static Filter</a></div>
            </div>
        </div>
    </div>
</div>
