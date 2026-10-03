<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use Illuminate\Foundation\Http\FormRequest;

class StoreRamadanIftarChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $iftar = $this->route('ramadanIftar');
        return $user && $iftar && ($user->hasRole('super_admin') || ($user->hasRole('relations_officer') && $user->can('ramadan_iftars.change_request.create') && ($user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id))));
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:2000']];
    }
}
