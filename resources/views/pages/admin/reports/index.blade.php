@extends('layouts.app')

@php
    $title = 'تقارير الإدارة';
    $subtitle = 'صفحة تقارير مفصولة وجاهزة للتحويل لاحقاً إلى موديولز، مع تابات لكل نوع تقرير.';
    $reportStatusLabel = function (?string $value): string {
        if (!$value) return '-';
        $translated = __('app.reports.status.value_labels.' . $value);
        return $translated !== 'app.reports.status.value_labels.' . $value ? $translated : $value;
    };
    $reportDecisionLabel = function (?string $value): string {
        if (!$value) return '-';
        $translated = __('app.reports.status.decision_labels.' . $value);
        return $translated !== 'app.reports.status.decision_labels.' . $value ? $translated : $value;
    };
@endphp

@section('page_title', $title)
@section('page_breadcrumb', $title)
@section('enable_header_search', '1')

@section('content')
    <div class="card stretch stretch-full mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <h1 class="h4 mb-2">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('role.super_admin.site_settings.index', ['report_year' => $reportYear, 'report_month' => $reportMonth]) }}">
                <i class="fas fa-gear me-1"></i> إعدادات الموقع والكاش
            </a>
        </div>
    </div>

    <div class="card stretch stretch-full mb-4">
        <div class="card-body">
            <ul class="nav nav-tabs flex-wrap" role="tablist">
                @foreach($availableTabs as $tabKey => $tabLabel)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === $tabKey ? 'active' : '' }}" href="{{ route('role.super_admin.reports', array_merge(request()->except('tab'), ['tab' => $tabKey])) }}">
                            {{ $tabLabel }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    @if($activeTab === 'relations')
        @include('pages.admin.reports.partials.relations', ['relationsReport' => $reportData['relations']])
    @elseif($activeTab === 'operations')
        @include('pages.admin.reports.partials.operations')
    @elseif($activeTab === 'enterprise')
        @include('pages.admin.reports.partials.enterprise')
    @else
        @include('pages.admin.reports.partials.overview')
    @endif
@endsection

@push('scripts')
    @if($activeTab === 'operations')
        <script type="module">
            import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
            mermaid.initialize({ startOnLoad: true });
        </script>
    @endif
@endpush

@push('styles')
    @if($activeTab === 'enterprise')
        <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/enterprise-dashboard.css') }}">
    @endif
@endpush
