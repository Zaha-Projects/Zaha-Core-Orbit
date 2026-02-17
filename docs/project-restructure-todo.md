# TODO إعادة هيكلة مشروع Zaha OPS (Controllers + Views فقط حالياً)

> **النطاق الحالي:** في هذه المرحلة سنركز فقط على تنظيم **Controllers** و **Views** بدون تغيير طبقات Domain/Application/Infrastructure وبدون إعادة توزيع routes بشكل جذري.

## 1) الهدف من المرحلة الحالية
- توحيد أماكن الكنترولرات بحيث تصبح Module-based بدل الاعتماد على Role-based داخل المسارات.
- توحيد هيكل `resources/views` بحيث تكون الصفحات تحت `pages/<module>/...`.
- الحفاظ على سلوك النظام الحالي بدون كسر route names أو صلاحيات الوصول.
- اعتماد واجهة الثيم الجديد في Layout موحد يحتوي: **App Header + Side Menu + Footer**.
- الالتزام بدعم اللغتين **العربية / الإنجليزية** في كل صفحة يتم تعديلها.

---

## 2) الهيكل المقترح للكنترولرات (فقط)

```text
app/Http/Controllers/
  Web/
    Dashboard/
      SuperAdminDashboardController.php
      RelationsDashboardController.php
      ProgramsDashboardController.php
      FinanceDashboardController.php
      MaintenanceDashboardController.php
      TransportDashboardController.php
      ReportsDashboardController.php
      StaffDashboardController.php

    Access/
      UsersController.php
      RolesController.php
      BranchesController.php
      CentersController.php
      DepartmentsController.php
      ApprovalsController.php

    Agenda/
      AgendaEventsController.php
      AgendaApprovalsController.php

    MonthlyActivities/
      MonthlyActivitiesController.php
      MonthlyActivitiesApprovalsController.php

    Finance/
      DonationsController.php
      BookingsController.php
      ZahaTimeController.php
      PaymentsController.php

    Maintenance/
      MaintenanceRequestsController.php
      MaintenanceApprovalsController.php

    Transport/
      VehiclesController.php
      DriversController.php
      TripsController.php

    Reports/
      ReportsController.php

    Staff/
      StaffAgendaController.php
      StaffActivitiesController.php
```

### قاعدة التسمية
- Controller name = `Module + Resource + Controller`.
- لا نستخدم أسماء مبنية على الدور داخل اسم الكنترولر (role يكون بالصلاحية/الميدلوير فقط).

---

## 3) الهيكل المقترح للـ Views (فقط)

```text
resources/views/
  layouts/
    app.blade.php
    app/
      header.blade.php
      sidebar.blade.php
      footer.blade.php
    guest.blade.php
    components/
      forms/
      tables/
      alerts/
      badges/

  pages/
    dashboard/
      super_admin.blade.php
      relations.blade.php
      programs.blade.php
      finance.blade.php
      maintenance.blade.php
      transport.blade.php
      reports.blade.php
      staff.blade.php

    access/
      users/
      roles/
      branches/
      centers/
      departments/
      approvals/

    agenda/
      events/
      approvals/

    monthly_activities/
      activities/
      approvals/

    finance/
      donations/
      bookings/
      zaha_time/
      payments/

    maintenance/
      requests/
      approvals/

    transport/
      vehicles/
      drivers/
      trips/

    reports/
      index/

    staff/
      agenda/
      activities/

  auth/
  welcome.blade.php
```

### قاعدة التسمية
- الصفحات القياسية لكل مورد: `index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`.
- الأجزاء المشتركة داخل `partials/` داخل نفس المورد.
- تجنب تكرار partials بين الموديولات؛ أي عنصر متكرر ينتقل إلى `layouts/components/*`.

---

## 4) TODO تنفيذية (Controllers + Views فقط)

## Phase A — Mapping
- [ ] عمل جرد كامل: `Old Controller/View -> New Controller/View`.
- [ ] تحديد الملفات ذات الأولوية العالية (Access, Agenda, Monthly Activities).
- [ ] توثيق أي تعارض في الأسماء قبل النقل.

## Phase B — Controllers Move
- [ ] نقل الكنترولرات من `App\Http\Controllers\Roles\*` إلى `App\Http\Controllers\Web\*` حسب الموديول.
- [ ] إبقاء نفس الميثودز الحالية (`index/store/update/destroy/...`) لتفادي كسر الـ routes.
- [ ] تحديث namespace + use statements بدون تغيير منطق العمل.
- [ ] إضافة طبقة توافق مؤقتة فقط إذا لزم (class aliases أو route namespace fallback).

