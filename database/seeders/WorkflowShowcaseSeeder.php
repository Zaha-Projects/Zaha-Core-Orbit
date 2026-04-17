<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorkflowShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AgendaWorkflowShowcaseSeeder::class,
            MonthlyWorkflowShowcaseSeeder::class,
        ]);
    }
}
