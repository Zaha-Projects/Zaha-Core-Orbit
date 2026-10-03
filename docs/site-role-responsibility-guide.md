# Site role and responsibility guide — source reference

**Audit date:** 2026-09-27  
**User-facing page:** `/dashboard/site-guide` (`site-guide.index`)  
**Maintainable prose source:** `config/site_role_guide.php`

## Authority and method

The active role catalogue comes from `RolesSeeder::roleDefinitions()`. Permissions come from `RolePermissionSeeder`, `RolesSeeder`, and the evaluation workflow seeders; route middleware, policies/controllers, and sidebar guards were used to constrain the prose. `CompleteRolePermissionSeeder` orchestrates the established catalogues and evaluation additions. Seeder assignment is additive, so the guide describes canonical intended grants; the admin-only technical table displays the permissions actually loaded from the database for support diagnosis.

## Complete active role inventory

| Arabic role | Technical key | Main purpose |
|---|---|---|
| مدير النظام | `super_admin` | complete configuration, access, reference, reporting, and oversight |
| المدير التنفيذي | `executive_manager` | executive approvals and institutional oversight |
| مدير البرامج | `programs_manager` | cross-branch program/plan visibility and reporting |
| مدير علاقات رئيسي | `relations_manager` | relations planning, approvals, and evaluation configuration |
| رئيس فرع | `supervisor` | branch approvals, Ramadan monitoring decision, final closure |
| مسؤول العلاقات | `relations_officer` | planning, execution, actual data, and correction |
| مسؤول المتابعة | `followup_officer` | Monthly verification/evaluation and Ramadan monitoring review |
| مسؤول التقييم | `evaluation_officer` | broad evaluation and post-execution visibility/management |
| مسؤول التقييم والمتابعة (عرض) | `evaluation_followup_viewer` | read-only evaluation/follow-up visibility |
| سكرتير الورش | `workshops_secretary` | workshop/agenda participation and assigned approvals |
| منسق الفروع | `branch_coordinator` | branch coordination and assigned plan approvals |
| ضابط التطوع | `volunteer_coordinator` | volunteer-related Monthly details and feedback |
| مدير الوحدة الإدارية | `administrative_unit_manager` | administrative execution-needs contribution |
| رئيس قسم الاتصال | `communication_head` | communications requests, media, and assigned approvals |
| مسؤول المالية | `finance_officer` | finance operational screens and reports |
| مسؤول الصيانة | `maintenance_officer` | maintenance requests, details, and attachments |
| مسؤول النقل | `transport_officer` | vehicles, drivers, trips, and transport requests |
| مستعرض التقارير | `reports_viewer` | read-only reporting, KPI, and enterprise analytics |
| موظف | `staff` | staff agenda and Monthly visibility |
| مدير الحركة | `movement_manager` | movement oversight |
| محرر الحركة | `movement_editor` | movement record editing through transport routes |
| مستعرض الحركة | `movement_viewer` | read-only movement visibility |

## Detailed responsibility source

Every role has a dedicated Arabic entry in `config/site_role_guide.php` with:

- operational purpose;
- modules and pages;
- main responsibilities;
- Monthly responsibilities;
- Ramadan responsibilities;
- input received;
- output/handoff;
- restrictions.

The user-facing page renders this single source as searchable role cards. It does not repeat curated descriptions in Blade. The technical role key is shown on every card. Only Super Admin sees the technical permissions table, whose permission names are loaded from current role relationships rather than copied into prose.

## Critical role handoffs

### Monthly Activities

Relations Officer/branch actor → creates, edits, and submits the plan  
Workflow approvers (including branch or organizational approvers configured by source) → approve/return  
Branch completion actor → records post-execution actuals  
Supervisor → approves/returns post-execution and closes the Monthly activity  
Follow-up Officer → verifies submitted fields and resolves incorrect verification metadata  
Follow-up Officer → submits evaluation after all verifications resolve  
Relations users → receive the evaluation result

### Ramadan Iftars

Relations Officer → plans and submits  
Configured approvers → approve/return planning  
Relations Officer → starts execution, records actuals, completes post-execution  
Follow-up Officer → selects monitoring method, compares planned/actual, documents mismatch, submits  
Supervisor → approves or returns monitoring  
Relations Officer → corrects source actual data when returned  
Follow-up Officer → reviews and resubmits  
Supervisor → final closure

### Annual Agenda

Relations planning roles → create/update  
Configured workflow roles → approve/return  
Authorized roles → update participation  
Monthly Activities may be synchronized from agenda context; Annual Agenda itself has no Follow-up post-execution stage.

## Global responsibility matrix

| Module/action | Relations Officer | Follow-up | Supervisor | Relations Manager | Executive Manager | Super Admin |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Create Monthly plan | ✓ | — | permitted by current catalogue | ✓ | — | oversight |
| Approve Monthly plan | — | — | ✓ | ✓ | ✓ | ✓ |
| Monthly post-execution | ✓/branch owner | verify | approve/return | visibility | visibility | oversight |
| Monthly evaluation | visibility | ✓ | visibility | configure/view | view | oversight |
| Create Ramadan Iftar | ✓ | — | — | ✓ | — | ✓ |
| Approve Ramadan plan | — | — | ✓ | ✓ | ✓ | ✓ |
| Ramadan execution/actuals | ✓ | review only | — | — | — | ✓ |
| Ramadan monitoring | — | ✓ | approve/return | — | — | ✓ |
| Ramadan closure | — | — | ✓ | — | — | ✓ |
| Access/roles/workflows | — | — | — | — | — | ✓ |
| Reports | limited | ✓ by permission | limited | ✓ | ✓ | ✓ |

“Permitted by current catalogue” distinguishes a technical grant from the primary operational owner; dynamic workflow and controller authorization still determine the actionable step.

## Admin distinction

There is no canonical `admin` role in `RolesSeeder`. Some legacy routes accept `admin`, but the active catalogue defines `super_admin`. The guide therefore does not invent a normal Admin role. `super_admin` receives all permissions and owns users, roles, branches, workflows, settings, evaluation configuration, reports, Ramadan periods/guidance/dashboard settings, and Ramadan/general reference data including meal types, mobilization methods, organizations, and local communities.

## Maintenance rules

1. Change operational prose only in `config/site_role_guide.php`.
2. Change canonical role names in `RolesSeeder` and update the matching guide key.
3. Never use the guide as authorization; middleware, policies, permissions, scopes, and services remain authoritative.
4. Keep sensitive technical permission names restricted to Super Admin.
5. Run the static role/permission searches after catalogue changes.
