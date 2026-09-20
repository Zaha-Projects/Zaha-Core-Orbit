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
<div class="card shadow-sm mb-3 ramadan-form-section" data-repeat="{{ $collection }}" id="{{ $collection === 'program_segments' ? 'iftar-program' : 'iftar-volunteers' }}" data-error-prefixes="{{ $collection }}">
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
            @foreach($fields as $field)@php($errorKey=$collection.'.'.$i.'.'.$field)<div class="col-md-4"><label class="form-label">{{ __('ramadan_iftars.labels.'.$fieldKeys[$field]) }}</label>
                @if(in_array($field,['planned_quantity','planned_count','duration_minutes','sort_order'],true))
                    <input type="number" min="0" class="form-control @error($errorKey) is-invalid @enderror" name="{{ $collection }}[{{ $i }}][{{ $field }}]" value="{{ old($errorKey, $row[$field] ?? 0) }}">
                @elseif($field === 'has_supporting_entity')
                    <select class="form-select @error($errorKey) is-invalid @enderror" name="{{ $collection }}[{{ $i }}][{{ $field }}]"><option value="0">{{ __('ramadan_iftars.options.no_supporter') }}</option><option value="1" {{ ($row[$field] ?? false) ? 'selected' : '' }}>{{ __('ramadan_iftars.options.supported') }}</option></select>
                @elseif($field === 'executor_user_id')
                    <select class="form-select @error($errorKey) is-invalid @enderror" name="{{ $collection }}[{{ $i }}][{{ $field }}]"><option value="">{{ __('ramadan_iftars.options.external_none') }}</option>@foreach($users as $user)<option value="{{ $user->id }}" {{ ($row[$field] ?? null) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>@endforeach</select>
                @elseif($field === 'beneficiary_segment_id')
                    <select class="form-select @error($errorKey) is-invalid @enderror" name="{{ $collection }}[{{ $i }}][{{ $field }}]"><option value="">{{ __('ramadan_iftars.options.none') }}</option>@foreach($beneficiarySegments as $segment)<option value="{{ $segment->id }}" {{ ($row[$field] ?? null) == $segment->id ? 'selected' : '' }}>{{ app()->getLocale()==='ar' ? $segment->name_ar : ($segment->name_en ?: $segment->name_ar) }}</option>@endforeach</select>
                @elseif($field === 'gender')
                    <select class="form-select @error($errorKey) is-invalid @enderror" name="{{ $collection }}[{{ $i }}][{{ $field }}]">@foreach(['male','female','mixed'] as $option)<option value="{{ $option }}" {{ ($row[$field] ?? null) === $option ? 'selected' : '' }}>{{ __('ramadan_iftars.options.'.$option) }}</option>@endforeach</select>
                @else
                    <input class="form-control @error($errorKey) is-invalid @enderror" name="{{ $collection }}[{{ $i }}][{{ $field }}]" value="{{ old($errorKey, $row[$field] ?? '') }}" placeholder="{{ __('ramadan_iftars.labels.'.$fieldKeys[$field]) }}">
                @endif
                @error($errorKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>@endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endforeach

@if($includeMeals ?? true)
<div class="card shadow-sm mb-3 ramadan-form-section" data-repeat="meals" id="iftar-meals" data-error-prefixes="meals">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><span><strong>٤. {{ __('ramadan_iftars.sections.meals') }}</strong><small>أضف الوجبات ومكوناتها والكميات المخطط لها</small></span><button type="button" class="btn btn-sm btn-outline-primary add-row">{{ __('ramadan_iftars.planning.add_meal') }}</button></div>
    <div class="card-body">@foreach($collections['meals'] as $i => $meal)<div class="planning-row border rounded p-3 mb-3">
        <div class="d-flex justify-content-between mb-2"><strong>{{ __('ramadan_iftars.planning.meal_row',['number'=>$i+1]) }}</strong><button type="button" class="btn btn-sm btn-outline-danger remove-row">{{ __('ramadan_iftars.planning.remove') }}</button></div>
        <input type="hidden" name="meals[{{ $i }}][id]" value="{{ $meal['id']??'' }}"><div class="row g-3"><div class="col-md-5"><label class="form-label">{{ __('ramadan_iftars.labels.description') }} <span class="text-danger">*</span></label><input class="form-control @error('meals.'.$i.'.description') is-invalid @enderror" name="meals[{{ $i }}][description]" value="{{ $meal['description']??'' }}">@error('meals.'.$i.'.description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-3"><label class="form-label">{{ __('ramadan_iftars.labels.planned_quantity') }} <span class="text-danger">*</span></label><input type="number" min="1" class="form-control @error('meals.'.$i.'.planned_quantity') is-invalid @enderror" name="meals[{{ $i }}][planned_quantity]" value="{{ $meal['planned_quantity']??0 }}">@error('meals.'.$i.'.planned_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-4"><label class="form-label">{{ __('ramadan_iftars.labels.estimated_value') }}</label><input class="form-control" name="meals[{{ $i }}][estimated_value]" value="{{ $meal['estimated_value']??'' }}"></div><div class="col-md-6"><label class="form-label">اسم المطعم <span class="text-danger">*</span></label><input class="form-control @error('meals.'.$i.'.restaurant_name') is-invalid @enderror" name="meals[{{ $i }}][restaurant_name]" value="{{ $meal['restaurant_name']??'' }}">@error('meals.'.$i.'.restaurant_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-6"><label class="form-label">رقم التواصل مع المطعم <span class="text-danger">*</span></label><input class="form-control @error('meals.'.$i.'.restaurant_contact') is-invalid @enderror" name="meals[{{ $i }}][restaurant_contact]" value="{{ $meal['restaurant_contact']??'' }}">@error('meals.'.$i.'.restaurant_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>
        @foreach($meal['items']??[] as $j=>$item)<div class="row g-3 mt-1"><input type="hidden" name="meals[{{ $i }}][items][{{ $j }}][id]" value="{{ $item['id']??'' }}"><div class="col-md-5"><label class="form-label">{{ __('ramadan_iftars.labels.item_name') }} <span class="text-danger">*</span></label><input class="form-control @error('meals.'.$i.'.items.'.$j.'.name') is-invalid @enderror" name="meals[{{ $i }}][items][{{ $j }}][name]" value="{{ $item['name']??'' }}">@error('meals.'.$i.'.items.'.$j.'.name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div><div class="col-md-4"><label class="form-label">{{ __('ramadan_iftars.labels.item_type') }}</label><select class="form-select" name="meals[{{ $i }}][items][{{ $j }}][item_type]">@foreach($mealItemTypes as $type)<option value="{{ $type }}" {{ ($item['item_type'] ?? null) === $type ? 'selected' : '' }}>{{ __('ramadan_iftars.options.'.$type) }}</option>@endforeach</select></div><div class="col-md-2"><label class="form-label">{{ __('ramadan_iftars.labels.quantity') }}</label><input type="number" min="0" class="form-control" name="meals[{{ $i }}][items][{{ $j }}][quantity]" value="{{ $item['quantity']??'' }}"></div><div class="col-md-4"><label class="form-label">مرفقات / مكونات الطبق <span class="text-danger">*</span></label><input class="form-control @error('meals.'.$i.'.items.'.$j.'.notes') is-invalid @enderror" name="meals[{{ $i }}][items][{{ $j }}][notes]" value="{{ $item['notes']??'' }}">@error('meals.'.$i.'.items.'.$j.'.notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div></div>@endforeach
    </div>@endforeach</div>
</div>
@endif
