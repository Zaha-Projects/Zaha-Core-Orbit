@extends('layouts.app')
@section('content')
    @include('pages.events.ramadan._form', ['formAction' => route('events.ramadan.iftars.update', $ramadanIftar), 'formMethod' => 'PUT'])
    <div class="container pb-4"><form method="POST" action="{{ route('events.ramadan.iftars.submit', $ramadanIftar) }}">@csrf<button class="btn btn-success">{{ __('ramadan_iftars.actions.submit') }}</button></form></div>
@endsection
