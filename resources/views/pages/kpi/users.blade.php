@extends('layouts.app')

@section('title', 'مؤشرات المستخدمين')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
@php
    $kpiUsersQuery = $filters['query'] + ['role' => $family] + ($userFilter ? ['user' => $userFilter] : []);
    $kpiSortLink = function (string $column) use ($kpiUsersQuery, $sort, $direction): string {
        return route('kpi.users.index', $kpiUsersQuery + ['sort' => $column, 'dir' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc']);
    };
    $kpiSortIcon = fn (string $column): string => $sort !== $column ? 'fa-sort' : ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
    $kpiPeriodQuery = \Illuminate\Support\Arr::only($filters['query'], ['year', 'month']);
@endphp
<div class="kpi-dashboard" dir="rtl" data-kpi-dashboard>
    @include('pages.kpi.partials.header', [
        'title' => 'مؤشرات المستخدمين',
        'subtitle' => 'مقارنة المستخدمين من الدور نفسه في مؤشرات قابلة للنسبة إلى منفّذها. لا يوجد ترتيب عام أو درجة إجمالية أو «أفضل موظف».',
        'icon' => 'fa-user-check',
    ])
    @include('pages.kpi.partials.section-nav')

    <nav class="kpi-tabs" aria-label="الدور">
        @foreach ($families as $familyKey => $familyConfig)
            <a href="{{ route('kpi.users.index', $filters['query'] + ['role' => $familyKey]) }}" class="kpi-tabs__item {{ $family === $familyKey ? 'is-active' : '' }}" @if($family === $familyKey) aria-current="page" @endif>
                <i class="fas {{ $familyConfig['icon'] }}" aria-hidden="true"></i> {{ $familyConfig['label_ar'] }}
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('kpi.users.index') }}" class="kpi-filters" aria-label="تصفية المستخدمين">
        <input type="hidden" name="role" value="{{ $family }}">
        <div class="kpi-filters__field">
            <label for="kpi-year" class="form-label">السنة</label>
            <select id="kpi-year" name="year" class="form-select">
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption }}" @if($filters['year'] === $yearOption) selected @endif>{{ $yearOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="kpi-month" class="form-label">الشهر</label>
            <select id="kpi-month" name="month" class="form-select">
                <option value="">كامل السنة</option>
                @foreach ($months as $monthNumber => $monthLabel)
                    <option value="{{ $monthNumber }}" @if($filters['month'] === $monthNumber) selected @endif>{{ $monthLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="kpi-branch" class="form-label">الفرع</label>
            <select id="kpi-branch" name="branch_id" class="form-select">
                <option value="">{{ \App\Services\Kpi\KpiScope::hasOversight(auth()->user()) ? 'جميع الفروع' : 'كل الفروع ضمن نطاقك' }}</option>
                @foreach ($branches as $branchOption)
                    <option value="{{ $branchOption->id }}" @if($filters['branch_id'] === (int) $branchOption->id) selected @endif>{{ $branchOption->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="kpi-user" class="form-label">المستخدم</label>
            <select id="kpi-user" name="user" class="form-select">
                <option value="">جميع المستخدمين</option>
                @foreach ($userOptions as $option)
                    <option value="{{ $option->id }}" @if($userFilter === (int) $option->id) selected @endif>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="kpi-module" class="form-label">نوع الفعالية</label>
            <select id="kpi-module" name="module" class="form-select">
                <option value="">جميع الفعاليات</option>
                @foreach ($moduleOptions as $moduleKey => $moduleLabel)
                    <option value="{{ $moduleKey }}" @if($filters['module'] === $moduleKey) selected @endif>{{ $moduleLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter" aria-hidden="true"></i> تطبيق</button>
            <a href="{{ route('kpi.users.index', ['role' => $family]) }}" class="btn btn-outline-secondary">إعادة ضبط</a>
        </div>
    </form>
    @if ($filters['rejected'] !== [])
        <div class="alert alert-warning kpi-alert" role="status"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i> تم تجاهل قيم تصفية غير صالحة.</div>
    @endif

    <section class="kpi-section" aria-labelledby="kpi-users-title">
        <div class="kpi-section__head">
            <h2 id="kpi-users-title">{{ $families[$family]['label_ar'] }} — {{ $filters['period']->label() }}</h2>
            <p>تُحتسب إجراءات كل مستخدم في الفروع ضمن نطاقك فقط. اضغط عنوان العمود للترتيب.</p>
        </div>
        @if ($rows->isEmpty())
            <div class="kpi-empty"><i class="fas fa-users" aria-hidden="true"></i> لا يوجد مستخدمون بهذا الدور ضمن نطاقك.</div>
        @else
            <div class="table-responsive kpi-table-wrap">
                <table class="table align-middle kpi-table">
                    <caption class="visually-hidden">مقارنة {{ $families[$family]['label_ar'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" aria-sort="{{ $sort === 'user' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}"><a href="{{ $kpiSortLink('user') }}" class="kpi-sort">المستخدم <i class="fas {{ $kpiSortIcon('user') }}" aria-hidden="true"></i></a></th>
                            <th scope="col">الفرع</th>
                            @foreach ($columns as $code => $column)
                                <th scope="col" aria-sort="{{ $sort === $code ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}" title="{{ $column['description_ar'] }}">
                                    <a href="{{ $kpiSortLink($code) }}" class="kpi-sort">{{ $column['name_ar'] }} <i class="fas {{ $kpiSortIcon($code) }}" aria-hidden="true"></i></a>
                                    <small class="kpi-card__code d-block">{{ $code }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <th scope="row"><a href="{{ route('kpi.users.show', [$row['user_id']] + $kpiPeriodQuery) }}">{{ $row['user'] }}</a></th>
                                <td>{{ $row['branch'] ?? '—' }}</td>
                                @foreach ($columns as $code => $column)
                                    @php($cell = $row['values'][$code])
                                    <td class="{{ $cell['value'] === null ? 'is-unavailable' : '' }}">{{ $cell['formatted'] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="kpi-section" aria-label="تحليلات المستخدمين">
        @include('pages.kpi.partials.charts', ['charts' => $charts])
    </section>
</div>
@endsection

@push('scripts')
    @include('layouts.app.partials.chartjs')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/kpi-dashboard.js') }}"></script>
@endpush
