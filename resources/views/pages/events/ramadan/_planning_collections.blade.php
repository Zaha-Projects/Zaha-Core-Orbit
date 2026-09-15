@php
$definitions = [
    'program_segments' => ['name','starts_at','ends_at','duration_minutes','sort_order','executor_user_id','external_executor_name'],
    'volunteer_requirements' => ['beneficiary_segment_id','gender','planned_count','tasks_summary'],
];
$sectionKeys = ['gifts'=>'gifts','program_segments'=>'programs','volunteer_requirements'=>'volunteers','supplies'=>'supplies'];
$addKeys = ['gifts'=>'add_gift','program_segments'=>'add_program','volunteer_requirements'=>'add_volunteer','supplies'=>'add_supply'];
$fieldKeys = [
    'description'=>'description','planned_quantity'=>'planned_quantity','has_supporting_entity'=>'supporting_entity',
    'supporting_entity_name'=>'supporting_entity_name','unit_value'=>'unit_value','name'=>'name',
    'starts_at'=>'starts_at','ends_at'=>'ends_at','duration_minutes'=>'duration_minutes','sort_order'=>'sort_order',
    'executor_user_id'=>'executor','external_executor_name'=>'external_executor_name','beneficiary_segment_id'=>'beneficiary_segment',
    'gender'=>'gender','planned_count'=>'planned_count','tasks_summary'=>'tasks_summary','item_name'=>'item_name',
    'provider_type'=>'provider_type','provider_name'=>'provider_name','estimated_value'=>'estimated_value','notes'=>'notes',
];
@endphp
@foreach($definitions as $collection => $fields)
@if(!isset($collectionFilter) || in_array($collection, $collectionFilter, true))
<div class="card shadow-sm mb-3" data-repeat="{{ $collection }}">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <strong>{{ __('ramadan_iftars.sections.'.$sectionKeys[$collection]) }}</strong>
        <button type="button" class="btn btn-sm btn-outline-primary add-row">{{ __('ramadan_iftars.planning.'.$addKeys[$collection]) }}</button>
    </div>
    <div class="card-body">
        @foreach($collections[$collection] as $i => $row)
        <div class="planning-row border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2"><strong>{{ __('ramadan_iftars.planning.row', ['number'=>$i + 1]) }}</strong><button type="button" class="btn btn-sm btn-outline-danger remove-row">{{ __('ramadan_iftars.planning.remove') }}</button></div>
            <input type="hidden" name="{{ $collection }}[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
            <div class="row g-3">
            @foreach($fields as $field)<div class="col-md-4"><label class="form-label">{{ __('ramadan_iftars.labels.'.$fieldKeys[$field]) }}</label>
                @if(in_array($field,['planned_quantity','planned_count','duration_minutes','sort_order'],true))
                    <input type="number" min="0" class="form-control" name="{{ $collection }}[{{ $i }}][{{ $field }}]" value="{{ old($collection.'.'.$i.'.'.$field, $row[$field] ?? 0) }}">
                @elseif($field === 'has_supporting_entity')
                    <select class="form-select" name="{{ $collection }}[{{ $i }}][{{ $field }}]"><option value="0">{{ __('ramadan_iftars.options.no_supporter') }}</option><option value="1" @selected($row[$field] ?? false)>{{ __('ramadan_iftars.options.supported') }}</option></select>
                @elseif($field === 'executor_user_id')
                    <select class="form-select" name="{{ $collection }}[{{ $i }}][{{ $field }}]"><option value="">{{ __('ramadan_iftars.options.external_none') }}</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(($row[$field]??null)==$user->id)>{{ $user->name }}</option>@endforeach</select>
                @elseif($field === 'beneficiary_segment_id')
                    <select class="form-select" name="{{ $collection }}[{{ $i }}][{{ $field }}]"><option value="">{{ __('ramadan_iftars.options.none') }}</option>@foreach($beneficiarySegments as $segment)<option value="{{ $segment->id }}" @selected(($row[$field]??null)==$segment->id)>{{ app()->getLocale()==='ar' ? $segment->name_ar : ($segment->name_en ?: $segment->name_ar) }}</option>@endforeach</select>
                @elseif($field === 'gender')
                    <select class="form-select" name="{{ $collection }}[{{ $i }}][{{ $field }}]">@foreach(['male','female','mixed'] as $option)<option value="{{ $option }}" @selected(($row[$field]??null)===$option)>{{ __('ramadan_iftars.options.'.$option) }}</option>@endforeach</select>
                @else
                    <input class="form-control" name="{{ $collection }}[{{ $i }}][{{ $field }}]" value="{{ old($collection.'.'.$i.'.'.$field, $row[$field] ?? '') }}" placeholder="{{ __('ramadan_iftars.labels.'.$fieldKeys[$field]) }}">
                @endif
            </div>@endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endforeach

