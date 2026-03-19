@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.edit_title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
@endphp


@section('content')
    <div class="event-module">
    <div class="card event-card mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>

            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <div class="fw-semibold mb-2">يرجى تصحيح الأخطاء التالية:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.edit_details') }}</h2>
            <form method="POST" action="{{ route('role.programs.activities.update', $monthlyActivity) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.title') }}</label>
                    <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $monthlyActivity->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.activity_date') }}</label>
                    <input class="form-control" type="date" name="activity_date" value="{{ sprintf('%04d-%02d-%02d', now()->year, $monthlyActivity->month, $monthlyActivity->day) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label>
                    <input class="form-control" type="date" name="proposed_date" value="{{ optional($monthlyActivity->proposed_date)->format('Y-m-d') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($monthlyActivity->branch_id === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" @selected($monthlyActivity->center_id === $center->id)>
                                {{ $center->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.agenda_event') }}</label>
                    <select class="form-select" name="agenda_event_id">
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.agenda_event_placeholder') }}</option>
                        @foreach ($agendaEvents as $event)
                            <option value="{{ $event->id }}" @selected($monthlyActivity->agenda_event_id === $event->id)>
                                {{ $event->event_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="draft" @selected($monthlyActivity->status === 'draft')>{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option>
                        <option value="submitted" @selected($monthlyActivity->status === 'submitted')>{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option>
                        <option value="changes_requested" @selected($monthlyActivity->status === 'changes_requested')>{{ __('app.roles.programs.monthly_activities.statuses.changes_requested') }}</option>
                        <option value="closed" @selected($monthlyActivity->status === 'closed')>{{ __('app.roles.programs.monthly_activities.statuses.closed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">نوع المكان</label>
                    <select class="form-select js-location-type @error('location_type') is-invalid @enderror" name="location_type" required>
                        <option value="inside_center" @selected(old('location_type', $monthlyActivity->location_type) === 'inside_center')>داخل المركز</option>
                        <option value="outside_center" @selected(old('location_type', $monthlyActivity->location_type) === 'outside_center')>خارج المركز</option>
                    </select>
                    @error('location_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-inside-location">
                    <label class="form-label">أي قاعة</label>
                    <input class="form-control @error('internal_location') is-invalid @enderror" name="internal_location" value="{{ old('internal_location', $monthlyActivity->internal_location) }}">
                    @error('internal_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">اسم الموقع</label>
                    <input class="form-control @error('outside_place_name') is-invalid @enderror" name="outside_place_name" value="{{ old('outside_place_name', $monthlyActivity->outside_place_name) }}">
                    @error('outside_place_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رابط الموقع من Google Maps</label>
                    <input class="form-control @error('outside_google_maps_url') is-invalid @enderror" name="outside_google_maps_url" value="{{ old('outside_google_maps_url', $monthlyActivity->outside_google_maps_url) }}">
                    @error('outside_google_maps_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.responsible_entity') }}</label>
                    <input class="form-control" name="responsible_party" value="{{ $monthlyActivity->responsible_party }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.execution_time') }}</label>
                    <input class="form-control" name="execution_time" value="{{ $monthlyActivity->execution_time }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">الفئة المستهدفة</label>
                    <select class="form-select js-target-group" name="target_group_id">
                        <option value="">-- اختر --</option>
                        @foreach($targetGroups as $group)
                            <option value="{{ $group->id }}" data-is-other="{{ $group->is_other ? 1 : 0 }}" @selected((string) old('target_group_id', $monthlyActivity->target_group_id) === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 js-target-group-other">
                    <label class="form-label">أخرى (توضيح)</label>
                    <input class="form-control" name="target_group_other" value="{{ old('target_group_other', $monthlyActivity->target_group_other ) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.short_description') }}</label>
                    <input class="form-control" name="short_description" value="{{ $monthlyActivity->short_description }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.need_volunteers') }}</label>
                    <input class="form-control" name="volunteer_need" value="{{ $monthlyActivity->volunteer_need }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.sponsors_open') }}</label>
                    <div class="row g-2">
                        @for ($i = 0; $i < 5; $i++)
                            @php $sponsor = $monthlyActivity->sponsors[$i] ?? null; @endphp
                            <div class="col-12 col-md-4">
                                <input class="form-control" name="sponsors[{{ $i }}][name]" value="{{ old("sponsors.$i.name", $sponsor->name ?? null) }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.sponsor_name') }}">
                            </div>
                            <div class="col-12 col-md-4">
                                <input class="form-control" name="sponsors[{{ $i }}][title]" value="{{ old("sponsors.$i.title", $sponsor->title ?? null) }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.sponsor_title') }}">
                            </div>
                            <div class="col-12 col-md-4 d-flex align-items-center">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="sponsors[{{ $i }}][is_official]" value="1" id="sponsor-edit-official-{{ $i }}" @checked(old("sponsors.$i.is_official", $sponsor?->is_official ?? true))>
                                    <label class="form-check-label" for="sponsor-edit-official-{{ $i }}">{{ __('app.roles.programs.monthly_activities.fields_ext.official_sponsor') }}</label>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.partners_open') }}</label>
                    <div class="row g-2">
                        @for ($i = 0; $i < 7; $i++)
                            @php $partner = $monthlyActivity->partners[$i] ?? null; @endphp
                            <div class="col-12 col-md-6">
                                <input class="form-control" name="partners[{{ $i }}][name]" value="{{ old("partners.$i.name", $partner->name ?? null) }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.partner_name') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <input class="form-control" name="partners[{{ $i }}][role]" value="{{ old("partners.$i.role", $partner->role ?? null) }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.partner_role') }}">
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="needs_official_letters" value="1" id="needs_letters_edit" @checked($monthlyActivity->needs_official_letters)>
                        <label class="form-check-label" for="needs_letters_edit">{{ __('app.roles.programs.monthly_activities.fields_ext.needs_letters') }}</label>
                    </div>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.letter_reason') }}</label>
                    <input class="form-control" name="letter_purpose" value="{{ $monthlyActivity->letter_purpose }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label>
                    <input class="form-control" type="date" name="rescheduled_date" value="{{ optional($monthlyActivity->rescheduled_date)->format('Y-m-d') }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.reschedule_reason') }}</label>
                    <input class="form-control" name="reschedule_reason" value="{{ $monthlyActivity->reschedule_reason }}">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="relations_approval_on_reschedule" value="1" id="relations_reschedule_approve_edit" @checked($monthlyActivity->relations_approval_on_reschedule)>
                        <label class="form-check-label" for="relations_reschedule_approve_edit">{{ __('app.roles.programs.monthly_activities.fields_ext.relations_reschedule_approve') }}</label>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.audience_satisfaction') }}</label>
                    <input class="form-control" type="number" min="0" max="100" step="0.01" name="audience_satisfaction_percent" value="{{ $monthlyActivity->audience_satisfaction_percent }}">
                </div>
                <div class="col-12"><div class="event-form-section"><h2 class="event-section-title">الحضور والمتطوعين</h2></div></div>
                <div class="col-12 col-md-3"><label class="form-label">الحضور المتوقع</label><input class="form-control" type="number" min="0" name="expected_attendance" value="{{ old('expected_attendance', $monthlyActivity->expected_attendance ) }}"></div>
                <div class="col-12 col-md-3"><label class="form-label">الحضور الفعلي</label><input class="form-control" type="number" min="0" name="actual_attendance" value="{{ old('actual_attendance', $monthlyActivity->actual_attendance ) }}"></div>
                <div class="col-12 col-md-3 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_volunteers" value="1" id="needs_volunteers" @checked(old('needs_volunteers', $monthlyActivity->needs_volunteers))><label class="form-check-label" for="needs_volunteers">تحتاج متطوعين</label></div></div>
                <div class="col-12 col-md-3"><label class="form-label">عدد المتطوعين المطلوب</label><input class="form-control" type="number" min="0" name="required_volunteers" value="{{ old('required_volunteers', $monthlyActivity->required_volunteers ) }}"></div>
                <div class="col-12"><label class="form-label">ملاحظات الحضور</label><textarea class="form-control" name="attendance_notes" rows="2">{{ old('attendance_notes', $monthlyActivity->attendance_notes ) }}</textarea></div>

                <div class="col-12"><div class="event-form-section"><h2 class="event-section-title">المخاطبات والتغطية الإعلامية</h2></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_official_correspondence" value="1" id="needs_official_correspondence" @checked(old('needs_official_correspondence', $monthlyActivity->needs_official_correspondence))><label class="form-check-label" for="needs_official_correspondence">تحتاج مخاطبات رسمية</label></div></div>
                <div class="col-12 col-md-8"><label class="form-label">سبب المخاطبة</label><input class="form-control" name="official_correspondence_reason" value="{{ old('official_correspondence_reason', $monthlyActivity->official_correspondence_reason ) }}"></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_media_coverage" value="1" id="needs_media_coverage" @checked(old('needs_media_coverage', $monthlyActivity->needs_media_coverage))><label class="form-check-label" for="needs_media_coverage">تحتاج تغطية إعلامية</label></div></div>
                <div class="col-12 col-md-8"><label class="form-label">ملاحظات التغطية الإعلامية</label><input class="form-control" name="media_coverage_notes" value="{{ old('media_coverage_notes', $monthlyActivity->media_coverage_notes ) }}"></div>


                <div class="col-12"><div class="event-form-section"><h2 class="event-section-title">Workflow Routing</h2></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_programs" value="1" id="requires_programs" @checked(old('requires_programs', $monthlyActivity->requires_programs))><label class="form-check-label" for="requires_programs">requires_programs</label></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_workshops" value="1" id="requires_workshops" @checked(old('requires_workshops', $monthlyActivity->requires_workshops))><label class="form-check-label" for="requires_workshops">requires_workshops</label></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_communications" value="1" id="requires_communications" @checked(old('requires_communications', $monthlyActivity->requires_communications))><label class="form-check-label" for="requires_communications">requires_communications</label></div></div>

                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ $monthlyActivity->description }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.programs.monthly_activities.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.supplies.title') }}</h2>
            <form method="POST" action="{{ route('role.programs.supplies.store', $monthlyActivity) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.supplies.fields.item_name') }}</label>
                    <input class="form-control" name="item_name" required>
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="available" value="1" id="supply-available">
                        <label class="form-check-label" for="supply-available">
                            {{ __('app.roles.programs.monthly_activities.supplies.fields.available') }}
                        </label>
                    </div>
                </div>
                <div class="col-12 col-md-3 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-primary btn-sm mt-4" type="submit">
                        {{ __('app.roles.programs.monthly_activities.supplies.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.programs.monthly_activities.supplies.table.item_name') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.supplies.table.available') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.supplies.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyActivity->supplies as $supply)
                            <tr>
                                <td>{{ $supply->item_name }}</td>
                                <td>{{ $supply->available ? __('app.roles.programs.monthly_activities.supplies.available_yes') : __('app.roles.programs.monthly_activities.supplies.available_no') }}</td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('role.programs.supplies.destroy', $supply) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.programs.monthly_activities.supplies.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.programs.monthly_activities.supplies.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.team.title') }}</h2>
            <form method="POST" action="{{ route('role.programs.team.store', $monthlyActivity) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label">اسم الفريق</label>
                    <input class="form-control" name="team_name">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.team.fields.member_name') }}</label>
                    <input class="form-control" name="member_name" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input class="form-control" type="email" name="member_email">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.team.fields.role_desc') }}</label>
                    <input class="form-control" name="role_desc">
                </div>
                <div class="col-12 col-md-2 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-primary btn-sm mt-4" type="submit">
                        {{ __('app.roles.programs.monthly_activities.team.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>الفريق</th>
                            <th>{{ __('app.roles.programs.monthly_activities.team.table.member_name') }}</th>
                            <th>البريد</th>
                            <th>{{ __('app.roles.programs.monthly_activities.team.table.role_desc') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.team.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyActivity->team as $member)
                            <tr>
                                <td>{{ $member->team_name ?? '-' }}</td>
                                <td>{{ $member->member_name }}</td>
                                <td>{{ $member->member_email ?? '-' }}</td>
                                <td>{{ $member->role_desc ?? '-' }}</td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('role.programs.team.destroy', $member) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.programs.monthly_activities.team.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.programs.monthly_activities.team.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.attachments.title') }}</h2>
            <form method="POST" action="{{ route('role.programs.attachments.store', $monthlyActivity) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.attachments.fields.file_type') }}</label>
                    <input class="form-control" name="file_type" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.attachments.fields.file_path') }}</label>
                    <input class="form-control" name="file_path" required>
                </div>
                <div class="col-12 col-md-2 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-primary btn-sm mt-4" type="submit">
                        {{ __('app.roles.programs.monthly_activities.attachments.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.programs.monthly_activities.attachments.table.file_type') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.attachments.table.file_path') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.attachments.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyActivity->attachments as $attachment)
                            <tr>
                                <td>{{ $attachment->file_type }}</td>
                                <td>{{ $attachment->file_path }}</td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('role.programs.attachments.destroy', $attachment) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.programs.monthly_activities.attachments.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.programs.monthly_activities.attachments.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.close_title') }}</h2>
            <form method="POST" action="{{ route('role.programs.activities.close', $monthlyActivity) }}" class="row g-3">
                @csrf
                @method('PATCH')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.actual_date') }}</label>
                    <input class="form-control" type="date" name="actual_date" value="{{ optional($monthlyActivity->actual_date)->format('Y-m-d') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="closed">{{ __('app.roles.programs.monthly_activities.statuses.closed') }}</option>
                        <option value="completed">{{ __('app.roles.programs.monthly_activities.statuses.completed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex justify-content-end align-items-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.programs.monthly_activities.actions.close') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const locType = document.querySelector('.js-location-type');
  const inside = document.querySelectorAll('.js-inside-location');
  const outside = document.querySelectorAll('.js-outside-location');
  const tg = document.querySelector('.js-target-group');
  const tgOther = document.querySelectorAll('.js-target-group-other');
  const toggle = () => {
    const outsideSelected = locType && locType.value === 'outside_center';
    inside.forEach(el => el.style.display = outsideSelected ? 'none' : 'block');
    outside.forEach(el => el.style.display = outsideSelected ? 'block' : 'none');
    const selected = tg?.selectedOptions?.[0];
    const isOther = selected && selected.dataset.isOther === '1';
    tgOther.forEach(el => el.style.display = isOther ? 'block' : 'none');
  };
  locType?.addEventListener('change', toggle);
  tg?.addEventListener('change', toggle);
  toggle();
});
</script>
@endpush

@endsection
