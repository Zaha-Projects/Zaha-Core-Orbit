<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RamadanReferenceController extends Controller
{
    public function organizations(Request $request): JsonResponse
    {
        return $this->search($request, CommunityOrganization::class);
    }

    public function localCommunities(Request $request): JsonResponse
    {
        return $this->search($request, LocalCommunity::class);
    }

    public function storeOrganization(Request $request): JsonResponse
    {
        return $this->store($request, CommunityOrganization::class);
    }

    public function storeLocalCommunity(Request $request): JsonResponse
    {
        return $this->store($request, LocalCommunity::class);
    }

    private function search(Request $request, string $model): JsonResponse
    {
        $branchId = $this->branchId($request);
        $term = trim(preg_replace('/\s+/u', ' ', (string) $request->query('q', '')));
        $items = $model::query()->select(['id', 'name', 'contact_name', 'contact_phone', 'location_name', 'address', 'google_maps_url'])
            ->where('branch_id', $branchId)->active()
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%'))
            ->when($term !== '', fn ($query) => $query->orderByRaw('CASE WHEN name = ? THEN 0 WHEN name LIKE ? THEN 1 ELSE 2 END', [$term, $term.'%']))
            ->orderBy('name')->limit(15)->get()->map(fn (Model $record) => $this->payload($record));

        return response()->json(['data' => $items]);
    }

    private function store(Request $request, string $model): JsonResponse
    {
        $branchId = $this->branchId($request);
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'min:7', 'max:25', 'regex:/^[0-9+()\-\s]+$/'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'google_maps_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'name.required' => 'الاسم مطلوب لإضافة السجل.',
            'contact_phone.required' => 'رقم التواصل مطلوب لإضافة السجل.',
            'contact_phone.min' => 'رقم التواصل قصير جدًا.',
            'contact_phone.max' => 'رقم التواصل طويل جدًا.',
            'contact_phone.regex' => 'أدخل رقم تواصل صالحًا باستخدام الأرقام والمسافات و + أو - أو الأقواس فقط.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }
        $data = $validator->validated();
        $normalized = $this->normalize($data['name']);
        $duplicate = $model::query()->where('branch_id', $branchId)->get(['id', 'name'])->first(
            fn (Model $record) => $this->normalize($record->name) === $normalized
        );
        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد سجل بنفس الاسم في هذا الفرع. يمكنك اختياره من نتائج البحث.',
                'errors' => ['name' => ['يوجد سجل بنفس الاسم في هذا الفرع. يمكنك اختياره من نتائج البحث.']],
            ], 422);
        }

        $record = $model::query()->create($data + ['branch_id' => $branchId, 'is_active' => true]);

        return response()->json(['success' => true, 'data' => $this->payload($record)], 201);
    }

    private function branchId(Request $request): int
    {
        $user = $request->user();
        abort_unless($user && ($user->hasAnyRole(['relations_manager', 'relations_officer', 'super_admin']) || $user->can('ramadan_iftars.create')), 403);
        $branchId = $user->branch_id ?? collect($user->scopedBranchIds())->first();
        abort_unless($branchId, 422, 'لا يوجد فرع مخول للمستخدم.');

        return (int) $branchId;
    }

    private function normalize(string $name): string
    {
        $withoutTatweel = str_replace("ـ", '', $name);

        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $withoutTatweel)));
    }

    private function payload(Model $record): array
    {
        return [
            'id' => $record->getKey(), 'name' => $record->name, 'label' => $record->name,
            'contact_name' => $record->contact_name, 'contact_phone' => $record->contact_phone,
            'location_name' => $record->location_name, 'address' => $record->address,
            'google_maps_url' => $record->google_maps_url,
        ];
    }
}
