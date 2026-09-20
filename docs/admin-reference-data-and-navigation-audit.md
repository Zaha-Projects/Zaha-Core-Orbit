# Admin reference data and navigation audit

Audited 2026-09-20 from models, migrations, seeders, controllers, routes, consuming forms, and both application sidebars. “Admin-managed” below means source proves that operators can safely change labels/order/availability. Structural enums, workflow states, and transactional tables are intentionally not CRUD.

## Inventory

| Dataset | Table / model | Module | Classification | Seeder | Admin page / actions | Activation / sort | Consumers | Missing management after this slice? |
|---|---|---|---|---|---|---|---|---|
| Branches | `branches` / `Branch` | Shared | A — Admin-managed lookup | `BranchSeeder` | Branch Admin: list/add/edit | business status; no reference sort | users, all branch-scoped modules | No |
| Departments | `departments` / `Department` | Shared Events | B — Shared reference | `DepartmentSeeder` | General reference page: add/edit | `is_active`, `sort_order` | agenda/monthly ownership | No |
| Department units | `department_units` / `DepartmentUnit` | Shared Events | B — Shared reference | `DepartmentUnitSeeder` | General reference page: add/edit | `is_active`, `sort_order` | agenda participation/execution | No |
| Event categories | `event_categories` / `EventCategory` | Shared Events | B — Shared reference | `EventCategorySeeder` | General reference page: add/edit | `is_active`, `sort_order` | agenda/monthly forms | No |
| Target groups | `target_groups` / `TargetGroup` | Shared Events | B — Shared reference | `TargetGroupSeeder` | General and Ramadan reference pages | `is_active`, `sort_order`, applicability | monthly/Ramadan targeting | No |
| Beneficiary segments | `beneficiary_segments` / `BeneficiarySegment` | Shared Events | B — Shared reference | `BeneficiarySegmentSeeder` | Ramadan reference page: add/edit | `is_active`, `sort_order` | target/volunteer planning | No |
| Execution need types | `execution_need_types` / `ExecutionNeedType` | Shared Events | B — Shared reference | `CanonicalExecutionNeedTypeSeeder` | Ramadan reference page: add/edit | `is_active`, `sort_order`, usage scope | monthly/Ramadan planning | No |
| Event status labels | `event_status_lookups` / `EventStatusLookup` | Shared Events | B — display lookup | `EventStatusLookupSeeder` | General reference page: add/edit | `is_active`, `sort_order` | status presentation | No |
| Evaluation questions | `evaluation_questions` / `EvaluationQuestion` | Evaluation | C — Module lookup | `EvaluationQuestionSeeder` | General reference page: add/edit | `is_active`, `sort_order` | activity evaluations | No |
| Zaha Time options | `zaha_time_options` / `ZahaTimeOption` | Finance/events | C — Module lookup | `ZahaTimeOptionSeeder` | General reference page: add/edit | `is_active`, `sort_order` | Zaha Time booking | No |
| Organizations/centres | `community_organizations` / `CommunityOrganization` | Ramadan | C — Module lookup | `CommunityOrganizationSeeder` | Ramadan reference page: add/edit | `is_active`; name order | Iftar host picker | No |
| Local communities | `local_communities` / `LocalCommunity` | Ramadan | C — Module lookup | `LocalCommunitySeeder` | Ramadan reference page: add/edit | `is_active`; name order | Iftar host picker | No |
| Mobilization methods | `mobilization_methods` / `MobilizationMethod` | Ramadan | C — Module lookup | `MobilizationMethodSeeder` | Dedicated Ramadan page: add/edit/toggle | `is_active`, `sort_order` | Iftar planning | No |
| Monitoring methods | `monitoring_methods` / `MonitoringMethod` | Ramadan/events | C — Module lookup | `MonitoringMethodSeeder` | Ramadan reference page: add/edit | `is_active`, `sort_order` | monitoring reports | No |
| Ramadan periods | `ramadan_periods` / `RamadanPeriod` | Ramadan | G — Operational configuration | `RamadanPeriodSeeder` | Ramadan settings/versioned actions | explicit confirm/activate | Ramadan date rules | No |
| Ramadan guidance | `event_guidance_versions` / `EventGuidanceVersion` | Ramadan | G — Versioned configuration | `RamadanIftarGuidanceSeeder` | Ramadan settings/versioned actions | immutable publish/archive | acknowledgement and planning | No |
| Dashboard visibility | `settings` / `Setting` key `ramadan_dashboard_enabled` | Ramadan | G — Feature configuration | `RamadanDashboardSettingSeeder` | Ramadan settings switch | enabled/disabled | main dashboard | No |
| Workflows and steps | `workflows`, `workflow_steps` | Shared | G — Workflow configuration | `WorkflowSeeder` | Existing workflow Admin | enabled/step order | approvals | No |
| Roles/permissions | Spatie ACL tables | Shared | G — Access configuration | role/permission seeders | Existing access Admin | ACL semantics | authorization | No |

## Explicitly excluded candidates

Structural PHP arrays/constants (`RamadanIftar` host/location states, gift/shield types, meal item types, event subject aliases), lifecycle/request/workflow statuses, polymorphic identity metadata, and approval actions are D/E and remain code-owned. Vehicles, drivers, users, centers/branches, activities, Iftars, donations, bookings, payments, requests, reports, attachments, attendance, monitoring reports, KPI rows, and correspondence are transactional F (or full business entities) and are not lookup CRUD. Configuration keys without a finite selectable catalogue remain on their existing focused settings pages.

## Management and activation contract

General shared references are managed once at **البيانات المرجعية العامة**. Ramadan-specific data stays under the expandable **إفطارات رمضان** group. Admin lists include active and inactive rows; consuming create forms query active rows, while edit forms explicitly retain their current inactive relation. Deactivation is preferred to deletion. Seeders use stable keys plus guarded inserts and do not reset normal Admin changes. Demo/showcase seeders remain outside production/reference orchestration.

## Navigation

- **الإدارة والصلاحيات:** users, roles, workflows, branches, approvals, reports.
- **الإعدادات والبيانات المرجعية:** site settings and shared Events reference data.
- **إفطارات رمضان** (expandable): settings, Ramadan reference data, organizations/centres, local communities, mobilization methods.
- Operational module and reporting links remain governed by their existing roles and permissions.

The active Ramadan child keeps its parent expanded and highlighted. Navigation depth is limited to Admin category → page.

## Dashboard and header

`ramadan_dashboard_enabled` defaults to enabled and is only an additional gate. When off, `DashboardController` does not call the Ramadan period/metrics loader. When on, the existing active-period, permission, branch-scope and lifecycle filters remain unchanged.

The main header renders an authoritative initial instant using `config('app.timezone')`, Gregorian date, Arabic weekday, and a display-only ICU `islamic-umalqura` Hijri date. A local JavaScript timer advances the clock without network requests. The Hijri header does not query `RamadanPeriod` and never controls operational dates.

## Seeder orchestration

`RamadanReferenceDataSeeder` contains production/reference records and the dashboard setting default. `RamadanIftarStagingSeeder` calls the same focused reference seeders before guidance and demo orchestration. Demo records remain in `RamadanIftarDemoSeeder`.
