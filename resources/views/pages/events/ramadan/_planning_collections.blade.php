@php
$definitions = [
 'gifts' => ['description','planned_quantity','has_supporting_entity','supporting_entity_name','unit_value'],
 'program_segments' => ['name','starts_at','ends_at','duration_minutes','sort_order','executor_user_id','external_executor_name'],
 'volunteer_requirements' => ['beneficiary_segment_id','gender','planned_count','tasks_summary'],
 'supplies' => ['item_name','planned_quantity','provider_type','provider_name','estimated_value','notes'],
];
@endphp
@foreach($definitions as $collection => $fields)
<div class="card mb-3" data-repeat="{{$collection}}"><div class="card-header d-flex justify-content-between"><strong>{{ str($collection)->replace('_',' ')->title() }}</strong><button type="button" class="btn btn-sm btn-outline-primary add-row">Add</button></div><div class="card-body">
@foreach($collections[$collection] as $i => $row)
<div class="planning-row row g-2 border rounded p-2 mb-2"><input type="hidden" name="{{$collection}}[{{$i}}][id]" value="{{$row['id'] ?? ''}}">
@foreach($fields as $field)<div class="col-md-3">
@if(in_array($field,['planned_quantity','planned_count','duration_minutes','sort_order']))<input type="number" min="0" class="form-control" name="{{$collection}}[{{$i}}][{{$field}}]" value="{{$row[$field] ?? 0}}" placeholder="{{str($field)->replace('_',' ')}}">
@elseif($field === 'has_supporting_entity')<select class="form-select" name="{{$collection}}[{{$i}}][{{$field}}]"><option value="0">No supporter</option><option value="1" @selected($row[$field] ?? false)>Supported</option></select>
@elseif($field === 'executor_user_id')<select class="form-select" name="{{$collection}}[{{$i}}][{{$field}}]"><option value="">External/none</option>@foreach($users as $user)<option value="{{$user->id}}" @selected(($row[$field]??null)==$user->id)>{{$user->name}}</option>@endforeach</select>
@elseif($field === 'beneficiary_segment_id')<select class="form-select" name="{{$collection}}[{{$i}}][{{$field}}]"><option value="">—</option>@foreach($beneficiarySegments as $segment)<option value="{{$segment->id}}" @selected(($row[$field]??null)==$segment->id)>{{$segment->name_ar}}</option>@endforeach</select>
@else<input class="form-control" name="{{$collection}}[{{$i}}][{{$field}}]" value="{{$row[$field] ?? ''}}" placeholder="{{str($field)->replace('_',' ')}}">@endif
</div>@endforeach<button type="button" class="btn btn-outline-danger col-auto remove-row">×</button></div>
@endforeach
</div></div>
@endforeach

<div class="card mb-3" data-repeat="meals"><div class="card-header d-flex justify-content-between"><strong>Meals and items</strong><button type="button" class="btn btn-sm btn-outline-primary add-row">Add meal</button></div><div class="card-body">
@foreach($collections['meals'] as $i => $meal)<div class="planning-row border rounded p-2 mb-2"><input type="hidden" name="meals[{{$i}}][id]" value="{{$meal['id']??''}}"><div class="row g-2"><div class="col"><input class="form-control" name="meals[{{$i}}][description]" value="{{$meal['description']??''}}" placeholder="Description"></div><div class="col"><input type="number" min="0" class="form-control" name="meals[{{$i}}][planned_quantity]" value="{{$meal['planned_quantity']??0}}"></div><div class="col"><input class="form-control" name="meals[{{$i}}][estimated_value]" value="{{$meal['estimated_value']??''}}" placeholder="Estimated value"></div><button type="button" class="btn btn-outline-danger col-auto remove-row">×</button></div>
@foreach($meal['items']??[] as $j=>$item)<div class="row g-2 mt-1"><input type="hidden" name="meals[{{$i}}][items][{{$j}}][id]" value="{{$item['id']??''}}"><div class="col"><input class="form-control" name="meals[{{$i}}][items][{{$j}}][name]" value="{{$item['name']??''}}"></div><div class="col"><select class="form-select" name="meals[{{$i}}][items][{{$j}}][item_type]">@foreach($mealItemTypes as $type)<option @selected(($item['item_type']??null)===$type)>{{$type}}</option>@endforeach</select></div><div class="col"><input type="number" class="form-control" name="meals[{{$i}}][items][{{$j}}][quantity]" value="{{$item['quantity']??''}}"></div></div>@endforeach
</div>@endforeach</div></div>

<div class="card mb-3" data-repeat="execution_teams"><div class="card-header d-flex justify-content-between"><strong>Execution teams</strong><button type="button" class="btn btn-sm btn-outline-primary add-row">Add team</button></div><div class="card-body">
@foreach($collections['execution_teams'] as $i=>$team)<div class="planning-row border rounded p-2 mb-2"><input type="hidden" name="execution_teams[{{$i}}][id]" value="{{$team['id']??''}}"><div class="row g-2"><div class="col"><input class="form-control" name="execution_teams[{{$i}}][name]" value="{{$team['name']??''}}"></div><div class="col"><select class="form-select" name="execution_teams[{{$i}}][leader_user_id]"><option value="">—</option>@foreach($users as $user)<option value="{{$user->id}}" @selected(($team['leader_user_id']??null)==$user->id)>{{$user->name}}</option>@endforeach</select></div><div class="col"><input type="number" min="0" class="form-control" name="execution_teams[{{$i}}][planned_members_count]" value="{{$team['planned_members_count']??''}}"></div><button type="button" class="btn btn-outline-danger col-auto remove-row">×</button></div>
@foreach($team['members']??[] as $j=>$member)<div class="row g-2 mt-1"><input type="hidden" name="execution_teams[{{$i}}][members][{{$j}}][id]" value="{{$member['id']??''}}"><div class="col"><select class="form-select" name="execution_teams[{{$i}}][members][{{$j}}][user_id]"><option value="">External</option>@foreach($users as $user)<option value="{{$user->id}}" @selected(($member['user_id']??null)==$user->id)>{{$user->name}}</option>@endforeach</select></div><div class="col"><input class="form-control" name="execution_teams[{{$i}}][members][{{$j}}][member_name]" value="{{$member['member_name']??''}}"></div><div class="col"><input class="form-control" name="execution_teams[{{$i}}][members][{{$j}}][task_description]" value="{{$member['task_description']??''}}"></div></div>@endforeach
</div>@endforeach</div></div>
