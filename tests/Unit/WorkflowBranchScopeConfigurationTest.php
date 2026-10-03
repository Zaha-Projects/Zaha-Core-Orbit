<?php

namespace Tests\Unit;

use Tests\TestCase;

class WorkflowBranchScopeConfigurationTest extends TestCase
{
    public function test_branch_scope_is_module_configuration_not_a_ramadan_service_hard_code(): void
    {
        $this->assertSame(
            ['relations_officer', 'supervisor', 'branch_coordinator'],
            config('workflows.branch_scoped_modules.monthly_activities')
        );
        $this->assertSame(
            ['relations_officer', 'supervisor', 'branch_coordinator'],
            config('workflows.branch_scoped_modules.ramadan_iftars')
        );
        $this->assertNull(config('workflows.branch_scoped_modules.agenda'));
    }
}
