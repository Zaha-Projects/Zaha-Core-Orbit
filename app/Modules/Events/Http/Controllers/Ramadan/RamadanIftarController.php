<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\AgendaEvent;
use App\Modules\Events\Models\TargetGroup;
use App\Models\User;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Http\Requests\Ramadan\StoreRamadanIftarRequest;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\MealType;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarPlanningService;
use App\Modules\Events\Services\RamadanGuidanceAcceptanceService;
use Illuminate\Http\Request;
use App\Modules\Events\Models\RamadanPeriod;

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
            ->with('success', __('ramadan_iftars.messages.created'));
    }

    public function edit(Request $request, RamadanIftar $ramadanIftar)
    {
        $this->authorizePlanningAccess($request, $ramadanIftar);
        abort_unless($ramadanIftar->isPlanningEditable(), 403);
        $ramadanIftar->load([
            'attendees', 'targetGroupSelections', 'meals.items', 'gifts', 'programSegments',
            'executionTeams.members', 'volunteerRequirements.beneficiarySegment', 'supplies',
            'executionNeeds.executionNeedType',
        ]);

        return view('pages.events.ramadan.edit', $this->formOptions($request, $ramadanIftar));
    }

    public function update(StoreRamadanIftarRequest $request, RamadanIftar $ramadanIftar)
    {
        $this->planning->update($ramadanIftar, $request->validated());

        return redirect()->route('events.ramadan.iftars.edit', $ramadanIftar)
            ->with('success', __('ramadan_iftars.messages.updated'));
    }

    private function authorizePlanningAccess(Request $request, ?RamadanIftar $iftar = null): void
    {
        $user = $request->user();
        $permission = $iftar ? 'ramadan_iftars.edit' : 'ramadan_iftars.create';
        abort_unless($user && ($user->hasAnyRole(['relations_manager', 'relations_officer', 'super_admin']) || $user->can($permission)), 403);
        if ($iftar && ! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) {
            abort_unless($user->hasAccessToScopedBranch((int) $iftar->branch_id), 403);
        }
    }

    private function formOptions(Request $request, ?RamadanIftar $iftar = null): array
    {
        $user = $request->user();
        $selectedBranchId = $iftar?->branch_id ?? $user->branch_id ?? collect($user->scopedBranchIds())->first();
        abort_unless($selectedBranchId, 422, 'لا يوجد فرع مخول للمستخدم.');
        $users = User::query()->where('status', 'active')->when($selectedBranchId, function ($query, $branchId) {
            $query->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereHas('assignedBranches', fn ($b) => $b->whereKey($branchId)));
        })->orderBy('name')->get();

        return [
            'ramadanIftar' => $iftar,
            'authorizedBranchId' => (int) $selectedBranchId,
            'agendaEvents' => AgendaEvent::query()->when($selectedBranchId, fn ($q, $id) => $q->forBranchAudience([(int) $id]))->orderBy('event_date')->get(),
            'targetGroups' => TargetGroup::query()->where(function ($query) use ($iftar) {
                $query->where(fn ($available) => $available->active()->forRamadanIftars());
                if ($iftar) {
                    $query->orWhereIn('id', $iftar->targetGroupSelections()->pluck('target_group_id'));
                }
            })->orderBy('sort_order')->get(),
            'beneficiarySegments' => BeneficiarySegment::query()->where(function ($query) use ($iftar) {
                $query->active();
                if ($iftar) $query->orWhereIn('id', $iftar->targetGroupSelections()->pluck('beneficiary_segment_id')->merge($iftar->volunteerRequirements()->pluck('beneficiary_segment_id'))->filter());
            })->ordered()->get(),
            'mobilizationMethods' => MobilizationMethod::query()->where(fn ($query) => $query->active()->when($iftar?->mobilization_method_id, fn ($q, $id) => $q->orWhere('id', $id)))->ordered()->get(),
            'selectedCommunityOrganization' => $iftar?->community_organization_id ? CommunityOrganization::query()->where('branch_id', $selectedBranchId)->find($iftar->community_organization_id) : null,
            'selectedLocalCommunity' => $iftar?->local_community_id ? LocalCommunity::query()->where('branch_id', $selectedBranchId)->find($iftar->local_community_id) : null,
            'users' => $users,
            'locationTypes' => RamadanIftar::locationTypes(),
            'hostTypes' => [RamadanIftar::HOST_ASSOCIATION, RamadanIftar::HOST_CENTER, RamadanIftar::HOST_LOCAL_COMMUNITY],
            'mealItemTypes' => MealType::query()->where(function ($query) use ($iftar) {
                $query->active();
                if ($iftar) $query->orWhereIn('code', $iftar->meals()->with('items')->get()->pluck('items')->flatten()->pluck('item_type'));
            })->ordered()->get(),
            'giftTypes' => \App\Modules\Events\Models\RamadanIftarGift::typeLabels(),
            'executionNeedTypes' => ExecutionNeedType::ramadanAvailableTypes(),
            'ramadanPeriod' => RamadanPeriod::current(),
            'ramadanPeriods' => RamadanPeriod::query()->active()->orderBy('start_date')->get(),
        ];
    }
}
