@extends('layouts.new-theme-dashboard')

@section('theme_sidebar_links')
    <li class="side-item {{ request()->routeIs('role.relations.agenda.*') ? 'selected' : '' }}">
        <a href="{{ route('role.relations.agenda.index') }}"><i class="fas fa-calendar-days"></i><span>{{ __('app.roles.relations.agenda.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.relations.approvals.*') ? 'selected' : '' }}">
        <a href="{{ route('role.relations.approvals.index') }}"><i class="fas fa-square-check"></i><span>{{ __('app.roles.relations.approvals.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.relations.activities.*') ? 'selected' : '' }}">
        <a href="{{ route('role.relations.activities.index') }}"><i class="fas fa-layer-group"></i><span>{{ __('app.roles.programs.monthly_activities.title') }}</span></a>
    </li>
@endsection

@php
    $title = __('app.roles.relations.agenda.create_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp

@section('content')
    @include('pages.agenda.events._form', [
        'formAction' => route('role.relations.agenda.store'),
        'formMethod' => 'POST',
        'submitLabel' => __('app.roles.relations.agenda.actions.save'),
        'title' => $title,
        'subtitle' => $subtitle,
        'branchParticipations' => [],
        'headerBadge' => null,
    ])
@endsection