@if($includeMeals ?? true)
<div class="card shadow-sm mb-3" data-repeat="meals">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><strong>{{ __('ramadan_iftars.sections.meals') }}</strong><button type="button" class="btn btn-sm btn-outline-primary add-row">{{ __('ramadan_iftars.planning.add_meal') }}</button></div>
    <div class="card-body">@foreach($collections['meals'] as $i => $meal)<div class="planning-row border rounded p-3 mb-3">
        <div class="d-flex justify-content-between mb-2"><strong>{{ __('ramadan_iftars.planning.meal_row',['number'=>$i+1]) }}</strong><button type="button" class="btn btn-sm btn-outline-danger remove-row">{{ __('ramadan_iftars.planning.remove') }}</button></div>
        <input type="hidden" name="meals[{{ $i }}][id]" value="{{ $meal['id']??'' }}"><div class="row g-3"><div class="col-md-5"><label class="form-label">{{ __('ramadan_iftars.labels.description') }}</label><input class="form-control" name="meals[{{ $i }}][description]" value="{{ $meal['description']??'' }}"></div><div class="col-md-3"><label class="form-label">{{ __('ramadan_iftars.labels.planned_quantity') }}</label><input type="number" min="0" class="form-control" name="meals[{{ $i }}][planned_quantity]" value="{{ $meal['planned_quantity']??0 }}"></div><div class="col-md-4"><label class="form-label">{{ __('ramadan_iftars.labels.estimated_value') }}</label><input class="form-control" name="meals[{{ $i }}][estimated_value]" value="{{ $meal['estimated_value']??'' }}"></div></div>
        @foreach($meal['items']??[] as $j=>$item)<div class="row g-3 mt-1"><input type="hidden" name="meals[{{ $i }}][items][{{ $j }}][id]" value="{{ $item['id']??'' }}"><div class="col-md-5"><label class="form-label">{{ __('ramadan_iftars.labels.item_name') }}</label><input class="form-control" name="meals[{{ $i }}][items][{{ $j }}][name]" value="{{ $item['name']??'' }}"></div><div class="col-md-4"><label class="form-label">{{ __('ramadan_iftars.labels.item_type') }}</label><select class="form-select" name="meals[{{ $i }}][items][{{ $j }}][item_type]">@foreach($mealItemTypes as $type)<option value="{{ $type }}" @selected(($item['item_type']??null)===$type)>{{ __('ramadan_iftars.options.'.$type) }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label">{{ __('ramadan_iftars.labels.quantity') }}</label><input type="number" min="0" class="form-control" name="meals[{{ $i }}][items][{{ $j }}][quantity]" value="{{ $item['quantity']??'' }}"></div><div class="col-md-4"><label class="form-label">مرفقات / مكونات الطبق</label><input class="form-control" name="meals[{{ $i }}][items][{{ $j }}][notes]" value="{{ $item['notes']??'' }}"></div></div>@endforeach
    </div>@endforeach</div>
</div>
@endif
