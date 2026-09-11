@php
    $value = fn (string $field, $default = '') => old($field, data_get($ramadanIftar, $field, $default));
    $collections = [
        'target_groups' => old('target_groups', $ramadanIftar ? $ramadanIftar->targetGroupSelections->toArray() : [[]]),
        'meals' => old('meals', $ramadanIftar ? $ramadanIftar->meals->map(fn ($meal) => array_merge($meal->toArray(), ['items' => $meal->items->toArray()]))->toArray() : [['items' => [[]]]]),
        'gifts' => old('gifts', $ramadanIftar ? $ramadanIftar->gifts->toArray() : [[]]),
        'program_segments' => old('program_segments', $ramadanIftar ? $ramadanIftar->programSegments->toArray() : [[]]),
        'execution_teams' => old('execution_teams', $ramadanIftar ? $ramadanIftar->executionTeams->map(fn ($team) => array_merge($team->toArray(), ['members' => $team->members->toArray()]))->toArray() : [['members' => [[]]]]),
        'volunteer_requirements' => old('volunteer_requirements', $ramadanIftar ? $ramadanIftar->volunteerRequirements->toArray() : [[]]),
        'supplies' => old('supplies', $ramadanIftar ? $ramadanIftar->supplies->toArray() : [[]]),
        'execution_needs' => old('execution_needs', $ramadanIftar ? $ramadanIftar->executionNeeds->toArray() : []),
    ];
