@extends('layouts.app')
@section('content')
    @include('pages.events.ramadan._form', ['formAction' => route('events.ramadan.iftars.update', $ramadanIftar), 'formMethod' => 'PUT'])
@endsection