## Phase C — Views Move
- [ ] نقل `resources/views/roles/*` إلى `resources/views/pages/*` حسب الموديول.
- [ ] تحديث `view()` references داخل الكنترولرات بعد النقل.
- [ ] توحيد naming templates للصفحات القياسية.
- [ ] استخراج العناصر المتكررة إلى `layouts/components`.

## Phase D — Verification
- [ ] فحص يدوي لكل صفحة رئيسية بعد النقل (عرض/إنشاء/تعديل/حذف).
- [ ] التأكد أن الصلاحيات الحالية تعمل كما هي (بدون تعديل policy logic حالياً).
- [ ] تشغيل lint للملفات المعدلة وتوثيق النتيجة.

---

## 5) خطة العمل التنفيذية خطوة بخطوة (صفحة/كنترولر)

### Sprint 0 — تجهيز الأساس (تم البدء)
- [x] تقسيم Layout إلى `Header + Sidebar + Footer` ضمن `layouts/app/*`.
- [x] ربط النصوص الأساسية للـ Layout بملفات الترجمة `ar/en`.
- [ ] نقل أول صفحة تجريبية إلى `resources/views/pages/...` مع الحفاظ على route.

### Sprint 1 — Access Module
1. [x] Controllers: Users, Roles, Branches, Centers, Approvals.
2. [x] Views: `pages/access/*` (index/create/edit/show حسب الحاجة).
3. [ ] ترجمة عربية/إنجليزية لكل labels/buttons/messages المستحدثة.
4. [ ] اختبار يدوي + lint.

#### Mapping المنفذ في Sprint 1
- `App\Http\Controllers\Roles\SuperAdmin\UsersManagementController`
  -> `App\Http\Controllers\Web\Access\UsersController`
- `App\Http\Controllers\Roles\SuperAdmin\RolesManagementController`
  -> `App\Http\Controllers\Web\Access\RolesController`
- `App\Http\Controllers\Roles\SuperAdmin\BranchesManagementController`
  -> `App\Http\Controllers\Web\Access\BranchesController`
- `App\Http\Controllers\Roles\SuperAdmin\CentersManagementController`
  -> `App\Http\Controllers\Web\Access\CentersController`
- `App\Http\Controllers\Roles\SuperAdmin\ApprovalsController`
  -> `App\Http\Controllers\Web\Access\ApprovalsController`
- `resources/views/roles/super_admin/users.blade.php`
  -> `resources/views/pages/access/users/index.blade.php`
- `resources/views/roles/super_admin/roles.blade.php`
  -> `resources/views/pages/access/roles/index.blade.php`
- `resources/views/roles/super_admin/branches.blade.php`
  -> `resources/views/pages/access/branches/index.blade.php`
- `resources/views/roles/super_admin/centers.blade.php`
  -> `resources/views/pages/access/centers/index.blade.php`
- `resources/views/roles/super_admin/approvals.blade.php`
  -> `resources/views/pages/access/approvals/index.blade.php`

### Sprint 2 — Agenda Module
1. [x] Controllers: AgendaEvents, AgendaApprovals.
2. [x] Views: `pages/agenda/events/*` + `pages/agenda/approvals/*`.
3. [x] ترجمة عربية/إنجليزية.
4. [ ] اختبار يدوي + lint.

#### Mapping المنفذ في Sprint 2
- `App\Http\Controllers\Roles\Relations\AgendaEventsController`
  -> `App\Http\Controllers\Web\Agenda\AgendaEventsController`
- `App\Http\Controllers\Roles\Relations\AgendaApprovalsController`
  -> `App\Http\Controllers\Web\Agenda\AgendaApprovalsController`
- `resources/views/roles/relations/agenda/index.blade.php`
  -> `resources/views/pages/agenda/events/index.blade.php`
- `resources/views/roles/relations/agenda/create.blade.php`
  -> `resources/views/pages/agenda/events/create.blade.php`
- `resources/views/roles/relations/agenda/edit.blade.php`
  -> `resources/views/pages/agenda/events/edit.blade.php`
- `resources/views/roles/relations/agenda/approvals.blade.php`
  -> `resources/views/pages/agenda/approvals/index.blade.php`

### Sprint 3 — Monthly Activities Module
1. [x] Controllers: MonthlyActivities, MonthlyActivitiesApprovals.
2. [x] Views: `pages/monthly_activities/activities/*` + `pages/monthly_activities/approvals/*`.
3. [x] ترجمة عربية/إنجليزية.
4. [ ] اختبار يدوي + lint.

#### Mapping المنفذ في Sprint 3
- `App\Http\Controllers\Roles\Programs\MonthlyActivitiesController`
  -> `App\Http\Controllers\Web\MonthlyActivities\MonthlyActivitiesController`
