@extends('layouts.app')

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
