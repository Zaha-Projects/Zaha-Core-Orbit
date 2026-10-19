@extends('layouts.app')

@section('title', 'مؤشرات الفروع')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
<div class="kpi-dashboard" dir="rtl">
    @include('pages.kpi.partials.header', [
        'title' => 'مؤشرات الفروع',
        'subtitle' => 'أعداد المراحل لكل فرع ضمن نطاقك. مؤشرات أداء الفروع التفصيلية تُضاف في المرحلة الرابعة.',
        'icon' => 'fa-code-branch',
        'filters' => $filters,
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.branches.index'), 'showModule' => true])

    <section class="kpi-section" aria-labelledby="kpi-branches-title">
        <div class="kpi-section__head">
            <h2 id="kpi-branches-title">الفعاليات حسب الفرع</h2>
            <p>الأجندة السنوية تُحتسب لكل فرع مشارك فيها، لذلك قد تظهر الفعالية نفسها لأكثر من فرع.</p>
        </div>
        @if ($rows === [])
            <div class="kpi-empty"><i class="fas fa-code-branch" aria-hidden="true"></i> {{ \App\Services\Kpi\KpiFormatter::UNAVAILABLE }} ضمن نطاقك.</div>
        @else
            <div class="table-responsive kpi-table-wrap">
                <table class="table align-middle kpi-table">
                    <caption class="visually-hidden">أعداد مراحل الفعاليات لكل فرع — {{ $filters['period']->label() }}</caption>
                    <thead>
                        <tr>
                            <th scope="col" rowspan="2">الفرع</th>
                            @foreach ($moduleKeys as $moduleKey)
                                <th scope="colgroup" colspan="{{ count($stages) }}" class="kpi-table__module kpi-table__module--{{ $modules[$moduleKey]['accent'] }}"><i class="fas {{ $modules[$moduleKey]['icon'] }}" aria-hidden="true"></i> {{ $modules[$moduleKey]['label_ar'] }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach ($moduleKeys as $moduleKey)
                                @foreach ($stages as $stage)
                                    <th scope="col">{{ $stage['label_ar'] }}</th>
                                @endforeach
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <th scope="row">{{ $row['branch'] }}</th>
                                @foreach ($moduleKeys as $moduleKey)
                                    @foreach ($row['modules'][$moduleKey] as $value)
                                        <td class="{{ $value === null ? 'is-unavailable' : '' }}" @if($value === null) title="لا ينطبق على هذه الوحدة" @endif>{{ $value === null ? '—' : number_format($value) }}</td>
                                    @endforeach
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
