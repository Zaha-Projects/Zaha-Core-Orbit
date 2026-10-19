@extends('layouts.app')

@section('title', $report['title'])

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
<div class="kpi-dashboard" dir="rtl">
    @include('pages.kpi.partials.header', [
        'title' => $report['title'],
        'subtitle' => 'تقرير تفصيلي خلف مؤشر الأداء. عرض فقط؛ التصدير في مرحلة لاحقة.',
        'icon' => $report['icon'],
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.reports.show', $reportKey), 'showModule' => false])

    @if (count($report['modules']) > 1)
        <nav class="kpi-tabs" aria-label="نوع الفعالية">
            @foreach ($report['modules'] as $moduleKey => $moduleLabel)
                <a href="{{ route('kpi.reports.show', [$reportKey] + $filters['query'] + ['module' => $moduleKey]) }}" class="kpi-tabs__item {{ $report['module'] === $moduleKey ? 'is-active' : '' }}" @if($report['module'] === $moduleKey) aria-current="page" @endif>{{ $moduleLabel }}</a>
            @endforeach
        </nav>
    @endif

    @foreach ($report['notes'] as $note)
        <p class="kpi-card__note"><i class="fas fa-circle-info" aria-hidden="true"></i> {{ $note }}</p>
    @endforeach

    <section class="kpi-section" aria-label="{{ $report['title'] }}">
        @if ($report['rows'] === null || $report['rows']->isEmpty())
            <div class="kpi-empty"><i class="fas fa-table-list" aria-hidden="true"></i> {{ \App\Services\Kpi\KpiFormatter::UNAVAILABLE }} للفترة والنطاق المحددين.</div>
        @else
            <div class="table-responsive kpi-table-wrap">
                <table class="table align-middle kpi-table">
                    <caption class="visually-hidden">{{ $report['title'] }} — {{ $filters['period']->label() }}</caption>
                    <thead>
                        <tr>
                            @foreach ($report['columns'] as $label)
                                <th scope="col">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                @foreach (array_keys($report['columns']) as $column)
                                    @if ($column === 'on_time')
                                        <td>
                                            @if ((int) $row->on_time === 1)
                                                <span class="kpi-state kpi-state--achieved"><i class="fas fa-check" aria-hidden="true"></i> نعم</span>
                                            @else
                                                <span class="kpi-state kpi-state--not-achieved"><i class="fas fa-xmark" aria-hidden="true"></i> لا</span>
                                            @endif
                                        </td>
                                    @elseif ($column === 'event' || $column === 'branch' || $column === 'responsible')
                                        <td class="text-start">{{ $row->{$column} ?? '—' }}</td>
                                    @else
                                        <td>{{ $row->{$column} ?? '—' }}</td>
                                    @endif
                                @endforeach
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
