<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'monthly_plan_lock_days' => '5',
            'branch_monthly_score_weight_satisfaction' => '40',
            'branch_monthly_score_weight_commitment' => '60',
            'audience_satisfaction_collection_method' => 'internal_form',
            'form5_details_status' => 'draft_v1_prepared',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
