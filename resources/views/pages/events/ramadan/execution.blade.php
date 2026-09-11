@extends('layouts.app')
@section('content')
@php
    $attendees = $ramadanIftar->attendees->concat([new \App\Modules\Events\Models\RamadanIftarAttendee]);
@endphp
<div class="container py-4">
    <div class="d-flex justify-content-between"><h1 class="h3">Ramadan Iftar execution</h1><a href="{{ route('events.ramadan.iftars.show', $ramadanIftar) }}">Back to Iftar</a></div>
    <p class="text-muted">Sensitive attendance and actual results for {{ $ramadanIftar->title }} · {{ optional($ramadanIftar->branch)->name }}</p>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="card mb-3"><div class="card-header">Approved plan baseline</div><div class="card-body row">
        <div class="col-md-4"><strong>Planned date</strong><br>{{ optional($ramadanIftar->planned_date)->format('Y-m-d') }}</div>
        <div class="col-md-4"><strong>Expected attendance</strong><br>{{ $ramadanIftar->expected_attendance }}</div>
        <div class="col-md-4"><strong>Planned meals</strong><br>{{ $ramadanIftar->planned_meals_count }}</div>
    </div></div>

    @if($ramadanIftar->execution_status === \App\Modules\Events\Models\RamadanIftar::EXECUTION_STATUS_PLANNED)
        <form method="POST" action="{{ route('events.ramadan.iftars.execution.start', $ramadanIftar) }}">@csrf<button class="btn btn-primary">Start execution</button></form>
    @else
    <form method="POST" action="{{ route('events.ramadan.iftars.execution.update', $ramadanIftar) }}">@csrf @method('PUT')
        <div class="card my-3"><div class="card-header">Actual event data</div><div class="card-body">
            <label>Actual date</label><input class="form-control" type="date" name="actual_date" value="{{ old('actual_date', optional($ramadanIftar->actual_date)->format('Y-m-d')) }}">
            <small class="text-muted">Attendance and meal totals are derived from the detailed rows below.</small>
        </div></div>

        <div class="card mb-3"><div class="card-header">Attendance roster</div><div class="card-body">
        @foreach($attendees as $i => $attendee)<div class="row g-2 border rounded p-2 mb-2">
            @if($attendee->exists)<input type="hidden" name="attendees[{{ $i }}][id]" value="{{ $attendee->id }}">@endif
            <div class="col-md-3"><input class="form-control" name="attendees[{{ $i }}][full_name]" value="{{ old("attendees.$i.full_name", $attendee->full_name) }}" placeholder="Full name"></div>
            <div class="col-md-2"><input class="form-control" name="attendees[{{ $i }}][phone]" value="{{ old("attendees.$i.phone", $attendee->phone) }}" placeholder="Phone"></div>
            <div class="col-md-1"><input class="form-control" type="number" min="0" max="150" name="attendees[{{ $i }}][age]" value="{{ old("attendees.$i.age", $attendee->age) }}" placeholder="Age"></div>
            <div class="col-md-2"><select class="form-select" name="attendees[{{ $i }}][target_group_id]"><option value="">Target group</option>@foreach($targetGroups as $group)<option value="{{ $group->id }}" @if($attendee->target_group_id == $group->id) selected @endif>{{ $group->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><select class="form-select" name="attendees[{{ $i }}][beneficiary_segment_id]"><option value="">Segment</option>@foreach($beneficiarySegments as $segment)<option value="{{ $segment->id }}" @if($attendee->beneficiary_segment_id == $segment->id) selected @endif>{{ $segment->name_ar }}</option>@endforeach</select></div>
            <div class="col-md-2"><input type="hidden" name="attendees[{{ $i }}][attended]" value="0"><label><input type="checkbox" name="attendees[{{ $i }}][attended]" value="1" @if(old("attendees.$i.attended", $attendee->attended)) checked @endif> Checked in</label></div>
            <div class="col-md-3"><input class="form-control" name="attendees[{{ $i }}][notes]" value="{{ old("attendees.$i.notes", $attendee->notes) }}" placeholder="Notes"></div>
            <div class="col-md-1">@if($attendee->exists)<label><input type="checkbox" name="attendees[{{ $i }}][_delete]" value="1"> Remove</label>@endif</div>
        </div>@endforeach
        </div></div>

        <div class="card mb-3"><div class="card-header">Meals and gifts — planned / actual</div><div class="card-body">
            @foreach($ramadanIftar->meals as $i => $meal)<input type="hidden" name="meals[{{ $i }}][id]" value="{{ $meal->id }}"><div class="row g-2 mb-2"><div class="col-md-4">{{ $meal->description }} (plan {{ $meal->planned_quantity }})</div><div class="col-md-2"><input class="form-control" type="number" min="0" name="meals[{{ $i }}][actual_quantity]" value="{{ $meal->actual_quantity }}" placeholder="Actual"></div><div class="col-md-2"><input class="form-control" type="number" min="1" max="5" name="meals[{{ $i }}][rating]" value="{{ $meal->rating }}" placeholder="Rating 1–5"></div><div class="col-md-4"><input class="form-control" name="meals[{{ $i }}][rating_notes]" value="{{ $meal->rating_notes }}" placeholder="Rating notes"></div></div>@endforeach
            @foreach($ramadanIftar->gifts as $i => $gift)<input type="hidden" name="gifts[{{ $i }}][id]" value="{{ $gift->id }}"><div class="row g-2 mb-2"><div class="col-md-6">{{ $gift->description }} (plan {{ $gift->planned_quantity }})</div><div class="col-md-3"><input class="form-control" type="number" min="0" name="gifts[{{ $i }}][actual_quantity]" value="{{ $gift->actual_quantity }}" placeholder="Actual gifts"></div></div>@endforeach
        </div></div>

        <div class="card mb-3"><div class="card-header">Program results</div><div class="card-body">@foreach($ramadanIftar->programSegments as $i => $segment)<input type="hidden" name="program_segments[{{ $i }}][id]" value="{{ $segment->id }}"><div class="row g-2 mb-2"><div class="col-md-4">{{ $segment->name }}</div><div class="col-md-3"><select class="form-select" name="program_segments[{{ $i }}][execution_status]">@foreach(\App\Modules\Events\Models\RamadanIftarProgramSegment::statuses() as $status)<option value="{{ $status }}" @if($segment->execution_status === $status) selected @endif>{{ $status }}</option>@endforeach</select></div><div class="col-md-5"><input class="form-control" name="program_segments[{{ $i }}][actual_notes]" value="{{ $segment->actual_notes }}" placeholder="Actual notes"></div></div>@endforeach</div></div>

        <div class="card mb-3"><div class="card-header">Teams and member tasks</div><div class="card-body">@foreach($ramadanIftar->executionTeams as $i => $team)<input type="hidden" name="execution_teams[{{ $i }}][id]" value="{{ $team->id }}"><div class="border rounded p-2 mb-2"><div class="row"><div class="col-md-6"><strong>{{ $team->name }}</strong> (plan {{ $team->planned_members_count ?: '—' }})</div><div class="col-md-3"><input class="form-control" type="number" min="0" name="execution_teams[{{ $i }}][actual_members_count]" value="{{ $team->actual_members_count }}" placeholder="Actual members"></div></div>@foreach($team->members as $j => $member)<input type="hidden" name="execution_teams[{{ $i }}][members][{{ $j }}][id]" value="{{ $member->id }}"><div class="row g-2 mt-2"><div class="col-md-4">{{ $member->member_name ?: optional($member->user)->name }}</div><div class="col-md-3"><select class="form-select" name="execution_teams[{{ $i }}][members][{{ $j }}][task_completed]"><option value="">Not evaluated</option><option value="1" @if($member->task_completed === true) selected @endif>Completed</option><option value="0" @if($member->task_completed === false) selected @endif>Not completed</option></select></div><div class="col-md-5"><input class="form-control" name="execution_teams[{{ $i }}][members][{{ $j }}][actual_task_note]" value="{{ $member->actual_task_note }}" placeholder="Actual task note"></div></div>@endforeach</div>@endforeach</div></div>

        <div class="card mb-3"><div class="card-header">Volunteers and supplies</div><div class="card-body">@foreach($ramadanIftar->volunteerRequirements as $i => $row)<input type="hidden" name="volunteer_requirements[{{ $i }}][id]" value="{{ $row->id }}"><div class="row g-2 mb-2"><div class="col-md-6">Volunteers planned: {{ $row->planned_count }}</div><div class="col-md-3"><input class="form-control" type="number" min="0" name="volunteer_requirements[{{ $i }}][actual_count]" value="{{ $row->actual_count }}" placeholder="Actual"></div></div>@endforeach @foreach($ramadanIftar->supplies as $i => $row)<input type="hidden" name="supplies[{{ $i }}][id]" value="{{ $row->id }}"><div class="row g-2 mb-2"><div class="col-md-4">{{ $row->item_name }} (plan {{ $row->planned_quantity }})</div><div class="col-md-3"><input class="form-control" type="number" min="0" name="supplies[{{ $i }}][actual_quantity]" value="{{ $row->actual_quantity }}" placeholder="Actual"></div><div class="col-md-3"><select class="form-select" name="supplies[{{ $i }}][is_available]"><option value="">Not assessed</option><option value="1" @if($row->is_available === true) selected @endif>Available</option><option value="0" @if($row->is_available === false) selected @endif>Unavailable</option></select></div></div>@endforeach</div></div>

        <div class="card mb-3"><div class="card-header">Canonical Execution Needs</div><div class="card-body">@foreach($ramadanIftar->executionNeeds as $i => $need)<input type="hidden" name="execution_needs[{{ $i }}][id]" value="{{ $need->id }}"><div class="row g-2 mb-2"><div class="col-md-4">{{ optional($need->executionNeedType)->name }} — {{ $need->is_required ? 'Required' : 'Not required' }}<br><small>{{ $need->planned_details }}</small></div><div class="col-md-3"><select class="form-select" name="execution_needs[{{ $i }}][status]"><option value="pending" @if($need->status === 'pending') selected @endif>Pending</option><option value="completed" @if($need->status === 'completed') selected @endif>Completed</option></select></div><div class="col-md-5"><input class="form-control" name="execution_needs[{{ $i }}][actual_details]" value="{{ $need->actual_details }}" placeholder="Actual result"></div></div>@endforeach</div></div>
        <button class="btn btn-success">Save actual execution data</button>
    </form>
    @endif
</div>
@endsection
