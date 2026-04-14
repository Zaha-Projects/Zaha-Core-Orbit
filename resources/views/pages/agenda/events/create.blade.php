@extends('layouts.app')

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
