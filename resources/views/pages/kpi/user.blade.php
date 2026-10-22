@extends('layouts.app')

@section('title', 'أداء '.$performance['user']->name)

@section('content')
<div class="kpi-dashboard" dir="rtl">
    @include('pages.kpi.partials.header', [
        'title' => 'أداء '.$performance['user']->name,
        'subtitle' => implode('، ', $performance['roles']).($performance['branch'] ? ' — '.$performance['branch'] : ''),
        'icon' => 'fa-user-check',
    ])
    @include('pages.kpi.partials.section-nav')
</div>
@include('pages.kpi.partials.user-performance')
@endsection
