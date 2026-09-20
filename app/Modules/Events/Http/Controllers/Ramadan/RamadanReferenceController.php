<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
            ->orderBy('name')->limit(15)->get()->map(fn (Model $record) => $this->payload($record));

        return response()->json(['data' => $items]);
    }

    private function store(Request $request, string $model): JsonResponse
    {
        $branchId = $this->branchId($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'google_maps_url' => ['nullable', 'url', 'max:2048'],
        ]);
        $normalized = $this->normalize($data['name']);
        $duplicate = $model::query()->where('branch_id', $branchId)->get(['id', 'name'])->first(
            fn (Model $record) => $this->normalize($record->name) === $normalized
        );
        if ($duplicate) {
            throw ValidationException::withMessages(['name' => 'يوجد سجل بهذا الاسم في فرعك بالفعل. اختره من نتائج البحث.']);
        }

        $record = $model::query()->create($data + ['branch_id' => $branchId, 'is_active' => true]);

        return response()->json($this->payload($record), 201);
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
        return mb_strtolower(trim(preg_replace('/[\s\x{0640}]+/u', ' ', $name)));
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
