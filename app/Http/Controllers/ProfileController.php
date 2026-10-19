<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
}
