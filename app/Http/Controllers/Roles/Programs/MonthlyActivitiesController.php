<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\Center;
use App\Models\MonthlyActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonthlyActivitiesController extends Controller
{
    public function index()
    {
        $activities = MonthlyActivity::with(['branch', 'center', 'agendaEvent', 'creator'])
            ->orderBy('month')
            ->orderBy('day')
            ->get();
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();

        return view('roles.programs.monthly_activities.index', compact('activities', 'branches', 'centers', 'agendaEvents'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();

        return view('roles.programs.monthly_activities.create', compact('branches', 'centers', 'agendaEvents'));
    }

    public function syncFromAgenda(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $events = AgendaEvent::query()
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->whereNotNull('event_date')
                        ->whereMonth('event_date', $data['month'])
                        ->whereYear('event_date', $data['year']);
                })->orWhere(function ($q) use ($data) {
                    $q->whereNull('event_date')
                        ->where('month', $data['month']);
                });
            })
            ->whereIn('status', ['relations_approved', 'published'])
            ->where(function ($query) use ($data) {
                $query->where('event_type', 'mandatory')
                    ->orWhereHas('participations', function ($participationQuery) use ($data) {
                        $participationQuery
                            ->where('entity_type', 'branch')
                            ->where('entity_id', $data['branch_id'])
                            ->where('participation_status', 'participant');
                    });
            })
            ->get();

        $created = 0;
        foreach ($events as $event) {
            $exists = MonthlyActivity::query()
                ->where('agenda_event_id', $event->id)
                ->where('branch_id', $data['branch_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            MonthlyActivity::create([
                'month' => (int) $event->month,
                'day' => (int) $event->day,
                'title' => $event->event_name,
                'proposed_date' => optional($event->event_date)?->toDateString() ?? Carbon::create($data['year'], $event->month, $event->day)->toDateString(),
                'is_in_agenda' => true,
                'agenda_event_id' => $event->id,
                'description' => $event->notes,
                'location_type' => 'inside_center',
                'location_details' => null,
                'status' => 'draft',
                'branch_id' => (int) $data['branch_id'],
                'center_id' => (int) $data['center_id'],
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', "تمت مزامنة {$created} فعالية من الأجندة إلى خطة الفرع.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'status' => ['required', 'string', 'max:50'],
            'location_type' => ['required', 'string', 'max:255'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $date = Carbon::parse($data['activity_date']);

        MonthlyActivity::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'],
            'is_in_agenda' => !empty($data['agenda_event_id']),
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'status' => $data['status'],
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.created'));
    }

    public function edit(MonthlyActivity $monthlyActivity)
    {
        $monthlyActivity->load(['supplies', 'team', 'attachments', 'approvals']);
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();

        return view('roles.programs.monthly_activities.edit', compact('monthlyActivity', 'branches', 'centers', 'agendaEvents'));
    }

    public function update(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'status' => ['required', 'string', 'max:50'],
            'location_type' => ['required', 'string', 'max:255'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $date = Carbon::parse($data['activity_date']);

        $monthlyActivity->update([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'],
            'is_in_agenda' => !empty($data['agenda_event_id']),
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'status' => $data['status'],
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
        ]);

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.updated', ['activity' => $monthlyActivity->title]));
    }

    public function submit(MonthlyActivity $monthlyActivity)
    {
        $monthlyActivity->update([
            'status' => 'submitted',
        ]);

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.submitted', ['activity' => $monthlyActivity->title]));
    }

    public function close(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'actual_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $monthlyActivity->update([
            'actual_date' => $data['actual_date'] ?? $monthlyActivity->actual_date,
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.closed', ['activity' => $monthlyActivity->title]));
    }
}
