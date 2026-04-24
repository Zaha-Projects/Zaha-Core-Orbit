@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.title');
    $subtitle = ($viewScope ?? 'default') === 'all_branches'
        ? 'يتم إظهار الخطط الشهرية المعتمدة بالكامل والمنشورة فقط لبقية الفروع.'
        : __('app.roles.programs.monthly_activities.subtitle');
    $normalizeMonthlyPageStatus = function (?string $status): ?string {
        return match ((string) $status) {
            'approved' => 'approved',
            'draft' => 'draft',
            default => filled($status) ? 'submitted' : null,
        };
    };
    $monthlyStatusLabels = collect($monthlyStatusOptions ?? [])->pluck('name', 'code')->all();
    $workflowStatusLabel = function (?string $status) use ($monthlyStatusLabels, $normalizeMonthlyPageStatus): string {
        if (! $status) {
            return '-';
        }

        $normalizedStatus = $normalizeMonthlyPageStatus($status);

        return $monthlyStatusLabels[$normalizedStatus]
            ?? \App\Models\EventStatusLookup::labelFor('monthly_activities', $normalizedStatus ?: $status)
            ?? \App\Models\EventStatusLookup::labelFor('monthly_activities', $status);
    };

    $roleLabel = function (?string $roleKey): ?string {
        if (! $roleKey) {
            return null;
        }

        $translated = __('app.acl.roles.' . $roleKey);
        if ($translated !== 'app.acl.roles.' . $roleKey) {
            return $translated;
        }

        return (string) \Illuminate\Support\Str::of($roleKey)->replace('_', ' ')->title();
    };

    $calendarStatusLabels = [
        'draft' => $workflowStatusLabel('draft'),
        'submitted' => $workflowStatusLabel('submitted'),
        'approved' => $workflowStatusLabel('approved'),
    ];
    $branchFilterSelected = $filters['branch_id'] ?? '';
    $calendarCreateBranchId = filled($branchFilterSelected)
        ? (int) $branchFilterSelected
        : (method_exists(auth()->user(), 'primaryScopedBranchId')
            ? auth()->user()?->primaryScopedBranchId()
            : auth()->user()?->branch_id);
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path) . '?v=' . $version;
    };
@endphp

