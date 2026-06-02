<?php

namespace App\Http\Controllers;

use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityEvaluationResponse;
use App\Models\WorkflowLog;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load(['branch', 'assignedBranches', 'roles']);

        $emailEditingEnabled = $this->profileEmailEditingEnabled();
        $canManageEmailEditing = $this->canManageProfileEmailEditing($request->user());

        return view('profile.show', [
            'user' => $user,
            'stats' => Cache::remember($this->statsCacheKey($user->id), now()->addMinutes(15), fn (): array => $this->buildStats($user->id)),
            'evaluations' => Cache::remember($this->evaluationsCacheKey($user->id), now()->addMinutes(15), fn (): array => $this->buildEvaluations($user->id)),
            'emailEditingEnabled' => $emailEditingEnabled,
            'canEditProfileEmail' => $emailEditingEnabled || $canManageEmailEditing,
            'canManageEmailEditing' => $canManageEmailEditing,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $canEditEmail = $this->profileEmailEditingEnabled() || $this->canManageProfileEmailEditing($user);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
        ];

        if ($canEditEmail) {
            $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }

        $data = $request->validate($rules);

        $user->forceFill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        if ($canEditEmail) {
            $user->email = $data['email'];
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->saveOrFail();

        Cache::forget($this->statsCacheKey($user->id));
        Cache::forget($this->evaluationsCacheKey($user->id));

        return back()->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updateEmailEditing(Request $request): RedirectResponse
    {
        abort_unless($this->canManageProfileEmailEditing($request->user()), 403);

        $data = $request->validate([
            'allow_profile_email_edit' => ['nullable', 'boolean'],
        ]);

        Setting::query()->updateOrCreate(
            ['key' => 'allow_profile_email_edit'],
            ['value' => (string) (int) ($data['allow_profile_email_edit'] ?? false)]
        );

        return back()->with('success', ($data['allow_profile_email_edit'] ?? false)
            ? 'تم السماح للمستخدمين بتعديل البريد الإلكتروني من الملف الشخصي.'
            : 'تم قفل تعديل البريد الإلكتروني من الملف الشخصي.');
    }

    private function profileEmailEditingEnabled(): bool
    {
        return Setting::valueOf('allow_profile_email_edit', '0') === '1';
    }

    private function canManageProfileEmailEditing(?User $user): bool
    {
        return (bool) ($user?->hasRole('super_admin') || $user?->can('users.manage'));
    }

    private function buildStats(int $userId): array
    {
        $profileUser = User::query()->with('assignedBranches')->find($userId);
        $branchIds = $profileUser?->scopedBranchIds() ?? [];

        return [
            'created_monthly_activities' => MonthlyActivity::query()->where('created_by', $userId)->count(),
            'assigned_branch_activities' => MonthlyActivity::query()
                ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds), fn ($query) => $query->whereRaw('1 = 0'))
                ->count(),
            'completed_branch_activities' => MonthlyActivity::query()
                ->when($branchIds !== [], fn ($query) => $query->whereIn('branch_id', $branchIds), fn ($query) => $query->whereRaw('1 = 0'))
                ->where('status', 'completed')
                ->count(),
            'workflow_actions' => WorkflowLog::query()->where('acted_by', $userId)->count(),
        ];
    }

    private function buildEvaluations(int $userId): array
    {
        $assigned = MonthlyActivity::query()->where('evaluation_assigned_user_id', $userId);
        $responses = MonthlyActivityEvaluationResponse::query()->where('created_by', $userId);

        return [
            'assigned_count' => (clone $assigned)->count(),
            'completed_count' => (clone $assigned)->whereNotNull('evaluation_score')->count(),
            'average_score' => round((float) (clone $responses)->whereNotNull('score')->avg('score'), 2),
            'responses_count' => (clone $responses)->count(),
            'latest' => (clone $assigned)
                ->whereNotNull('evaluation_score')
                ->latest('updated_at')
                ->take(5)
                ->get(['id', 'title', 'evaluation_score', 'updated_at'])
                ->map(fn (MonthlyActivity $activity): array => [
                    'title' => $activity->title,
                    'score' => $activity->evaluation_score,
                    'date' => optional($activity->updated_at)->format('Y-m-d'),
                ])
                ->all(),
        ];
    }

    private function statsCacheKey(int $userId): string
    {
        return 'profile.stats.user.' . $userId;
    }

    private function evaluationsCacheKey(int $userId): string
    {
        return 'profile.evaluations.user.' . $userId;
    }
}
