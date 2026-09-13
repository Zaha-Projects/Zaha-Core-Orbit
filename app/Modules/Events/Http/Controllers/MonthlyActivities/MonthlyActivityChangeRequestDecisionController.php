<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Http\Controllers\Web\MonthlyActivities\MonthlyActivitiesApprovalsController;

/**
 * Monthly edit/delete change-request decisions.
 *
 * Phase 2.1 deliberately inherits the proven legacy implementation so public
 * behavior remains byte-for-byte stable while route ownership is separated.
 */
class MonthlyActivityChangeRequestDecisionController extends MonthlyActivitiesApprovalsController
{
}
