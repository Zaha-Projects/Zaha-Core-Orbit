@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
@endphp

@section('content')
    <div class="event-module">
        <div class="card event-card mb-4">
            <div class="card-body">
                <h1 class="h4 mb-2">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">يرجى تصحيح الأخطاء التالية:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="event-kpi-grid">
            <div class="event-kpi-card"><div class="text-muted small">{{ __('app.roles.programs.monthly_activities.list_title') }}</div><div class="event-kpi-value">{{ $activities->count() }}</div></div>
            <div class="event-kpi-card"><div class="text-muted small">{{ __('app.roles.programs.monthly_activities.statuses.approved') }}</div><div class="event-kpi-value">{{ $activities->where('status','approved')->count() }}</div></div>
        </div>

        <div class="card event-card mb-4">
            <div class="card-body">
                <h2 class="event-section-title">{{ __('app.roles.programs.monthly_activities.sync.title') }}</h2>
                <form method="POST" action="{{ route('role.programs.activities.sync_from_agenda') }}" class="row event-form-grid">
                    @csrf
                    <div class="col-12 col-md-6 col-xl-3"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label><select class="form-select" name="branch_id" required><option value="">--</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-6 col-xl-3"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.center') }}</label><select class="form-select" name="center_id" required><option value="">--</option>@foreach ($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select></div>
                    <div class="col-6 col-xl-2"><label class="form-label">{{ __('app.roles.programs.monthly_activities.sync.month') }}</label><input type="number" min="1" max="12" class="form-control" name="month" value="{{ now()->month }}" required></div>
                    <div class="col-6 col-xl-2"><label class="form-label">{{ __('app.roles.programs.monthly_activities.sync.year') }}</label><input type="number" min="2020" max="2100" class="form-control" name="year" value="{{ now()->year }}" required></div>
                    <div class="col-12 col-xl-2 event-actions"><button class="btn btn-outline-primary" type="submit">{{ __('app.roles.programs.monthly_activities.sync.run') }}</button></div>
                </form>
            </div>
        </div>

        <div class="card event-card mb-4">
            <div class="card-body">
                <h2 class="event-section-title">{{ __('app.roles.programs.monthly_activities.create_title') }}</h2>
                <form method="POST" action="{{ route('role.programs.activities.store') }}" class="row event-form-grid">
                    @csrf
                    <div class="col-12 col-lg-6"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.title') }}</label><input class="form-control" name="title" value="{{ old('title') }}" required></div>
                    <div class="col-12 col-md-6 col-lg-3"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.activity_date') }}</label><input class="form-control" type="date" name="activity_date" value="{{ old('activity_date') }}" required></div>
                    <div class="col-12 col-md-6 col-lg-3"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label><input class="form-control" type="date" name="proposed_date" value="{{ old('proposed_date') }}" required></div>
                    <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label><select class="form-select" name="branch_id" required><option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.center') }}</label><select class="form-select" name="center_id" required><option value="">{{ __('app.roles.programs.monthly_activities.fields.center_placeholder') }}</option>@foreach ($centers as $center)<option value="{{ $center->id }}">{{ $center->name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.agenda_event') }}</label><select class="form-select" name="agenda_event_id"><option value="">{{ __('app.roles.programs.monthly_activities.fields.agenda_event_placeholder') }}</option>@foreach ($agendaEvents as $event)<option value="{{ $event->id }}">{{ $event->event_name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-4"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label><select class="form-select" name="status" required><option value="draft">{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option><option value="submitted">{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option><option value="approved">{{ __('app.roles.programs.monthly_activities.statuses.approved') }}</option></select></div>
                    <div class="col-12 col-md-4"><label class="form-label">نوع المكان</label><select class="form-select js-location-type" name="location_type" required><option value="inside_center" @selected(old('location_type', 'inside_center') === 'inside_center')>داخل المركز</option><option value="outside_center" @selected(old('location_type', 'inside_center') === 'outside_center')>خارج المركز</option></select></div>
                    <div class="col-12 col-md-4 js-inside-location"><label class="form-label">أي قاعة</label><input class="form-control" name="internal_location" value="{{ old('internal_location') }}"></div>
                    <div class="col-12 col-md-4 js-outside-location"><label class="form-label">اسم الموقع</label><input class="form-control" name="outside_place_name" value="{{ old('outside_place_name') }}"></div>
                    <div class="col-12 col-md-4 js-outside-location"><label class="form-label">رابط الموقع من Google Maps</label><input class="form-control" name="outside_google_maps_url" value="{{ old('outside_google_maps_url') }}"></div>
                    <div class="col-12"><label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.description') }}</label><textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea></div>
                    <div class="col-12 event-actions"><button class="btn btn-primary" type="submit">{{ __('app.roles.programs.monthly_activities.actions.create') }}</button></div>
                </form>
            </div>
        </div>

        <div class="card event-card">
            <div class="card-body">
                <h2 class="event-section-title">{{ __('app.roles.programs.monthly_activities.list_title') }}</h2>
                <div class="event-table-wrap table-responsive">
                    <table class="table table-sm align-middle event-table">
                        <thead><tr><th>{{ __('app.roles.programs.monthly_activities.table.title') }}</th><th>{{ __('app.roles.programs.monthly_activities.table.date') }}</th><th>مصدر الفعالية</th><th>{{ __('app.roles.programs.monthly_activities.table.branch') }}</th><th>{{ __('app.roles.programs.monthly_activities.table.status') }}</th><th class="text-end">{{ __('app.roles.programs.monthly_activities.table.actions') }}</th></tr></thead>
                        <tbody>
                            @forelse ($activities as $activity)
                                <tr>
                                    <td>{{ $activity->title }}</td><td>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</td><td>@if($activity->is_in_agenda)<span class='badge bg-success-subtle text-success'>من الأجندة</span>@else<span class='badge bg-warning-subtle text-warning'>خارج الأجندة</span>@endif</td><td>{{ $activity->branch?->name ?? '-' }}</td><td><span class="event-status status-{{ $activity->status }}">{{ $activity->status }}</span></td>
                                    <td class="text-end"><div class="event-actions"><a class="btn btn-sm btn-outline-secondary" href="{{ route('role.programs.activities.edit', $activity) }}">{{ __('app.roles.programs.monthly_activities.actions.edit') }}</a><form method="POST" action="{{ route('role.programs.activities.submit', $activity) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.programs.monthly_activities.actions.submit') }}</button></form></div></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted">{{ __('app.roles.programs.monthly_activities.table.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $activities->links() }}</div>
    </div>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const locType = document.querySelector('.js-location-type');
  const inside = document.querySelectorAll('.js-inside-location');
  const outside = document.querySelectorAll('.js-outside-location');
  const toggle = () => {
    const outsideSelected = locType && locType.value === 'outside_center';
    inside.forEach(el => el.style.display = outsideSelected ? 'none' : 'block');
    outside.forEach(el => el.style.display = outsideSelected ? 'block' : 'none');
  };
  locType?.addEventListener('change', toggle);
  toggle();
});
</script>
@endpush
