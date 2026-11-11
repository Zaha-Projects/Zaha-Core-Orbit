<?php

namespace App\Modules\Movement\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Modules\Movement\Http\Requests\Fleet\LocationRequest;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Support\MovementAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Saved places (plan §7.5, screen 2b, Revision 4). Manager-only (movement.locations.manage). */
class LocationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Location::class);

        $trashed = $request->boolean('trashed');
        $search = trim((string) $request->query('search', ''));

        $locations = Location::query()
            ->with('branch')
            ->when($trashed, fn ($q) => $q->onlyTrashed())
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%")->orWhere('address', 'like', "%{$search}%")))
            ->orderBy('name_ar')
            ->paginate(25)
            ->withQueryString();

        return view('pages.movement.fleet.locations.index', compact('locations', 'trashed', 'search'));
    }

    public function create()
    {
        $this->authorize('create', Location::class);

        return view('pages.movement.fleet.locations.form', ['location' => new Location(['is_active' => true]), 'branches' => Branch::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function store(LocationRequest $request)
    {
        DB::transaction(function () use ($request) {
            $location = Location::create($request->validated());
            MovementAudit::record('location.created', $location, null, $location->getAttributes());
        });

        return redirect()->route('movement.locations.index')->with('success', __('movement.flash.saved'));
    }

    public function edit(Location $location)
    {
        $this->authorize('update', $location);

        return view('pages.movement.fleet.locations.form', ['location' => $location, 'branches' => Branch::query()->orderBy('name')->get(['id', 'name'])]);
    }

    public function update(LocationRequest $request, Location $location)
    {
        DB::transaction(function () use ($request, $location) {
            $before = $location->getAttributes();
            $location->update($request->validated());
            [$old, $new] = MovementAudit::diff($before, $location);
            if ($new) {
                MovementAudit::record('location.updated', $location, $old, $new);
            }
        });

        return redirect()->route('movement.locations.index')->with('success', __('movement.flash.saved'));
    }

    public function destroy(Location $location)
    {
        $this->authorize('delete', $location);

        DB::transaction(function () use ($location) {
            $location->delete();
            MovementAudit::record('location.deleted', $location, null, ['deleted_at' => (string) $location->deleted_at]);
        });

        return redirect()->route('movement.locations.index')->with('success', __('movement.flash.deleted'));
    }

    public function restore(Location $location)
    {
        $this->authorize('restore', $location);

        DB::transaction(function () use ($location) {
            $location->restore();
            MovementAudit::record('location.restored', $location);
        });

        return redirect()->route('movement.locations.index')->with('success', __('movement.flash.restored'));
    }
}
