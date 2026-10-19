{{-- KPI navigation group. Include only when \App\Services\Kpi\KpiScope::canAccess($user). --}}
@php
    $kpiMenuOpen = request()->routeIs('kpi.*') || ($showOperationalReports && request()->routeIs('role.reports.*'));
    $kpiMenuId = $kpiMenuId ?? 'kpiMenu';
    $kpiModules = config('kpis.modules', []);
@endphp
<li class="side-item {{ $kpiMenuOpen ? 'selected' : '' }}">
    <a href="#{{ $kpiMenuId }}" data-bs-toggle="collapse" aria-expanded="{{ $kpiMenuOpen ? 'true' : 'false' }}" aria-controls="{{ $kpiMenuId }}"><i class="fas fa-chart-line"></i><span>مؤشرات الأداء والتقارير</span></a>
</li>
<li id="{{ $kpiMenuId }}" class="collapse {{ $kpiMenuOpen ? 'show' : '' }}">
    <ul class="list-unstyled pe-3 mb-2">
        <li class="side-item {{ request()->routeIs('kpi.overview') ? 'selected' : '' }}"><a href="{{ route('kpi.overview') }}"><i class="fas fa-gauge-high"></i><span>نظرة عامة</span></a></li>
        <li class="side-item {{ request()->routeIs('kpi.branches.*') ? 'selected' : '' }}"><a href="{{ route('kpi.branches.index') }}"><i class="fas fa-code-branch"></i><span>مؤشرات الفروع</span></a></li>
        @if (\App\Services\Kpi\KpiScope::canViewUsers(auth()->user()))
            <li class="side-item {{ request()->routeIs('kpi.users.*') ? 'selected' : '' }}"><a href="{{ route('kpi.users.index') }}"><i class="fas fa-user-check"></i><span>مؤشرات المستخدمين</span></a></li>
        @endif
        @foreach ($kpiModules as $moduleKey => $kpiModule)
            <li class="side-item {{ request()->routeIs('kpi.events.show') && request()->route('module') === $moduleKey ? 'selected' : '' }}"><a href="{{ route('kpi.events.show', $moduleKey) }}"><i class="fas {{ $kpiModule['icon'] }}"></i><span>{{ $kpiModule['label_ar'] }}</span></a></li>
        @endforeach
        @if ($showOperationalReports)
            <li class="side-item {{ request()->routeIs('role.reports.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.index') }}"><i class="fas fa-chart-simple"></i><span>التقارير التشغيلية</span></a></li>
        @endif
    </ul>
</li>
