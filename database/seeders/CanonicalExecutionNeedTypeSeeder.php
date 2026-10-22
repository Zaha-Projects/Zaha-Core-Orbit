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
                    'module_config' => json_encode([
                        'monthly_activity' => ['available' => $definition['monthly'] ?? true, 'required' => $definition['mandatory_monthly'] ?? false],
                        'ramadan_iftar' => ['available' => $definition['ramadan'] ?? false, 'required' => $definition['mandatory_ramadan'] ?? false],
                        'bazaar' => ['available' => $definition['monthly'] ?? true, 'required' => false],
                    ], JSON_UNESCAPED_UNICODE),
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

            $need = ExecutionNeedType::query()->where('code', $code)->first();
            if ($need) {
                $moduleConfig = is_array($need->module_config) ? $need->module_config : (json_decode($need->module_config ?: '{}', true) ?: []);
                if (! array_key_exists('bazaar', $moduleConfig)) {
                    $moduleConfig['bazaar'] = ['available' => $definition['monthly'] ?? true, 'required' => false];
                    $need->update(['module_config' => $moduleConfig]);
                }
            }
        }
    }
}
