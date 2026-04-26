@extends('layouts.new-theme-dashboard')

@section('title', __('app.common.dashboard'))

@section('content')
    <section class="mb-4">
        <div class="card p-4">
            <h1 class="h4 mb-2" data-i18n="welcome">{{ __('app.common.dashboard') }}</h1>
            <p class="text-muted mb-0" data-i18n="subtitle">{{ __('app.dashboard.no_role_message') }}</p>
        </div>
    </section>

    <section id="cardsSection" class="row g-3 mb-4">
        @forelse($cards ?? [] as $card)
            <div class="col-md-6 col-xl-3">
                <a href="{{ $card['url'] }}" class="text-decoration-none text-reset">
                    <div class="kpi-card stat-card-blue h-100">
                        <div class="kpi-head">
                            <p class="kpi-label mb-0">{{ $card['title'] }}</p>
                            <span class="kpi-icon"><i class="{{ $card['icon'] }}"></i></span>
                        </div>
                        <h3 class="kpi-value">{{ $loop->iteration }}</h3>
                        <p class="kpi-delta">{{ $card['description'] }}</p>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info mb-0">{{ __('app.dashboard.no_role_message') }}</div>
            </div>
        @endforelse
    </section>

    <section class="row g-3">
        <div class="col-lg-8">
            <div class="card p-3" id="calendarSection">
                <h2 class="h4 mb-3" data-i18n="calendar">التقويم</h2>
                <div id="calendar"></div>
                <div id="calendarFallback" class="alert alert-warning mt-3 d-none" data-i18n="calendar_fallback">تعذر تحميل التقويم.</div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-3 mb-3">
                <h2 class="h4" data-i18n="datetime">التاريخ والوقت</h2>
                <label for="meetingDate" class="form-label" data-i18n="meeting_date">موعد الاجتماع</label>
                <input id="meetingDate" class="form-control">
                <small id="currentTime" class="text-muted d-block mt-2"></small>
                <div id="dateFallback" class="alert alert-warning mt-2 d-none" data-i18n="date_fallback">تعذر تحميل أداة التاريخ.</div>
            </div>

            <div class="card p-3" id="notificationsSection">
                <h2 class="h4" data-i18n="notifications">الإشعارات</h2>
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAlerts" type="button" role="tab">Alerts</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPagination" type="button" role="tab">Pagination</button></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabAlerts" role="tabpanel">
                        <div class="d-grid gap-2 mb-3">
                            <button id="successToast" class="btn btn-theme" data-i18n="notify_success">تنبيه نجاح</button>
                            <button id="warningToast" class="btn btn-outline-warning" data-i18n="notify_warning">تنبيه تحذير</button>
                            <button id="infoAlert" class="btn btn-outline-info" data-i18n="notify_info">تنبيه معلومات</button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tabPagination" role="tabpanel">
                        <nav id="paginationSection" aria-label="Theme pagination">
                            <ul class="pagination mb-0">
                                <li class="page-item disabled"><span class="page-link">«</span></li>
                                <li class="page-item active"><span class="page-link">1</span></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">»</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
