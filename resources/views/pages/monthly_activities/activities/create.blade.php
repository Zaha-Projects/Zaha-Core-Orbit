@extends('layouts.app')

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
