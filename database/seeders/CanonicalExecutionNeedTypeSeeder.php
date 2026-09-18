<?php

namespace Database\Seeders;

use App\Modules\Events\Models\ExecutionNeedType;
use Illuminate\Database\Seeder;

class CanonicalExecutionNeedTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ExecutionNeedType::CANONICAL_DEFINITIONS as $code => $definition) {
            ExecutionNeedType::query()->insertOrIgnore(
                [
                    'code' => $code, 'created_at' => now(), 'updated_at' => now(),
                    'name' => $definition['name'],
                    'sort_order' => (array_search($code, ExecutionNeedType::canonicalCodes(), true) + 1) * 10,
                    'is_active' => true,
                    'is_canonical' => true,
                    'is_monthly_activity' => $definition['monthly'] ?? true,
                    'is_ramadan_iftar' => $definition['ramadan'] ?? false,
                    'mandatory_for_monthly' => $definition['mandatory_monthly'] ?? false,
                    'mandatory_for_ramadan' => $definition['mandatory_ramadan'] ?? false,
                ]
            );
            ExecutionNeedType::query()->where('code', $code)->where('is_canonical', false)->whereNull('scope_configured_at')
                ->update([
                    'is_canonical' => true,
                    'is_monthly_activity' => $definition['monthly'] ?? true,
                    'is_ramadan_iftar' => $definition['ramadan'] ?? false,
                ]);
            // A reference-code upgrade must not replace an explicit scope choice.
            ExecutionNeedType::query()->where('code', $code)->where('is_canonical', false)
                ->whereNotNull('scope_configured_at')->update(['is_canonical' => true]);
        }
    }
}