- `App\Http\Controllers\Roles\Programs\MonthlyActivityApprovalsController`
  -> `App\Http\Controllers\Web\MonthlyActivities\MonthlyActivitiesApprovalsController`
- `resources/views/roles/programs/monthly_activities/index.blade.php`
  -> `resources/views/pages/monthly_activities/activities/index.blade.php`
- `resources/views/roles/programs/monthly_activities/create.blade.php`
  -> `resources/views/pages/monthly_activities/activities/create.blade.php`
- `resources/views/roles/programs/monthly_activities/edit.blade.php`
  -> `resources/views/pages/monthly_activities/activities/edit.blade.php`
- `resources/views/roles/programs/monthly_activities/approvals.blade.php`
  -> `resources/views/pages/monthly_activities/approvals/index.blade.php`


### Sprint 4 — Finance Module
1. [x] Controllers: Donations, Bookings, ZahaTime, Payments.
2. [x] Views: `pages/finance/donations/*`, `pages/finance/bookings/*`, `pages/finance/zaha_time/*`, `pages/finance/payments/*`.
3. [x] ترجمة عربية/إنجليزية.
4. [ ] اختبار يدوي + lint.

#### Mapping المنفذ في Sprint 4
- `App\Http\Controllers\Roles\Finance\DonationsCashController`
  -> `App\Http\Controllers\Web\Finance\DonationsController`
- `App\Http\Controllers\Roles\Finance\BookingsController`
  -> `App\Http\Controllers\Web\Finance\BookingsController`
- `App\Http\Controllers\Roles\Finance\ZahaTimeBookingsController`
  -> `App\Http\Controllers\Web\Finance\ZahaTimeController`
- `App\Http\Controllers\Roles\Finance\PaymentsController`
  -> `App\Http\Controllers\Web\Finance\PaymentsController`
- `resources/views/roles/finance/donations/*.blade.php`
  -> `resources/views/pages/finance/donations/*.blade.php`
- `resources/views/roles/finance/bookings/*.blade.php`
  -> `resources/views/pages/finance/bookings/*.blade.php`
- `resources/views/roles/finance/zaha_time/*.blade.php`
  -> `resources/views/pages/finance/zaha_time/*.blade.php`
- `resources/views/roles/finance/payments/index.blade.php`
  -> `resources/views/pages/finance/payments/index.blade.php`


### Sprint 5 — Maintenance Module
1. [x] Controllers: MaintenanceRequests, MaintenanceApprovals.
2. [x] Views: `pages/maintenance/requests/*` + `pages/maintenance/approvals/*`.
3. [x] ترجمة عربية/إنجليزية.
4. [ ] اختبار يدوي + lint.

#### Mapping المنفذ في Sprint 5
- `App\Http\Controllers\Roles\Maintenance\MaintenanceRequestsController`
  -> `App\Http\Controllers\Web\Maintenance\MaintenanceRequestsController`
- `App\Http\Controllers\Roles\Maintenance\MaintenanceApprovalsController`
  -> `App\Http\Controllers\Web\Maintenance\MaintenanceApprovalsController`
- `resources/views/roles/maintenance/requests/index.blade.php`
  -> `resources/views/pages/maintenance/requests/index.blade.php`
- `resources/views/roles/maintenance/requests/create.blade.php`
  -> `resources/views/pages/maintenance/requests/create.blade.php`
- `resources/views/roles/maintenance/requests/edit.blade.php`
  -> `resources/views/pages/maintenance/requests/edit.blade.php`
- `resources/views/roles/maintenance/approvals.blade.php`
  -> `resources/views/pages/maintenance/approvals/index.blade.php`

> بعد كل Sprint يتم فتح PR مستقل مع قائمة Mapping واضحة للملفات المنقولة.

---

## 6) أمثلة انتقال مباشرة
- `App\Http\Controllers\Roles\SuperAdmin\DepartmentsManagementController`
  -> `App\Http\Controllers\Web\Access\DepartmentsController`
- `App\Http\Controllers\Roles\Relations\AgendaEventsController`
  -> `App\Http\Controllers\Web\Agenda\AgendaEventsController`
- `resources/views/roles/super_admin/departments.blade.php`
  -> `resources/views/pages/access/departments/index.blade.php`
- `resources/views/roles/relations/agenda/create.blade.php`
  -> `resources/views/pages/agenda/events/create.blade.php`

---

## 7) ما تم تأجيله صراحةً
- إعادة هيكلة Domain/Application/Infrastructure.
- تقسيم ملفات routes إلى عدة ملفات.
- إعادة تصميم authorization/policies.
- تحسين CI/coverage.

> سيتم تنفيذ البنود المؤجلة في مرحلة لاحقة بعد استقرار تنظيم الكنترولرات والفيوات.
