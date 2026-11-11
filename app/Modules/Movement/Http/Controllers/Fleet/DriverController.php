<?php

namespace App\Modules\Movement\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Movement\Http\Requests\Fleet\DriverRequest;
use App\Modules\Movement\Http\Requests\Fleet\LinkDriverUserRequest;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Support\MovementAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Driver management and user linking (plan §7.3, §13.3, screen 3). Personal data is manager-only (D7). */
class DriverController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Driver::class);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(Driver::STATUSES)],
            'search' => ['nullable', 'string', 'max:100'],
            'trashed' => ['nullable', 'boolean'],
        ]);

        $drivers = Driver::query()
            ->with('user')
            ->when($filters['trashed'] ?? false, fn ($q) => $q->onlyTrashed())
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('employment_status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('licence_number', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('pages.movement.fleet.drivers.index', [
            'drivers' => $drivers,
            'filters' => $filters,
            'needsVerification' => Driver::query()->where('employment_status', Driver::STATUS_PENDING_VERIFICATION)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Driver::class);

        return view('pages.movement.fleet.drivers.form', ['driver' => new Driver(['employment_status' => Driver::STATUS_ACTIVE])]);
    }

    public function store(DriverRequest $request)
    {
        $driver = DB::transaction(function () use ($request) {
            $driver = Driver::create($request->validated());
            MovementAudit::record('driver.created', $driver, null, $driver->getAttributes());

            return $driver;
        });

        return redirect()->route('movement.drivers.show', $driver)->with('success', __('movement.flash.saved'));
    }

    public function show(Request $request, Driver $driver)
    {
        $this->authorize('view', $driver);

        $search = trim((string) $request->query('user_search', ''));
        $candidates = collect();
        if ($search !== '' && $request->user()->can('link', $driver) && $driver->user_id === null) {
            // Searchable picker: active users not linked to any driver (including soft-deleted drivers).
            $candidates = User::query()
                ->where('status', 'active')
                ->whereNotIn('id', Driver::withTrashed()->whereNotNull('user_id')->select('user_id'))
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'email']);
        }

        return view('pages.movement.fleet.drivers.show', [
            'driver' => $driver->load('user'),
            'windows' => $driver->unavailabilities()->orderByDesc('starts_at')->limit(50)->get(),
            'windowTypes' => Unavailability::TYPES,
            'userSearch' => $search,
            'candidates' => $candidates,
        ]);
    }

    public function edit(Driver $driver)
    {
        $this->authorize('update', $driver);

        return view('pages.movement.fleet.drivers.form', ['driver' => $driver]);
    }

    public function update(DriverRequest $request, Driver $driver)
    {
        DB::transaction(function () use ($request, $driver) {
            $before = $driver->getAttributes();
            $driver->update($request->validated());
            [$old, $new] = MovementAudit::diff($before, $driver);
            if (array_key_exists('employment_status', $new)) {
                MovementAudit::record('driver.status_changed', $driver, ['employment_status' => $old['employment_status'] ?? null], ['employment_status' => $new['employment_status']]);
            }
            if ($rest = array_diff_key($new, ['employment_status' => true])) {
                MovementAudit::record('driver.updated', $driver, array_intersect_key($old, $rest), $rest);
            }
        });

        return redirect()->route('movement.drivers.show', $driver)->with('success', __('movement.flash.saved'));
    }

    public function link(LinkDriverUserRequest $request, Driver $driver)
    {
        DB::transaction(function () use ($request, $driver) {
            $driver->update(['user_id' => (int) $request->validated()['user_id']]);
            MovementAudit::record('driver.user_linked', $driver, ['user_id' => null], ['user_id' => $driver->user_id]);
        });

        return redirect()->route('movement.drivers.show', $driver)->with('success', __('movement.flash.linked'));
    }

    /** Unlinking keeps the driver row and all history (§13.3). */
    public function unlink(Driver $driver)
    {
        $this->authorize('link', $driver);

        if ($driver->user_id !== null) {
            DB::transaction(function () use ($driver) {
                $old = $driver->user_id;
                $driver->update(['user_id' => null]);
                MovementAudit::record('driver.user_unlinked', $driver, ['user_id' => $old], ['user_id' => null]);
            });
        }

        return redirect()->route('movement.drivers.show', $driver)->with('success', __('movement.flash.unlinked'));
    }

    public function destroy(Driver $driver)
    {
        $this->authorize('delete', $driver);

        DB::transaction(function () use ($driver) {
            $driver->delete();
            MovementAudit::record('driver.deleted', $driver, null, ['deleted_at' => (string) $driver->deleted_at]);
        });

        return redirect()->route('movement.drivers.index')->with('success', __('movement.flash.deleted'));
    }

    public function restore(Driver $driver)
    {
        $this->authorize('restore', $driver);

        DB::transaction(function () use ($driver) {
            $driver->restore();
            MovementAudit::record('driver.restored', $driver);
        });

        return redirect()->route('movement.drivers.show', $driver)->with('success', __('movement.flash.restored'));
    }
}
