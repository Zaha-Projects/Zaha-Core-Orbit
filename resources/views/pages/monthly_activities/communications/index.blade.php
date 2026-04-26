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

@section('content')
<div class="container">
    <h1>Communications Requests</h1>
    @foreach($requests as $request)
        <div>
            <a href="{{ route('role.relations.activities.edit', $request->event_id) }}">{{ $request->event->title ?? 'Event #'.$request->event_id }}</a>
            <form method="POST" action="{{ route('role.programs.communications_requests.update', $request) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <select name="status">
                    @foreach(['pending','in_progress','approved','rejected','completed'] as $status)
                        <option value="{{ $status }}" @selected($request->status === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <input type="text" name="notes" value="{{ $request->notes }}" />
                <input type="file" name="media_files[]" multiple />
                <button type="submit">Save</button>
            </form>
        </div>
    @endforeach
    {{ $requests->links() }}
</div>
@endsection
