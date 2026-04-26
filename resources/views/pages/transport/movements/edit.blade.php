@extends('layouts.app')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">{{ __('app.roles.transport.movements.edit_title') }}</h1>
            <form method="POST" action="{{ route('role.transport.movements.update', $movementDay) }}">
                @method('PUT')
                @include('pages.transport.movements.form', ['submitLabel' => __('app.roles.transport.movements.actions.update')])
            </form>
        </div>
    </div>
@endsection