@endphp
<div class="container py-4">
    <h1 class="h3 mb-3">{{ $ramadanIftar ? 'Edit Ramadan Iftar plan' : 'Create Ramadan Iftar plan' }}</h1>
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $formAction }}" id="ramadan-planning-form">
        @csrf @if($formMethod !== 'POST') @method($formMethod) @endif
        <div class="card mb-3"><div class="card-body row g-3">
            <div class="col-md-6"><label>Branch</label><select class="form-select" name="branch_id" required>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($value('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label>Agenda event</label><select class="form-select" name="agenda_event_id"><option value="">—</option>@foreach($agendaEvents as $event)<option value="{{ $event->id }}" @selected($value('agenda_event_id') == $event->id)>{{ $event->event_name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label>Title</label><input class="form-control" name="title" value="{{ $value('title') }}" required></div>
            <div class="col-md-6"><label>Relations officer</label><select class="form-select" name="relations_officer_id" required>@foreach($users as $user)<option value="{{ $user->id }}" @selected($value('relations_officer_id') == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label>Planned date</label><input type="date" class="form-control" name="planned_date" value="{{ old('planned_date', optional($ramadanIftar?->planned_date)->format('Y-m-d')) }}" required></div>
            <div class="col-md-4"><label>From</label><input type="time" class="form-control" name="time_from" value="{{ $value('time_from') }}"></div>
            <div class="col-md-4"><label>To</label><input type="time" class="form-control" name="time_to" value="{{ $value('time_to') }}"></div>
            <div class="col-md-4"><label>Location type</label><select class="form-select" name="location_type">@foreach($locationTypes as $type)<option @selected($value('location_type') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-4"><label>Host type</label><select class="form-select" name="host_type">@foreach($hostTypes as $type)<option @selected($value('host_type') === $type)>{{ $type }}</option>@endforeach</select></div>
            <div class="col-md-4"><label>Location name</label><input class="form-control" name="location_name" value="{{ $value('location_name') }}"></div>
            <div class="col-md-6"><label>Community organization</label><select class="form-select" name="community_organization_id"><option value="">—</option>@foreach($communityOrganizations as $item)<option value="{{ $item->id }}" @selected($value('community_organization_id') == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label>Local community</label><select class="form-select" name="local_community_id"><option value="">—</option>@foreach($localCommunities as $item)<option value="{{ $item->id }}" @selected($value('local_community_id') == $item->id)>{{ $item->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label>Mobilization method</label><select class="form-select" name="mobilization_method_id"><option value="">—</option>@foreach($mobilizationMethods as $item)<option value="{{ $item->id }}" @selected($value('mobilization_method_id') == $item->id)>{{ $item->name_ar }}</option>@endforeach</select></div>
            <div class="col-md-6"><label>Other mobilization</label><input class="form-control" name="mobilization_method_other" value="{{ $value('mobilization_method_other') }}"></div>
            @foreach(['address','description','google_maps_url','contact_name','contact_phone','supporting_entity_name'] as $field)<div class="col-md-6"><label>{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $field)) }}</label><input class="form-control" name="{{ $field }}" value="{{ $value($field) }}"></div>@endforeach
        </div></div>

        <div class="card mb-3"><div class="card-header"><strong>Execution needs</strong></div><div class="card-body row g-3">
            @foreach($executionNeedTypes as $needType)
                @php $selectedNeed = collect($collections['execution_needs'])->firstWhere('execution_need_type_id', $needType->id); @endphp
                <div class="col-md-6 border rounded p-3">
                    @if($selectedNeed)<input type="hidden" name="execution_needs[{{ $needType->id }}][id]" value="{{ $selectedNeed['id'] ?? '' }}">@endif
                    <input type="hidden" name="execution_needs[{{ $needType->id }}][execution_need_type_id]" value="{{ $needType->id }}">
                    <input type="hidden" name="execution_needs[{{ $needType->id }}][is_required]" value="0">
                    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="execution_needs[{{ $needType->id }}][is_required]" value="1" id="execution-need-{{ $needType->id }}" @if((bool)($selectedNeed['is_required'] ?? false)) checked @endif><label class="form-check-label" for="execution-need-{{ $needType->id }}">{{ $needType->name }}</label></div>
                    <textarea class="form-control" name="execution_needs[{{ $needType->id }}][planned_details]" rows="2" placeholder="Planning details">{{ $selectedNeed['planned_details'] ?? '' }}</textarea>
                </div>
            @endforeach
        </div></div>

        @php $rows = $collections['target_groups']; @endphp
        <div class="card mb-3" data-repeat="target_groups"><div class="card-header d-flex justify-content-between"><strong>Target groups</strong><button type="button" class="btn btn-sm btn-outline-primary add-row">Add</button></div><div class="card-body">
            @foreach($rows as $i => $row)<div class="planning-row border rounded p-2 mb-2"><input type="hidden" name="target_groups[{{$i}}][id]" value="{{ $row['id'] ?? '' }}"><div class="row g-2"><div class="col-md-3"><select class="form-select" name="target_groups[{{$i}}][target_group_id]">@foreach($targetGroups as $item)<option value="{{$item->id}}" @selected(($row['target_group_id'] ?? null)==$item->id)>{{$item->name}}</option>@endforeach</select></div><div class="col-md-2"><select class="form-select" name="target_groups[{{$i}}][beneficiary_segment_id]"><option value="">—</option>@foreach($beneficiarySegments as $item)<option value="{{$item->id}}" @selected(($row['beneficiary_segment_id'] ?? null)==$item->id)>{{$item->name_ar}}</option>@endforeach</select></div><div class="col-md-2"><input class="form-control" type="number" min="0" name="target_groups[{{$i}}][planned_count]" value="{{$row['planned_count'] ?? 0}}"></div><div class="col-md-2"><input class="form-control" name="target_groups[{{$i}}][target_group_custom_text]" value="{{$row['target_group_custom_text'] ?? ''}}" placeholder="Other group"></div><div class="col-md-2"><input class="form-control" name="target_groups[{{$i}}][segment_custom_text]" value="{{$row['segment_custom_text'] ?? ''}}" placeholder="Other segment"></div><button type="button" class="btn btn-outline-danger col-auto remove-row">×</button></div></div>@endforeach
        </div></div>

        @include('pages.events.ramadan._planning_collections', compact('collections','mealItemTypes','users','beneficiarySegments'))
        <button class="btn btn-primary" type="submit">Save draft</button>
    </form>
</div>
<script>
document.addEventListener('click', event => {
    if (event.target.matches('.remove-row')) event.target.closest('.planning-row').remove();
    if (event.target.matches('.add-row')) {
        const card = event.target.closest('[data-repeat]');
        const source = card.querySelector('.planning-row');
        if (!source) return;
        const clone = source.cloneNode(true);
        const index = Date.now();
        clone.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
            if (input.type !== 'hidden' && input.tagName !== 'SELECT') input.value = '';
            if (input.type === 'hidden') input.value = '';
        });
        card.querySelector('.card-body').appendChild(clone);
    }
});
</script>
