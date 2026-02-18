<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;

class ApprovalsController extends Controller
{
    public function index()
    {
        $steps = [
            [
                'title' => __('app.roles.super_admin.approvals.steps.agenda.title'),
                'items' => __('app.roles.super_admin.approvals.steps.agenda.items'),
            ],
            [
                'title' => __('app.roles.super_admin.approvals.steps.monthly_plan.title'),
                'items' => __('app.roles.super_admin.approvals.steps.monthly_plan.items'),
            ],
            [
                'title' => __('app.roles.super_admin.approvals.steps.maintenance.title'),
                'items' => __('app.roles.super_admin.approvals.steps.maintenance.items'),
            ],
            [
                'title' => __('app.roles.super_admin.approvals.steps.transport.title'),
                'items' => __('app.roles.super_admin.approvals.steps.transport.items'),
            ],
            [
                'title' => __('app.roles.super_admin.approvals.steps.bookings.title'),
                'items' => __('app.roles.super_admin.approvals.steps.bookings.items'),
            ],
        ];

        return view('pages.access.approvals.index', compact('steps'));
    }
}