@section('content')
    <div
        class="event-module monthly-activities-module"
        data-calendar-endpoint="{{ route('role.relations.activities.calendar') }}"
        data-rtl="{{ app()->getLocale() === 'ar' ? '1' : '0' }}"
        data-create-url="{{ route('role.relations.activities.create') }}"
        data-default-branch-id="{{ $calendarCreateBranchId ?: '' }}"
    >
        <div class="card event-card mb-4">
            <div class="card-body">
                <h1 class="h4 mb-2">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">يرجى تصحيح الأخطاء التالية:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="event-kpi-grid">
            @foreach (($summaryCards ?? collect()) as $card)
                @php
                    $summaryFilterKey = (string) ($card['filter_key'] ?? '');
                    $isActiveSummaryCard = (string) ($filters['summary_filter'] ?? '') === $summaryFilterKey;
                    $summaryQuery = collect(request()->except(['page', 'summary_filter', 'status']))
                        ->when($summaryFilterKey !== '', fn ($query) => $query->put('summary_filter', $summaryFilterKey))
                        ->all();
                @endphp
                <a
                    href="{{ route('role.relations.activities.index', $summaryQuery) }}"
                    class="event-kpi-card d-block text-decoration-none text-reset {{ $isActiveSummaryCard ? 'border-primary shadow-sm' : '' }}"
                >
                    <div class="text-muted small">{{ $card['label'] }}</div>
                    <div class="event-kpi-value">{{ $card['count'] }}</div>
                </a>
            @endforeach
        </div>

        <div class="card event-card mb-4">
            <div class="card-body">
                <h2 class="event-section-title">{{ __('app.common.filter') }}</h2>
                <form method="GET" action="{{ route('role.relations.activities.index') }}" class="row event-form-grid">
                    @if (($viewScope ?? 'default') === 'all_branches')
                        <input type="hidden" name="scope" value="all_branches">
                    @endif
                    @if (!empty($filters['summary_filter']))
                        <input type="hidden" name="summary_filter" value="{{ $filters['summary_filter'] }}">
                    @endif
                    @if ($canFilterBranches)
                        @include('pages.shared.filters.select-field', [
                            'columnClass' => 'col-12 col-md-6 col-xl-3',
                            'fieldName' => 'branch_id',
                            'label' => __('app.roles.programs.monthly_activities.fields.branch'),
                            'placeholder' => __('app.roles.programs.monthly_activities.fields.branch_placeholder'),
                            'options' => $branches,
                            'selectedValue' => $branchFilterSelected,
                            'optionValueKey' => 'id',
                            'optionLabelKey' => 'name',
                        ])
                    @endif
                    @include('pages.shared.filters.month-and-year-select', [
                        'monthColumnClass' => 'col-6 col-xl-2',
                        'yearColumnClass' => 'col-6 col-xl-2',
                        'monthLabel' => __('app.roles.programs.monthly_activities.sync.month'),
                        'yearLabel' => __('app.roles.programs.monthly_activities.sync.year'),
                        'selectedMonth' => $filters['month'] ?? '',
                        'selectedYear' => $filters['year'] ?? '',
                    ])
                    @include('pages.shared.filters.status-select', [
                        'columnClass' => 'col-12 col-md-6 col-xl-3',
                        'fieldName' => 'status',
                        'label' => __('app.roles.programs.monthly_activities.fields.status'),
                        'placeholder' => 'كل الحالات',
                        'options' => $monthlyStatusOptions,
                        'selectedValue' => $filters['status'] ?? '',
                    ])
                    <div class="col-12 col-xl-2 event-actions"><button class="btn btn-outline-primary" type="submit">{{ __('app.common.filter') }}</button></div>
                </form>
            </div>
        </div>

        <div class="card event-card mb-4">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h2 class="event-section-title mb-1">{{ __('app.roles.programs.monthly_activities.create_title') }}</h2>
                    <p class="text-muted mb-0">استخدم نموذج الإدخال لإضافة نشاط جديد مع الحفاظ على نفس مسار الاعتمادات.</p>
                </div>
                <a href="{{ route('role.relations.activities.create') }}" class="btn btn-primary">{{ __('app.roles.programs.monthly_activities.actions.create') }}</a>
            </div>
        </div>

        <div class="agenda-view-switch mb-3" role="tablist">
            <button type="button" class="btn btn-sm btn-primary active" data-view-toggle="table" aria-pressed="true">بطاقات</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-view-toggle="calendar" aria-pressed="false">تقويم</button>
        </div>

        <div class="agenda-view-pane" data-view-pane="table">
            <div class="card event-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <h2 class="event-section-title mb-0">{{ __('app.roles.programs.monthly_activities.list_title') }}</h2>
                        <span class="text-muted small">عرض {{ $activities->count() }} من أصل {{ method_exists($activities, 'total') ? $activities->total() : $activities->count() }} نشاط</span>
                    </div>
                    <div class="monthly-cards-grid">
                        @forelse ($activities as $activity)
                            @php
                                $viewer = auth()->user();
                                $isSubmittedOrBeyond = in_array((string) $activity->status, ['submitted', 'in_review', 'approved', 'completed', 'closed'], true);
                                $isReadOnlyUnified = (bool) $activity->is_from_agenda
                                    && (string) $activity->plan_type === 'unified'
                                    && (string) optional($activity->agendaEvent)->event_type === 'mandatory';
                                $canBranchPartialEditUnified = $isReadOnlyUnified
                                    && (bool) config('monthly_activity.unified_branch_edit.enabled', true)
                                    && $viewer
                                    && method_exists($viewer, 'hasBranchScopedMonthlyVisibility')
                                    && $viewer->hasBranchScopedMonthlyVisibility()
                                    && ! $viewer->hasRole('super_admin');
                            @endphp
                            <article class="monthly-activity-card">
                                <div class="module-card-header">
                                    <div class="d-flex justify-content-between gap-2 align-items-start flex-wrap mb-0">
                                        <h3 class="h6 mb-1">{{ $activity->title }}</h3>
                                        @php($pageStatusKey = $normalizeMonthlyPageStatus($activity->status))
                                        <span class="event-status status-{{ $pageStatusKey ?? $activity->status }}">{{ $workflowStatusLabel($activity->status) }}</span>
                                    </div>
                                </div>
                                <div class="module-card-body">
                                    <div class="small text-muted mb-2">
                                        {{ $activity->agendaEvent?->event_name ? 'فعالية مرتبطة: '.$activity->agendaEvent->event_name : 'فعالية مستقلة' }}
                                    </div>
                                    <div class="monthly-activity-meta">
                                        <span>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</span>
                                        <span>{{ $activity->branch?->name ?? '-' }}</span>
                                        <span>{{ $activity->is_in_agenda ? 'من الأجندة' : 'إدخال يدوي' }}</span>
                                        <span>نسخة {{ (int) ($activity->plan_version ?: 1) }}</span>
                                    </div>
                                    <p class="text-muted small mt-2 mb-0">{{ \Illuminate\Support\Str::limit($activity->short_description ?: $activity->description ?: 'لا يوجد وصف مختصر.', 140) }}</p>
                                </div>
                                <div class="module-card-footer">
                                    <div class="event-actions">
                                    <a class="btn btn-sm btn-outline-dark" href="{{ route('role.relations.activities.show', $activity) }}">عرض</a>
                                    @if($isReadOnlyUnified && ! $canBranchPartialEditUnified)
                                        <span class="badge bg-success-subtle text-success">موحد معتمد — عرض فقط</span>
                                    @else
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'form' => 1]) }}">{{ __('app.roles.programs.monthly_activities.actions.edit') }}</a>
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post']) }}">إكمال بعد التنفيذ</a>
                                        @if (! $isSubmittedOrBeyond && ! $isReadOnlyUnified)
                                            <form method="POST" action="{{ route('role.relations.activities.submit', $activity) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.programs.monthly_activities.actions.submit') }}</button>
                                            </form>
                                        @else
                                            <span class="badge {{ $isReadOnlyUnified ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info' }}">
                                                {{ $isReadOnlyUnified ? 'موحد معتمد — قفل جزئي' : 'الحالة: ' . $workflowStatusLabel($activity->status) }}
                                            </span>
                                        @endif
                                    @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="text-muted">{{ __('app.roles.programs.monthly_activities.table.empty') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="mt-3 d-flex flex-column align-items-center gap-2">
                {{ $activities->links() }}
                @if ($activities->hasMorePages())
                    <a class="btn btn-outline-primary" href="{{ $activities->nextPageUrl() }}">عرض المزيد</a>
                @endif
            </div>
        </div>

        <div class="agenda-view-pane d-none" data-view-pane="calendar">
            <div class="card event-card">
                <div class="card-body">
                    <div class="agenda-calendar-toolbar mb-3 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="prev">السابق</button>
                        <h2 class="h6 mb-0" data-calendar-title></h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="next">التالي</button>
                    </div>
                    <div class="agenda-calendar-weekdays" data-calendar-weekdays></div>
                    <div class="agenda-calendar-grid" data-calendar-grid></div>
                </div>
            </div>
        </div>
    </div>
@push('styles')
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/event-ui-shared.css') }}">
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/monthly-activities-index.css') }}">
@endpush

@push('scripts')
    <script type="application/json" id="monthly-status-labels-json">@json($calendarStatusLabels)</script>
    <script src="{{ $versionedAsset('assets/js/ui-shared.js') }}"></script>
    <script src="{{ $versionedAsset('assets/js/monthly-activities-index.js') }}"></script>
@endpush
@endsection
