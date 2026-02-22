@php
    $theme = session('ui.theme', 'light');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
    $isArabic = app()->getLocale() === 'ar';
@endphp
<header class="nxl-header">
    <div class="header-wrapper">
        <div class="header-left d-flex align-items-center gap-4">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-arrow-right"></i></a>
            </div>
            <div class="dropdown nxl-h-item nxl-header-search">
                <a href="javascript:void(0);" class="nxl-head-link me-0" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="feather-search"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-search-dropdown">
                    <div class="input-group search-form">
                        <span class="input-group-text"><i class="feather-search fs-6 text-muted"></i></span>
                        <input type="text" class="form-control search-input-field" placeholder="Search...." />
                        <span class="input-group-text"><button type="button" class="btn-close"></button></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-right ms-auto">
            <div class="d-flex align-items-center">
                <div class="dropdown nxl-h-item nxl-header-language d-none d-sm-flex">
                    <a href="javascript:void(0);" class="nxl-head-link me-0 nxl-language-link" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                        <img src="{{ asset($isArabic ? 'assets/vendors/img/flags/4x3/sa.svg' : 'assets/vendors/img/flags/4x3/us.svg') }}" alt="" class="img-fluid wd-20" />
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-language-dropdown">
                        <div class="dropdown-divider mt-0"></div>
                        <div class="language-items-wrapper">
                            <div class="select-language px-4 py-2 hstack justify-content-between gap-4">
                                <div class="lh-lg">
                                    <h6 class="mb-0">Select Language</h6>
                                    <p class="fs-11 text-muted mb-0">2 languages avaiable!</p>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="row px-4 pt-3">
                                <div class="col-sm-6 col-6 language_select {{ $isArabic ? 'active' : '' }}">
                                    <form method="POST" action="{{ route('ui.locale', 'ar') }}">
                                        @csrf
                                        <button class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2" type="submit">
                                            <div class="avatar-image avatar-sm"><img src="{{ asset('assets/vendors/img/flags/1x1/sa.svg') }}" alt="" class="img-fluid" /></div>
                                            <span>العربية</span>
                                        </button>
                                    </form>
                                </div>
                                <div class="col-sm-6 col-6 language_select {{ $isArabic ? '' : 'active' }}">
                                    <form method="POST" action="{{ route('ui.locale', 'en') }}">
                                        @csrf
                                        <button class="btn p-0 border-0 bg-transparent d-flex align-items-center gap-2" type="submit">
                                            <div class="avatar-image avatar-sm"><img src="{{ asset('assets/vendors/img/flags/1x1/us.svg') }}" alt="" class="img-fluid" /></div>
                                            <span>English</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="nxl-h-item d-none d-sm-flex">
                    <div class="full-screen-switcher">
                        <a href="javascript:void(0);" class="nxl-head-link me-0" onclick="$('body').fullScreenHelper('toggle');">
                            <i class="feather-maximize maximize"></i>
                            <i class="feather-minimize minimize"></i>
                        </a>
                    </div>
                </div>

                <div class="nxl-h-item dark-light-theme">
                    <form method="POST" action="{{ route('ui.theme', $nextTheme) }}">
                        @csrf
                        <button type="submit" class="nxl-head-link me-0 border-0 bg-transparent {{ $theme === 'dark' ? 'light-button' : 'dark-button' }}" title="{{ __('app.layout.theme_toggle') }}">
                            <i class="feather-{{ $theme === 'dark' ? 'sun' : 'moon' }}"></i>
                        </button>
                    </form>
                </div>

                <div class="dropdown nxl-h-item">
                    <a class="nxl-head-link me-3" data-bs-toggle="dropdown" href="#" role="button" data-bs-auto-close="outside">
                        <i class="feather-bell"></i>
                        <span class="badge bg-danger nxl-h-badge">3</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-notifications-menu">
                        <div class="d-flex justify-content-between align-items-center notifications-head">
                            <h6 class="fw-bold text-dark mb-0">Notifications</h6>
                            <a href="javascript:void(0);" class="fs-11 text-success text-end ms-auto"><span>Make as Read</span></a>
                        </div>
                        <div class="notifications-item"><div class="notifications-desc"><a href="javascript:void(0);" class="font-body text-truncate-2-line"><span class="fw-semibold text-dark">Static</span> Theme notification item</a></div></div>
                        <div class="text-center notifications-footer"><a href="javascript:void(0);" class="fs-13 fw-semibold text-dark">All Notifications</a></div>
                    </div>
                </div>

                @auth
                    <div class="dropdown nxl-h-item">
                        <a href="javascript:void(0);" data-bs-toggle="dropdown" role="button" data-bs-auto-close="outside">
                            <img src="{{ asset('assets/images/avatar/1.png') }}" alt="user-image" class="img-fluid user-avtar me-0" />
                        </a>
                        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                            <div class="dropdown-header"><div class="d-flex align-items-center"><img src="{{ asset('assets/images/avatar/1.png') }}" alt="" class="img-fluid user-avtar" /><div><h6 class="text-dark mb-0">{{ auth()->user()->name }}</h6></div></div></div>
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0);" class="dropdown-item"><i class="feather-user"></i><span>Profile Details</span></a>
                            <a href="javascript:void(0);" class="dropdown-item"><i class="feather-settings"></i><span>Account Settings</span></a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item" type="submit"><i class="feather-log-out"></i><span>{{ __('app.common.logout') }}</span></button></form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>
