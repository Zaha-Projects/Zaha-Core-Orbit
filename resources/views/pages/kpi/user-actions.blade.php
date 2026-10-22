@extends('layouts.app')

@section('title', $report['title'].' — '.$target->name)

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
@php($kpiActionQuery = \Illuminate\Support\Arr::except($filters['query'], ['module', 'branch_id']))
<div class="kpi-dashboard" dir="rtl">
    @include('pages.kpi.partials.header', [
        'title' => $report['title'],
        'subtitle' => 'إجراءات '.$target->name.' التي تُبنى عليها مؤشرات الأداء. كل صف إجراء مسجّل باسم المستخدم.',
        'icon' => $report['icon'],
    ])
    @unless ($isSelf)
        @include('pages.kpi.partials.section-nav')
    @endunless

    <div class="kpi-report-toolbar">
        @if (count($report['modules']) > 1)
            <nav class="kpi-tabs" aria-label="نوع الفعالية">
                @foreach ($report['modules'] as $moduleKey => $moduleLabel)
                    <a href="{{ route($route, $routeParameters + $kpiActionQuery + ['module' => $moduleKey] + ($report['filter'] ? ['filter' => $report['filter']] : [])) }}" class="kpi-tabs__item {{ $report['module'] === $moduleKey ? 'is-active' : '' }}" @if($report['module'] === $moduleKey) aria-current="page" @endif>{{ $moduleLabel }}</a>
                @endforeach
            </nav>
        @endif
        @if ($report['filters'] !== [])
            <nav class="kpi-tabs" aria-label="نتيجة القرار">
                <a href="{{ route($route, $routeParameters + $kpiActionQuery + ['module' => $report['module']]) }}" class="kpi-tabs__item {{ $report['filter'] === null ? 'is-active' : '' }}">الكل</a>
                @foreach ($report['filters'] as $filterKey => $filterLabel)
                    <a href="{{ route($route, $routeParameters + $kpiActionQuery + ['module' => $report['module'], 'filter' => $filterKey]) }}" class="kpi-tabs__item {{ $report['filter'] === $filterKey ? 'is-active' : '' }}">{{ $filterLabel }}</a>
                @endforeach
            </nav>
        @endif
        @if ($canExport && $report['rows'] !== null && $report['rows']->total() > 0)
            <a class="btn btn-outline-primary" href="{{ route($route.'.export', $routeParameters + $kpiActionQuery + ['module' => $report['module']] + ($report['filter'] ? ['filter' => $report['filter']] : [])) }}">
                <i class="fas fa-file-csv" aria-hidden="true"></i> تصدير CSV
            </a>
        @endif
    </div>
    <p class="kpi-card__note"><i class="fas fa-calendar" aria-hidden="true"></i> {{ $filters['period']->label() }}{{ $isSelf ? '' : ' — ضمن الفروع المسموح لك بها' }}</p>

    <section class="kpi-section" aria-label="{{ $report['title'] }}">
        @if ($report['rows'] === null || $report['rows']->isEmpty())
            <div class="kpi-empty"><i class="fas fa-table-list" aria-hidden="true"></i> لا توجد إجراءات مسجلة للفترة والنوع المحددين.</div>
        @else
            <div class="table-responsive kpi-table-wrap">
                <table class="table align-middle kpi-table">
                    <caption class="visually-hidden">{{ $report['title'] }}</caption>
                    <thead>
                        <tr>
                            @foreach ($report['columns'] as $column => $label)
                                @continue($column === 'duration_unit')
                                <th scope="col">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                <td class="text-start">{{ $row->user_name ?? '—' }}</td>
                                <td>{{ $row->module }}</td>
                                <td class="text-start">{{ $row->event ?? '—' }}</td>
                                <td>{{ $row->branch ?? '—' }}</td>
                                <td class="text-start">{{ $row->action ?? '—' }}</td>
                                <td>{{ $row->action_date ?? '—' }}</td>
                                <td>{{ $row->result ?? '—' }}</td>
                                <td>{{ $row->duration === null ? '—' : $row->duration.' '.$row->duration_unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $report['rows']->links() }}</div>
        @endif
    </section>
</div>
@endsection
