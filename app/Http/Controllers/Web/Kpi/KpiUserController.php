<?php

namespace App\Http\Controllers\Web\Kpi;

use App\Http\Controllers\Concerns\StreamsCsv;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Kpi\Concerns\ParsesKpiFilters;
use App\Models\User;
use App\Services\Kpi\KpiRegistry;
use App\Services\Kpi\KpiReportService;
use App\Services\Kpi\KpiScope;
use App\Services\Kpi\KpiUserPerformanceService;
use Illuminate\Http\Request;

/**
 * User performance (Phase 5).
 *
 * Access:
 * - self: every authenticated user, own actions only (My Profile → الأداء and /profile/performance/*);
 * - others: super_admin or kpi.users.view, target inside the viewer's branch scope (KpiScope::canViewUser);
 *   the viewer's branch scope also limits which of the target's actions are counted;
 * - CSV: own actions always; others' actions and organisational reports need kpi.export.
 */
class KpiUserController extends Controller
{
    use ParsesKpiFilters;
    use StreamsCsv;

    private KpiRegistry $registry;
    private KpiUserPerformanceService $performance;
    private KpiReportService $reports;

    public function __construct(KpiRegistry $registry, KpiUserPerformanceService $performance, KpiReportService $reports)
    {
        $this->registry = $registry;
        $this->performance = $performance;
        $this->reports = $reports;
    }

    /** Management users page: same-role comparison (no ranking, no overall score). */
    public function index(Request $request)
    {
        abort_unless(KpiScope::canViewUsers($request->user()), 403);
        $filters = $this->filters($request, array_keys($this->registry->modules()));
        $families = $this->performance->families();
        $family = is_string($request->query('role')) && array_key_exists($request->query('role'), $families) ? $request->query('role') : array_key_first($families);
        $userFilter = filter_var($request->query('user'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        $sortable = array_merge(['user'], $families[$family]['comparison']);
        $sort = in_array($request->query('sort'), $sortable, true) ? $request->query('sort') : 'user';
        $direction = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $comparison = $this->performance->comparison($family, $filters['period'], $filters['scope'], $userFilter, $sort, $direction,
            $filters['module'] ? [$filters['module']] : null);
        $userOptions = $this->performance->candidates($families[$family]['roles'], $filters['scope']);

        return view('pages.kpi.users', [
            'filters' => $filters,
            'families' => $families,
            'family' => $family,
            'userFilter' => $userFilter,
            'userOptions' => $userOptions,
            'sort' => $sort,
            'direction' => $direction,
            'canExport' => KpiScope::canExport($request->user()),
        ] + $comparison + $this->filterOptions($filters));
    }

    public function show(Request $request, int $user)
    {
        [$target, $filters] = $this->authorizedTarget($request, $user);

        return view('pages.kpi.user', [
            'filters' => $filters,
            'performance' => $this->performance->performance($target, $filters['period'], $filters['scope']),
            'actionsRoute' => 'kpi.users.actions',
            'actionsParameters' => ['user' => $target->id],
            'canExport' => $this->canExportFor($request->user(), $target),
        ] + $this->filterOptions($filters));
    }

    public function actions(Request $request, int $user, string $action)
    {
        [$target, $filters] = $this->authorizedTarget($request, $user);

        return $this->actionsView($request, $target, $action, $filters, 'kpi.users.actions', ['user' => $target->id]);
    }

    public function actionsExport(Request $request, int $user, string $action)
    {
        [$target, $filters] = $this->authorizedTarget($request, $user);
        abort_unless($this->canExportFor($request->user(), $target), 403);

        return $this->actionsCsv($request, $target, $action, $filters);
    }

    /** Self routes (My Profile): no kpi.view required, own actions in every branch. */
    public function selfActions(Request $request, string $action)
    {
        $user = $request->user();
        $filters = $this->filters($request, [], null, KpiScope::forSelf($user));

        return $this->actionsView($request, $user, $action, $filters, 'profile.performance.actions', []);
    }

    public function selfActionsExport(Request $request, string $action)
    {
        $user = $request->user();
        $filters = $this->filters($request, [], null, KpiScope::forSelf($user));

        return $this->actionsCsv($request, $user, $action, $filters);
    }

    /* ------------------------------------------------------------------ helpers */

    /** 403 for out-of-scope users (direct-ID access); self viewing uses the self scope. */
    private function authorizedTarget(Request $request, int $userId): array
    {
        $viewer = $request->user();
        $target = User::query()->with(['roles', 'branch', 'assignedBranches'])->findOrFail($userId);
        abort_unless(KpiScope::canViewUser($viewer, $target), 403);

        $isSelf = (int) $viewer->id === (int) $target->id;
        $filters = $this->filters($request, [], null, $isSelf ? KpiScope::forSelf($viewer) : null);

        return [$target, $filters];
    }

    private function canExportFor(User $viewer, User $target): bool
    {
        return (int) $viewer->id === (int) $target->id || KpiScope::canExport($viewer);
    }

    private function actionsView(Request $request, User $target, string $action, array $filters, string $route, array $routeParameters)
    {
        abort_unless(KpiReportService::userActionExists($action), 404);
        $module = KpiReportService::userActionModuleFor($action, $request->query('module'));
        $filter = KpiReportService::userActionFilterFor($action, $request->query('filter'));

        return view('pages.kpi.user-actions', [
            'filters' => $filters,
            'target' => $target,
            'report' => $this->reports->userActions($action, $module, $filter, $target, $filters['period'], $filters['scope']),
            'route' => $route,
            'routeParameters' => $routeParameters + ['action' => $action],
            'canExport' => $this->canExportFor($request->user(), $target),
            'isSelf' => (int) $request->user()->id === (int) $target->id,
        ] + $this->filterOptions($filters));
    }

    private function actionsCsv(Request $request, User $target, string $action, array $filters)
    {
        abort_unless(KpiReportService::userActionExists($action), 404);
        $module = KpiReportService::userActionModuleFor($action, $request->query('module'));
        $filter = KpiReportService::userActionFilterFor($action, $request->query('filter'));
        $query = $this->reports->userActionsQuery($action, $module, $filter, $target, $filters['period'], $filters['scope']);

        return $this->csvResponse(
            'kpi-user-'.$target->id.'-'.$action.'-'.$module.'-'.$filters['year'].($filters['month'] ? '-'.$filters['month'] : '').'.csv',
            array_values(KpiReportService::USER_ACTION_COLUMNS),
            $query ? $query->cursor()->map($this->reports->userActionsCsvMapper()) : [],
            true
        );
    }
}
