@extends('layouts.app')

@section('page_title', __('theme_dashboard.page_title'))
@section('page_breadcrumb', __('theme_dashboard.page_title'))

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/main.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .theme-dashboard-card {
            border-radius: 1rem;
            border: 1px solid var(--border-soft, #dbe3ef);
            background: var(--card-bg, #fff);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        }

        .theme-dashboard-stat {
            border-radius: 1rem;
            color: #fff;
            min-height: 135px;
        }

        .theme-dashboard-stat--blue { background: linear-gradient(135deg, #00a9c4, #2fc9e2); }
        .theme-dashboard-stat--green { background: linear-gradient(135deg, #00a37f, #1ec7a0); }
        .theme-dashboard-stat--purple { background: linear-gradient(135deg, #4f5ee0, #7a88ff); }
        .theme-dashboard-stat--gold { background: linear-gradient(135deg, #d1992b, #ffc247); color: #1f2937; }

        .theme-dashboard-actions .btn {
            border-radius: .75rem;
        }

        .app-skin-dark .theme-dashboard-card {
            border-color: #2d3f5d;
            background: #1f2a3d;
            color: #edf3ff;
        }

        .app-skin-dark .nav-tabs .nav-link {
            color: #d2dbee;
        }

        .app-skin-dark .nav-tabs .nav-link.active {
            background: #27354d;
            color: #fff;
            border-color: #3a4e6f;
        }

        #dashboardCalendar {
            min-height: 320px;
        }
    </style>
@endpush

@section('content')
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="theme-dashboard-stat theme-dashboard-stat--blue p-3 d-flex flex-column justify-content-between">
                <div>{{ __('theme_dashboard.stats.active_projects') }}</div>
                <div class="h3 mb-0">12</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="theme-dashboard-stat theme-dashboard-stat--green p-3 d-flex flex-column justify-content-between">
                <div>{{ __('theme_dashboard.stats.open_tasks') }}</div>
                <div class="h3 mb-0">34</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="theme-dashboard-stat theme-dashboard-stat--purple p-3 d-flex flex-column justify-content-between">
                <div>{{ __('theme_dashboard.stats.team_members') }}</div>
                <div class="h3 mb-0">18</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="theme-dashboard-stat theme-dashboard-stat--gold p-3 d-flex flex-column justify-content-between">
                <div>{{ __('theme_dashboard.stats.progress') }}</div>
                <div class="h3 mb-0">86%</div>
            </div>
        </div>
    </div>

    <div class="theme-dashboard-card p-3 p-lg-4 mb-3">
        <h1 class="h4 mb-2">{{ __('theme_dashboard.welcome_title') }}</h1>
        <p class="text-muted mb-0">{{ __('theme_dashboard.welcome_subtitle') }}</p>
    </div>

    <div class="theme-dashboard-card p-3 p-lg-4 mb-3">
        <div class="theme-dashboard-actions d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-success" id="successToastBtn">{{ __('theme_dashboard.actions.success_toast') }}</button>
            <button type="button" class="btn btn-warning" id="warningToastBtn">{{ __('theme_dashboard.actions.warning_toast') }}</button>
            <button type="button" class="btn btn-info text-white" id="infoAlertBtn">{{ __('theme_dashboard.actions.info_alert') }}</button>
        </div>

        <div class="alert alert-success mb-2" role="alert">{{ __('theme_dashboard.alerts.success') }}</div>
        <div class="alert alert-warning mb-2" role="alert">{{ __('theme_dashboard.alerts.warning') }}</div>
        <div class="alert alert-info mb-0" role="alert">{{ __('theme_dashboard.alerts.info') }}</div>
    </div>

    <div class="theme-dashboard-card p-3 p-lg-4 mb-3">
        <ul class="nav nav-tabs mb-3" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="cards-tab" data-bs-toggle="tab" data-bs-target="#cards-tab-pane" type="button" role="tab">{{ __('theme_dashboard.tabs.cards') }}</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar-tab-pane" type="button" role="tab">{{ __('theme_dashboard.tabs.calendar') }}</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pagination-tab" data-bs-toggle="tab" data-bs-target="#pagination-tab-pane" type="button" role="tab">{{ __('theme_dashboard.tabs.pagination') }}</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="cards-tab-pane" role="tabpanel" aria-labelledby="cards-tab" tabindex="0">
                <div class="row g-3">
                    @forelse($cards ?? [] as $card)
                        <div class="col-12 col-md-6 col-xl-4">
                            <a href="{{ $card['url'] }}" class="card h-100 text-decoration-none text-reset">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="{{ $card['icon'] }}"></i>
                                        <h2 class="h6 mb-0">{{ $card['title'] }}</h2>
                                    </div>
                                    <p class="text-muted mb-0 small">{{ $card['description'] }}</p>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-secondary mb-0">{{ __('theme_dashboard.empty_cards') }}</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="tab-pane fade" id="calendar-tab-pane" role="tabpanel" aria-labelledby="calendar-tab" tabindex="0">
                <div class="row g-3">
                    <div class="col-12 col-xl-8">
                        <div id="dashboardCalendar" class="border rounded-3 p-2"></div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <label for="meetingDate" class="form-label">{{ __('theme_dashboard.meeting_date') }}</label>
                        <input id="meetingDate" type="text" class="form-control mb-2" placeholder="{{ __('theme_dashboard.meeting_placeholder') }}">
                        <div class="small text-muted">{{ __('theme_dashboard.current_time') }}: <span id="currentTime"></span></div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pagination-tab-pane" role="tabpanel" aria-labelledby="pagination-tab" tabindex="0">
                <p class="text-muted">{{ __('theme_dashboard.pagination_note') }}</p>
                <nav aria-label="{{ __('theme_dashboard.pagination_aria') }}">
                    <ul class="pagination mb-0">
                        <li class="page-item disabled"><span class="page-link">{{ __('theme_dashboard.pagination.prev') }}</span></li>
                        <li class="page-item active"><span class="page-link">1</span></li>
                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                        <li class="page-item"><a class="page-link" href="#">{{ __('theme_dashboard.pagination.next') }}</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locale = @json(app()->getLocale());
            const isArabic = locale === 'ar';

            const currentTimeEl = document.getElementById('currentTime');
            const updateTime = () => {
                if (!currentTimeEl) return;
                currentTimeEl.textContent = new Date().toLocaleString(locale);
            };
            updateTime();
            setInterval(updateTime, 1000);

            if (window.flatpickr) {
                flatpickr('#meetingDate', {
                    enableTime: true,
                    dateFormat: 'Y-m-d H:i',
                    time_24hr: true,
                    locale: isArabic ? 'ar' : 'default'
                });
            }

            const calendarEl = document.getElementById('dashboardCalendar');
            if (window.FullCalendar && calendarEl) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: isArabic ? 'ar' : 'en',
                    direction: isArabic ? 'rtl' : 'ltr',
                    headerToolbar: { start: 'prev,next today', center: 'title', end: 'dayGridMonth,timeGridWeek' },
                    events: [
                        { title: @json(__('theme_dashboard.calendar.events.planning')), start: new Date().toISOString().slice(0, 10) },
                        { title: @json(__('theme_dashboard.calendar.events.review')), start: new Date(Date.now() + 86400000).toISOString().slice(0, 10) }
                    ]
                });
                calendar.render();
            }

            toastr.options.positionClass = isArabic ? 'toast-top-left' : 'toast-top-right';
            toastr.options.rtl = isArabic;

            document.getElementById('successToastBtn')?.addEventListener('click', function () {
                toastr.success(@json(__('theme_dashboard.toasts.saved')), @json(__('theme_dashboard.toasts.title')));
            });

            document.getElementById('warningToastBtn')?.addEventListener('click', function () {
                toastr.warning(@json(__('theme_dashboard.toasts.warning')), @json(__('theme_dashboard.toasts.title')));
            });

            document.getElementById('infoAlertBtn')?.addEventListener('click', function () {
                Swal.fire({
                    icon: 'info',
                    title: @json(__('theme_dashboard.sweetalert.title')),
                    text: @json(__('theme_dashboard.sweetalert.text'))
                });
            });
        });
    </script>
@endpush
