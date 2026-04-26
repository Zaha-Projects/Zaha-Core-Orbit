@extends('layouts.app')

@section('page_title', __('app.roles.transport.requests.title'))
@section('page_breadcrumb', __('app.roles.transport.requests.title'))

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ __('app.roles.transport.requests.title') }}</h1>
            <p class="text-muted mb-0">{{ __('app.roles.transport.requests.subtitle') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.requests.create_title') }}</h2>
            <form method="POST" action="{{ route('role.transport.requests.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.requests.fields.request_date') }}</label>
                    <input class="form-control" type="date" name="request_date" value="{{ old('request_date') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.requests.fields.day_name') }}</label>
                    <input class="form-control" name="day_name" value="{{ old('day_name') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.requests.fields.destination') }}</label>
                    <input class="form-control" name="destination" value="{{ old('destination') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.requests.fields.accompanying_team') }}</label>
                    <input class="form-control" name="accompanying_team" value="{{ old('accompanying_team') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.requests.fields.departure_time') }}</label>
                    <input class="form-control" type="time" name="departure_time" value="{{ old('departure_time') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.requests.fields.return_time') }}</label>
                    <input class="form-control" type="time" name="return_time" value="{{ old('return_time') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.transport.requests.fields.general_notes') }}</label>
                    <textarea class="form-control" rows="2" name="general_notes">{{ old('general_notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">{{ __('app.roles.transport.requests.actions.submit_request') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.requests.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('app.roles.transport.requests.fields.requester') }}</th>
                            <th>{{ __('app.roles.transport.requests.fields.branch') }}</th>
                            <th>{{ __('app.roles.transport.requests.fields.date') }}</th>
                            <th>{{ __('app.roles.transport.requests.fields.destination') }}</th>
                            <th>{{ __('app.roles.transport.requests.fields.status') }}</th>
                            <th>{{ __('app.roles.transport.requests.fields.driver') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $transportRequest)
                            @php($trip = $transportRequest->trips->first())
                            <tr>
                                <td>{{ $transportRequest->id }}</td>
                                <td>{{ $transportRequest->requester?->name ?? '-' }}</td>
                                <td>{{ $transportRequest->branch?->name ?? '-' }}</td>
                                <td>{{ optional($transportRequest->request_date)->format('Y-m-d') }}</td>
                                <td>{{ $trip?->destination ?? '-' }}</td>
                                <td>
                                    @php($requestStatus = __('app.roles.transport.requests.statuses.' . $transportRequest->status))
                                    {{ $requestStatus !== 'app.roles.transport.requests.statuses.' . $transportRequest->status ? $requestStatus : $transportRequest->status }}
                                </td>
                                <td>{{ $transportRequest->driver?->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td colspan="7">
                                    <div class="row g-3">
                                        @if ($isTransportOfficer)
                                            <div class="col-12 col-lg-8">
                                                <form method="POST" action="{{ route('role.transport.requests.process', $transportRequest) }}" class="row g-2 border rounded p-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.status') }}</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="in_review" @selected($transportRequest->status === 'in_review')>{{ __('app.roles.transport.requests.statuses.in_review') }}</option>
                                                            <option value="approved" @selected($transportRequest->status === 'approved')>{{ __('app.roles.transport.requests.statuses.approved') }}</option>
                                                            <option value="rejected" @selected($transportRequest->status === 'rejected')>{{ __('app.roles.transport.requests.statuses.rejected') }}</option>
                                                            <option value="closed" @selected($transportRequest->status === 'closed')>{{ __('app.roles.transport.requests.statuses.closed') }}</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.assign_driver') }}</label>
                                                        <select class="form-select" name="driver_id">
                                                            <option value="">{{ __('app.roles.transport.requests.fields.no_driver') }}</option>
                                                            @foreach ($drivers as $driver)
                                                                <option value="{{ $driver->id }}" @selected((int) $transportRequest->driver_id === (int) $driver->id)>{{ $driver->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.destination') }}</label>
                                                        <input class="form-control" name="destination" value="{{ $trip?->destination }}">
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.accompanying_team') }}</label>
                                                        <input class="form-control" name="accompanying_team" value="{{ $trip?->accompanying_team }}">
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.departure_short') }}</label>
                                                        <input class="form-control" type="time" name="departure_time" value="{{ $trip?->departure_time }}">
                                                    </div>
                                                    <div class="col-12 col-md-4">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.return_short') }}</label>
                                                        <input class="form-control" type="time" name="return_time" value="{{ $trip?->return_time }}">
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.officer_notes') }}</label>
                                                        <textarea class="form-control" rows="2" name="movement_officer_notes">{{ $transportRequest->movement_officer_notes }}</textarea>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.fields.action_comment') }}</label>
                                                        <textarea class="form-control" rows="2" name="comment"></textarea>
                                                    </div>
                                                    <div class="col-12 d-flex justify-content-end">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">{{ __('app.roles.transport.requests.actions.save_officer_decision') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif

                                        @if (auth()->id() === $transportRequest->requester_id && $transportRequest->status === 'closed')
                                            <div class="col-12 col-lg-4">
                                                <form method="POST" action="{{ route('role.transport.requests.feedback', $transportRequest) }}" class="border rounded p-3">
                                                    @csrf
                                                    @method('PATCH')
                                                    <h3 class="h6">{{ __('app.roles.transport.requests.feedback.title') }}</h3>
                                                    <div class="mb-2">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.feedback.punctuality') }}</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="punctuality_score" value="{{ $transportRequest->feedback?->punctuality_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.feedback.cleanliness') }}</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="cleanliness_score" value="{{ $transportRequest->feedback?->cleanliness_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.feedback.driver_behavior') }}</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="driver_behavior_score" value="{{ $transportRequest->feedback?->driver_behavior_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.feedback.overall') }}</label>
                                                        <input type="number" min="1" max="5" class="form-control" name="overall_score" value="{{ $transportRequest->feedback?->overall_score }}">
                                                    </div>
                                                    <div class="mb-2">
                                                        <label class="form-label">{{ __('app.roles.transport.requests.feedback.comment') }}</label>
                                                        <textarea class="form-control" rows="2" name="comment">{{ $transportRequest->feedback?->comment }}</textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-success btn-sm">{{ __('app.roles.transport.requests.actions.save_feedback') }}</button>
                                                </form>
                                            </div>
                                        @endif

                                        <div class="col-12">
                                            <div class="small text-muted">
                                                {{ __('app.roles.transport.requests.action_log.title') }}
                                                @forelse ($transportRequest->actions as $action)
                                                    <span class="badge bg-light text-dark border me-1 mb-1">
                                                        {{ $action->action_type }} {{ __('app.roles.transport.requests.action_log.by') }} {{ $action->actor?->name ?? __('app.roles.transport.requests.action_log.unknown_user') }}
                                                    </span>
                                                @empty
                                                    {{ __('app.roles.transport.requests.action_log.none') }}
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">{{ __('app.roles.transport.requests.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
