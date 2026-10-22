@extends('layouts.app')

@section('title', 'مؤشرات الفروع')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
@php
    $kpiSortLink = function (string $column) use ($filters, $sort, $direction): string {
        $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

        return route('kpi.branches.index', $filters['query'] + ['sort' => $column, 'dir' => $nextDirection]);
    };
    $kpiSortIcon = fn (string $column): string => $sort !== $column ? 'fa-sort' : ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
    $kpiBranchQuery = \Illuminate\Support\Arr::except($filters['query'], ['branch_id', 'module']);
@endphp
<div class="kpi-dashboard" dir="rtl">
    @include('pages.kpi.partials.header', [
        'title' => 'مقارنة الفروع',
        'subtitle' => 'مؤشرات فعاليات الفروع (الأنشطة الشهرية، الإفطارات، البازارات) لكل فرع ضمن نطاقك. لا يوجد ترتيب موزون أو «أفضل فرع» حتى اعتماد معادلة التقييم.',
        'icon' => 'fa-code-branch',
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.branches.index'), 'showModule' => true])

    <section class="kpi-section" aria-labelledby="kpi-branches-title">
        <div class="kpi-section__head">
            <h2 id="kpi-branches-title">مقارنة الفروع</h2>
            <p>الأجندة السنوية لا تُضاف هنا لأن تنفيذ الفرع لها يُحتسب عبر نشاطه الشهري. اضغط على عنوان العمود للترتيب.</p>
        </div>
        @if ($rows->isEmpty())
            <div class="kpi-empty"><i class="fas fa-code-branch" aria-hidden="true"></i> {{ \App\Services\Kpi\KpiFormatter::UNAVAILABLE }} ضمن نطاقك.</div>
        @else
            <div class="table-responsive kpi-table-wrap">
                <table class="table align-middle kpi-table">
                    <caption class="visually-hidden">مقارنة الفروع — {{ $filters['period']->label() }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" aria-sort="{{ $sort === 'branch' ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <a href="{{ $kpiSortLink('branch') }}" class="kpi-sort">الفرع <i class="fas {{ $kpiSortIcon('branch') }}" aria-hidden="true"></i></a>
                            </th>
                            @foreach ($columns as $columnKey => $column)
                                <th scope="col" aria-sort="{{ $sort === $columnKey ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}" title="{{ $column['description'] }}">
                                    <a href="{{ $kpiSortLink($columnKey) }}" class="kpi-sort">{{ $column['label'] }} <i class="fas {{ $kpiSortIcon($columnKey) }}" aria-hidden="true"></i></a>
                                    <small class="kpi-card__code d-block">{{ $column['code'] }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <th scope="row"><a href="{{ route('kpi.branches.show', [$row['branch_id']] + $kpiBranchQuery) }}">{{ $row['branch'] }}</a></th>
                                @foreach ($columns as $columnKey => $column)
                                    @php($cell = $row['values'][$columnKey])
                                    <td class="{{ $cell['value'] === null ? 'is-unavailable' : '' }}">{{ $cell['formatted'] }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
