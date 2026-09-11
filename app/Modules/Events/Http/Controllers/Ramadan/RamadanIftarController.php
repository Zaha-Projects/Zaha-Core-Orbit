<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\TargetGroup;
use App\Models\User;
use App\Modules\Events\Http\Requests\Ramadan\StoreRamadanIftarRequest;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarMealItem;
use App\Modules\Events\Services\RamadanIftarPlanningService;
use App\Modules\Events\Services\RamadanGuidanceAcceptanceService;
use Illuminate\Http\Request;

class RamadanIftarController extends Controller
{
    private RamadanIftarPlanningService $planning;
    private RamadanGuidanceAcceptanceService $guidanceAcceptance;

    public function __construct(RamadanIftarPlanningService $planning, RamadanGuidanceAcceptanceService $guidanceAcceptance)
    {
        $this->planning = $planning;
        $this->guidanceAcceptance = $guidanceAcceptance;
    }

    public function create(Request $request)
    {
        $this->authorizePlanningAccess($request);

        if (! $this->guidanceAcceptance->hasAcceptedCurrent($request)) {
            return redirect()->route('events.ramadan.guidance.show');
        }

        return view('pages.events.ramadan.create', $this->formOptions($request));
    }

    public function store(StoreRamadanIftarRequest $request)
    {
        [$guidance, $acceptedAt] = $this->guidanceAcceptance->acceptedCurrentOrFail($request);
        $iftar = $this->planning->create($request->validated(), $request->user(), $guidance, $acceptedAt);
        $this->guidanceAcceptance->forgetAcceptance($request);

        return redirect()->route('events.ramadan.iftars.edit', $iftar)
            ->with('success', 'Ramadan Iftar plan created successfully.');
    }

    public function edit(Request $request, RamadanIftar $ramadanIftar)
    {
        $this->authorizePlanningAccess($request, $ramadanIftar);
        abort_unless($ramadanIftar->status === RamadanIftar::STATUS_DRAFT, 403);
        $ramadanIftar->load([
            'targetGroupSelections', 'meals.items', 'gifts', 'programSegments',
            'executionTeams.members', 'volunteerRequirements', 'supplies',
        ]);

        return view('pages.events.ramadan.edit', $this->formOptions($request, $ramadanIftar));
    }

    public function update(StoreRamadanIftarRequest $request, RamadanIftar $ramadanIftar)
    {
        $this->planning->update($ramadanIftar, $request->validated());

        return redirect()->route('events.ramadan.iftars.edit', $ramadanIftar)
            ->with('success', 'Ramadan Iftar plan updated successfully.');
    }

    private function authorizePlanningAccess(Request $request, ?RamadanIftar $iftar = null): void
    {
        $user = $request->user();
        $permission = $iftar ? 'monthly_activities.edit' : 'monthly_activities.create';
        abort_unless($user && ($user->hasAnyRole(['relations_manager', 'relations_officer', 'super_admin']) || $user->can($permission)), 403);
        if ($iftar && ! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
            abort_unless($user->hasAccessToScopedBranch((int) $iftar->branch_id), 403);
        }
    }

    private function formOptions(Request $request, ?RamadanIftar $iftar = null): array
    {
        $user = $request->user();
        $branchIds = ($user->hasRole('super_admin') || $user->can('branches.view.all')) ? null : $user->scopedBranchIds();
        $selectedBranchId = old('branch_id', $iftar?->branch_id ?? ($branchIds[0] ?? null));
        $branchQuery = Branch::query()->orderBy('name');
        if ($branchIds !== null) $branchQuery->whereIn('id', $branchIds);
        $users = User::query()->where('status', 'active')->when($selectedBranchId, function ($query, $branchId) {
            $query->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereHas('assignedBranches', fn ($b) => $b->whereKey($branchId)));
        })->orderBy('name')->get();

        return [
            'ramadanIftar' => $iftar,
            'branches' => $branchQuery->get(),
            'agendaEvents' => AgendaEvent::query()->when($selectedBranchId, fn ($q, $id) => $q->forBranchAudience([(int) $id]))->orderBy('event_date')->get(),
            'targetGroups' => TargetGroup::query()->active()->forRamadanIftars()->orderBy('sort_order')->get(),
            'beneficiarySegments' => BeneficiarySegment::query()->active()->ordered()->get(),
            'mobilizationMethods' => MobilizationMethod::query()->active()->ordered()->get(),
            'communityOrganizations' => CommunityOrganization::query()->active()->when($selectedBranchId, fn ($q, $id) => $q->where('branch_id', $id))->ordered()->get(),
            'localCommunities' => LocalCommunity::query()->active()->when($selectedBranchId, fn ($q, $id) => $q->where('branch_id', $id))->ordered()->get(),
            'users' => $users,
            'locationTypes' => RamadanIftar::locationTypes(),
            'hostTypes' => RamadanIftar::hostTypes(),
            'mealItemTypes' => RamadanIftarMealItem::types(),
        ];
    }
}
