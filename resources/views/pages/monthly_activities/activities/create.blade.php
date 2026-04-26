@extends('layouts.new-theme-dashboard')

@section('theme_sidebar_links')
    <li class="side-item {{ request()->routeIs('role.relations.agenda.*') ? 'selected' : '' }}">
        <a href="{{ route('role.relations.agenda.index') }}"><i class="fas fa-calendar-days"></i><span>{{ __('app.roles.relations.agenda.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.relations.activities.*') && request('scope') !== 'all_branches' ? 'selected' : '' }}">
        <a href="{{ route('role.relations.activities.index') }}"><i class="fas fa-layer-group"></i><span>{{ __('app.roles.programs.monthly_activities.title') }}</span></a>
    </li>
    @can('monthly_activities.view_other_branches')
        <li class="side-item {{ request()->routeIs('role.relations.activities.*') && request('scope') === 'all_branches' ? 'selected' : '' }}">
            <a href="{{ route('role.relations.activities.index', ['scope' => 'all_branches']) }}"><i class="fas fa-table-cells-large"></i><span>الخطط الشهرية للفروع الأخرى</span></a>
        </li>
    @endcan
    <li class="side-item {{ request()->routeIs('role.programs.approvals.*') ? 'selected' : '' }}">
        <a href="{{ route('role.programs.approvals.index') }}"><i class="fas fa-square-check"></i><span>{{ __('app.roles.programs.monthly_activities.approvals.title') }}</span></a>
    </li>
@endsection

@php
    $title = __('app.roles.programs.monthly_activities.create_title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
@endphp

@section('content')
    @include('pages.monthly_activities.activities._form', [
        'formAction' => route('role.relations.activities.store'),
        'formMethod' => 'POST',
        'submitLabel' => __('app.roles.programs.monthly_activities.actions.create'),
        'title' => $title,
        'subtitle' => $subtitle,
    ])
@endsection
