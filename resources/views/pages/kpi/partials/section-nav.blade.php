{{-- KPI section tabs. Carries period/branch filters between pages. Param: $filters (optional) --}}
@php
    $kpiQuery = isset($filters) ? \Illuminate\Support\Arr::except($filters['query'], ['module']) : [];
    $kpiTabs = [
        ['route' => 'kpi.overview', 'active' => 'kpi.overview', 'icon' => 'fa-gauge-high', 'label' => 'نظرة عامة'],
        ['route' => 'kpi.branches.index', 'active' => 'kpi.branches.*', 'icon' => 'fa-code-branch', 'label' => 'مؤشرات الفروع'],
        ['route' => 'kpi.events.index', 'active' => 'kpi.events.*', 'icon' => 'fa-layer-group', 'label' => 'مؤشرات الفعاليات'],
    ];
    if (\App\Services\Kpi\KpiScope::canViewUsers(auth()->user())) {
        $kpiTabs[] = ['route' => 'kpi.users.index', 'active' => 'kpi.users.*', 'icon' => 'fa-user-check', 'label' => 'مؤشرات المستخدمين'];
    }
@endphp
<nav class="kpi-tabs" aria-label="أقسام مؤشرات الأداء">
    @foreach ($kpiTabs as $tab)
        @php($isActive = request()->routeIs($tab['active']))
        <a href="{{ route($tab['route'], $tab['route'] === 'kpi.users.index' ? [] : $kpiQuery) }}" class="kpi-tabs__item {{ $isActive ? 'is-active' : '' }}" @if($isActive) aria-current="page" @endif>
            <i class="fas {{ $tab['icon'] }}" aria-hidden="true"></i> {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
