<?php

namespace App\Modules\Events\Http\Controllers\Bazaar;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use App\Modules\Events\Http\Requests\Bazaar\StoreBazaarRequest;
use App\Modules\Events\Models\Bazaar;
use App\Modules\Events\Models\BazaarTableDiscount;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Models\SubjectTargetGroup;
use App\Modules\Events\Models\TargetGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BazaarController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->browseFilters($request);
        $query = $this->browseQuery($request, $filters)->with(['branch', 'relationsOfficer']);
        $bazaars = $query->orderBy('bazaar_date')->orderBy('starts_at')->paginate(12)->withQueryString();
        $branches = Branch::query()->when(
            ! $request->user()->hasRole('super_admin') && ! $request->user()->can('branches.view.all'),
            fn ($branchQuery) => $branchQuery->whereIn('id', $request->user()->scopedBranchIds())
        )->orderBy('name')->get();

        return view('pages.events.bazaars.index', compact('bazaars', 'branches', 'filters'));
    }

    public function calendar(Request $request)
    {
        $filters = $this->browseFilters($request);
        $items = $this->browseQuery($request, $filters)
            ->with('branch')->orderBy('bazaar_date')->orderBy('starts_at')->get()
            ->map(fn (Bazaar $bazaar) => [
                'title' => $bazaar->name,
                'date' => $bazaar->bazaar_date?->format('Y-m-d'),
                'starts_at' => substr((string) $bazaar->starts_at, 0, 5),
                'ends_at' => substr((string) $bazaar->ends_at, 0, 5),
                'status' => $bazaar->status,
                'status_label' => __('bazaars.statuses.'.$bazaar->status),
                'location' => $bazaar->location_name,
                'branch' => $bazaar->branch?->name,
                'open_url' => route('events.bazaars.show', $bazaar),
            ])->values();

        return response()->json(['items' => $items]);
    }

    public function create(Request $request) { return view('pages.events.bazaars.create', $this->options($request)); }
    public function edit(Request $request, Bazaar $bazaar) { $this->access($request, $bazaar); abort_unless($bazaar->isPlanningEditable(), 403); return view('pages.events.bazaars.edit', $this->options($request, $bazaar)); }
    public function show(Request $request, Bazaar $bazaar) { $this->access($request, $bazaar); return view('pages.events.bazaars.show', ['bazaar' => $bazaar->load($this->relations())]); }

    public function store(StoreBazaarRequest $request)
    {
        $bazaar = DB::transaction(function () use ($request) {
            $bazaar = Bazaar::query()->create(Arr::only($request->validated(), ['branch_id', 'relations_officer_id', 'name', 'bazaar_date', 'starts_at', 'ends_at', 'location_type', 'location_name', 'location_details', 'map_url', 'planned_table_count']) + ['status' => Bazaar::STATUS_DRAFT]);
            $this->sync($bazaar, $request->validated());
            return $bazaar;
        });
        return redirect()->route('events.bazaars.edit', $bazaar)->with('success', 'تم إنشاء خطة البازار.');
    }

    public function update(StoreBazaarRequest $request, Bazaar $bazaar)
    {
        abort_unless($bazaar->isPlanningEditable(), 403);
        DB::transaction(function () use ($request, $bazaar) {
            $bazaar->update(Arr::only($request->validated(), ['name', 'bazaar_date', 'starts_at', 'ends_at', 'location_type', 'location_name', 'location_details', 'map_url', 'planned_table_count']));
            $this->sync($bazaar, $request->validated());
        });
        return redirect()->route('events.bazaars.edit', $bazaar)->with('success', 'تم تحديث خطة البازار.');
    }

    public function submit(Request $request, Bazaar $bazaar) { $this->access($request, $bazaar); abort_unless($bazaar->isPlanningEditable(), 422); abort_unless($bazaar->tables()->count() === $bazaar->planned_table_count, 422, 'عدد الطاولات غير متطابق.'); $bazaar->update(['status' => Bazaar::STATUS_SUBMITTED, 'submitted_at' => now()]); return back()->with('success', 'تم إرسال البازار للاعتماد.'); }

    public function decide(Request $request, Bazaar $bazaar)
    {
        $this->access($request, $bazaar); abort_unless($bazaar->status === Bazaar::STATUS_SUBMITTED, 422);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'return', 'reject'])], 'note' => ['nullable', 'string', 'max:2000']]);
        $status = ['approve' => Bazaar::STATUS_APPROVED, 'return' => Bazaar::STATUS_RETURNED, 'reject' => Bazaar::STATUS_REJECTED][$data['decision']];
        $bazaar->update(['status' => $status, 'approved_by' => $request->user()->id, 'approved_at' => $data['decision'] === 'approve' ? now() : null, 'verification_note' => $data['note'] ?? null]);
        return back()->with('success', 'تم حفظ قرار الاعتماد.');
    }

    public function execution(Request $request, Bazaar $bazaar) { $this->access($request, $bazaar); abort_unless(in_array($bazaar->status, [Bazaar::STATUS_APPROVED, Bazaar::STATUS_EXECUTING], true), 403); return view('pages.events.bazaars.execution', ['bazaar' => $bazaar->load($this->relations())]); }

    public function saveExecution(Request $request, Bazaar $bazaar)
    {
        $this->access($request, $bazaar); abort_unless(in_array($bazaar->status, [Bazaar::STATUS_APPROVED, Bazaar::STATUS_EXECUTING], true), 403);
        $data = $request->validate([
            'tables' => ['required', 'array', 'size:'.$bazaar->planned_table_count], 'tables.*.id' => ['required', 'integer'], 'tables.*.was_booked' => ['required', 'boolean'],
            'tables.*.actual_renter_matches' => ['required_if:tables.*.was_booked,1', 'nullable', 'boolean'], 'tables.*.actual_tenant_name' => ['nullable', 'string', 'max:255'], 'tables.*.actual_tenant_phone' => ['nullable', 'string', 'max:25'], 'tables.*.actual_community_organization_id' => ['nullable', 'integer', 'exists:community_organizations,id'], 'tables.*.renter_change_note' => ['nullable', 'string', 'max:2000'],
            'tables.*.actual_material_description' => ['nullable', 'string', 'max:2000'], 'tables.*.material_matches' => ['nullable', 'boolean'], 'tables.*.material_mismatch_note' => ['nullable', 'string', 'max:2000'],
            'tables.*.is_paid' => ['nullable', 'boolean'], 'tables.*.amount_paid' => ['nullable', 'numeric', 'min:0'], 'tables.*.payment_status' => ['nullable', Rule::in(['paid', 'partial', 'unpaid', 'approved_discount'])], 'tables.*.actual_notes' => ['nullable', 'string', 'max:2000'],
            'tables.*.discount_type' => ['nullable', Rule::in(['amount', 'percentage'])], 'tables.*.discount_value' => ['nullable', 'numeric', 'min:0'], 'tables.*.discount_reason' => ['nullable', 'string', 'max:2000'], 'complete' => ['nullable', 'boolean'],
        ]);
        DB::transaction(function () use ($data, $bazaar, $request) {
            foreach ($data['tables'] as $i => $row) {
                $table = $bazaar->tables()->whereKey($row['id'])->lockForUpdate()->firstOrFail();
                if ($row['was_booked'] && ! ($row['actual_renter_matches'] ?? true) && blank($row['renter_change_note'] ?? null)) abort(422, 'سبب تغيير المستأجر مطلوب للطاولة '.($i + 1));
                if ($row['was_booked'] && ! ($row['material_matches'] ?? true) && blank($row['material_mismatch_note'] ?? null)) abort(422, 'سبب اختلاف المادة مطلوب للطاولة '.($i + 1));
                $table->update(Arr::only($row, ['was_booked', 'actual_renter_matches', 'actual_tenant_name', 'actual_tenant_phone', 'actual_community_organization_id', 'renter_change_note', 'actual_material_description', 'material_matches', 'material_mismatch_note', 'is_paid', 'amount_paid', 'payment_status', 'actual_notes']) + ['amount_due' => $table->currentDiscount?->status === 'approved' ? $table->currentDiscount->final_amount : $table->planned_rental_amount]);
                if (! empty($row['discount_type'])) {
                    abort_if(blank($row['discount_reason'] ?? null), 422, 'سبب الخصم مطلوب.');
                    $table->discounts()->create(['original_amount' => $table->planned_rental_amount, 'discount_type' => $row['discount_type'], 'discount_value' => $row['discount_value'], 'reason' => $row['discount_reason'], 'status' => 'pending', 'requested_by' => $request->user()->id, 'requested_at' => now()]);
                }
            }
            $bazaar->update(['status' => ! empty($data['complete']) ? Bazaar::STATUS_POST_EXECUTION : Bazaar::STATUS_EXECUTING, 'execution_started_at' => $bazaar->execution_started_at ?? now(), 'post_execution_submitted_at' => ! empty($data['complete']) ? now() : null, 'actual_occupied_table_count' => $bazaar->tables()->where('was_booked', true)->count()]);
        });
        return redirect()->route('events.bazaars.show', $bazaar)->with('success', 'تم حفظ بيانات التنفيذ.');
    }

    public function decideDiscount(Request $request, BazaarTableDiscount $discount)
    {
        $bazaar = $discount->table->bazaar; $this->access($request, $bazaar); abort_unless($discount->status === 'pending', 422);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'return', 'reject'])], 'decision_note' => ['nullable', 'string', 'max:2000']]);
        $status = ['approve' => 'approved', 'return' => 'returned', 'reject' => 'rejected'][$data['decision']];
        $final = null;
        if ($status === 'approved') { $cut = $discount->discount_type === 'percentage' ? $discount->original_amount * min($discount->discount_value, 100) / 100 : min($discount->discount_value, $discount->original_amount); $final = $discount->original_amount - $cut; }
        $discount->update(['status' => $status, 'final_amount' => $final, 'approved_by' => $request->user()->id, 'approved_at' => $status === 'approved' ? now() : null, 'decision_note' => $data['decision_note'] ?? null]);
        if ($status === 'approved') $discount->table()->update(['amount_due' => $final, 'payment_status' => 'approved_discount']);
        return back()->with('success', 'تم حفظ قرار الخصم.');
    }

    public function verify(Request $request, Bazaar $bazaar)
    {
        $this->access($request, $bazaar); abort_unless($bazaar->status === Bazaar::STATUS_POST_EXECUTION, 422);
        abort_if($bazaar->tables()->whereHas('currentDiscount', fn ($q) => $q->where('status', 'pending'))->exists(), 422, 'لا يمكن إكمال المتابعة قبل البت في الخصومات المعلقة.');
        $data = $request->validate(['decision' => ['required', Rule::in(['verify', 'return'])], 'verification_note' => ['nullable', 'string', 'max:2000']]);
        $status = $data['decision'] === 'verify' ? Bazaar::STATUS_VERIFIED : Bazaar::STATUS_EXECUTING;
        $bazaar->update(['status' => $status, 'verified_by' => $request->user()->id, 'verified_at' => $data['decision'] === 'verify' ? now() : null, 'verification_note' => $data['verification_note'] ?? null]);
        return back()->with('success', 'تم حفظ قرار المتابعة.');
    }

    public function close(Request $request, Bazaar $bazaar)
    {
        $this->access($request, $bazaar);
        abort_unless($bazaar->status === Bazaar::STATUS_VERIFIED, 422);
        $bazaar->update(['status' => Bazaar::STATUS_COMPLETED]);

        return back()->with('success', 'تم الاعتماد النهائي وإغلاق البازار.');
    }

    private function sync(Bazaar $bazaar, array $data): void
    {
        $bazaar->tables()->delete();
        foreach ($data['tables'] as $row) $bazaar->tables()->create(Arr::only($row, ['table_number', 'rental_type', 'tenant_name', 'tenant_phone', 'community_organization_id', 'table_liaison_name', 'table_liaison_phone', 'planned_material_description', 'planned_rental_amount', 'liaison_user_id', 'notes']));
        $bazaar->targetGroupSelections()->delete();
        foreach ($data['target_group_ids'] as $id) SubjectTargetGroup::query()->create(['subject_type' => EventSubjectTypes::BAZAAR, 'subject_id' => $bazaar->id, 'target_group_id' => $id]);
        $bazaar->executionNeeds()->delete();
        $types = ExecutionNeedType::query()->availableFor(EventSubjectTypes::BAZAAR)->get()->keyBy('id');
        foreach (collect($data['execution_needs'])->filter(fn ($row) => ($row['selected'] ?? false) || $types->get($row['execution_need_type_id'])?->isMandatoryForBazaar()) as $row) {
            $type = $types->get($row['execution_need_type_id']); if (! $type) continue;
            $details = $type->code === 'invitations' ? json_encode(Arr::only($row, ['invitation_type', 'distribution_channel', 'distribution_other']), JSON_UNESCAPED_UNICODE) : $row['planned_details'];
            SubjectExecutionNeed::query()->create(['subject_type' => EventSubjectTypes::BAZAAR, 'subject_id' => $bazaar->id, 'execution_need_type_id' => $type->id, 'is_required' => true, 'availability' => 'available', 'planned_details' => $details, 'status' => SubjectExecutionNeed::STATUS_PENDING]);
        }
    }

    private function options(Request $request, ?Bazaar $bazaar = null): array
    {
        $branchId = $bazaar?->branch_id ?? $request->user()->branch_id ?? collect($request->user()->scopedBranchIds())->first();
        return ['bazaar' => $bazaar?->load($this->relations()), 'targetGroups' => TargetGroup::query()->active()->forBazaars()->orderBy('sort_order')->get(), 'executionNeedTypes' => ExecutionNeedType::query()->canonical()->availableFor(EventSubjectTypes::BAZAAR)->orderBy('sort_order')->get(), 'users' => User::query()->where('status', 'active')->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereHas('assignedBranches', fn ($b) => $b->whereKey($branchId)))->orderBy('name')->get()];
    }
    private function relations(): array { return ['branch', 'relationsOfficer', 'tables.organization', 'tables.actualOrganization', 'tables.liaison', 'tables.currentDiscount.requester', 'tables.currentDiscount.approver', 'targetGroupSelections.targetGroup', 'executionNeeds.executionNeedType']; }
    private function access(Request $request, Bazaar $bazaar): void { abort_unless($request->user()->hasRole('super_admin') || $request->user()->can('branches.view.all') || $request->user()->hasAccessToScopedBranch((int) $bazaar->branch_id), 403); }

    private function browseFilters(Request $request): array
    {
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->input('month')) ? $request->input('month') : now()->format('Y-m');
        $branchId = filter_var($request->input('branch_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return [
            'search' => trim((string) $request->input('search')),
            'branch_id' => $branchId === false ? null : $branchId,
            'status' => in_array($request->input('status'), Bazaar::STATUSES, true) ? $request->input('status') : null,
            'month' => $month,
            'location_type' => in_array($request->input('location_type'), ['inside', 'outside'], true) ? $request->input('location_type') : null,
            'rental_type' => in_array($request->input('rental_type'), ['individual', 'organization'], true) ? $request->input('rental_type') : null,
        ];
    }

    private function browseQuery(Request $request, array $filters)
    {
        [$year, $month] = array_map('intval', explode('-', $filters['month']));
        $start = Carbon::create($year, $month)->startOfMonth();
        $query = Bazaar::query()->whereBetween('bazaar_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
        if (! $request->user()->hasRole('super_admin') && ! $request->user()->can('branches.view.all')) $query->whereIn('branch_id', $request->user()->scopedBranchIds());
        $query->when($filters['search'], fn ($q, $value) => $q->where(fn ($search) => $search->where('name', 'like', "%{$value}%")->orWhere('location_name', 'like', "%{$value}%")))
            ->when($filters['branch_id'], fn ($q, $value) => $q->where('branch_id', $value))
            ->when($filters['status'], fn ($q, $value) => $q->where('status', $value))
            ->when($filters['location_type'], fn ($q, $value) => $q->where('location_type', $value))
            ->when($filters['rental_type'], fn ($q, $value) => $q->whereHas('tables', fn ($tables) => $tables->where('rental_type', $value)));

        return $query;
    }
}
