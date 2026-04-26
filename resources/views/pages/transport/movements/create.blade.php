@extends('layouts.new-theme-dashboard')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">{{ __('app.roles.transport.movements.create_title') }}</h1>
            <form method="POST" action="{{ route('role.transport.movements.store') }}">
                @include('pages.transport.movements.form', ['submitLabel' => __('app.roles.transport.movements.actions.create'), 'movementDay' => null])
            </form>
        </div>
    </div>
@endsection
