# TODO إعادة هيكلة مشروع Zaha OPS (Controllers + Views فقط حالياً)

> **النطاق الحالي:** في هذه المرحلة سنركز فقط على تنظيم **Controllers** و **Views** بدون تغيير طبقات Domain/Application/Infrastructure وبدون إعادة توزيع routes بشكل جذري.

## 1) الهدف من المرحلة الحالية
- توحيد أماكن الكنترولرات بحيث تصبح Module-based بدل الاعتماد على Role-based داخل المسارات.
- توحيد هيكل `resources/views` بحيث تكون الصفحات تحت `pages/<module>/...`.
- الحفاظ على سلوك النظام الحالي بدون كسر route names أو صلاحيات الوصول.

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

## 5) أمثلة انتقال مباشرة
- `App\Http\Controllers\Roles\SuperAdmin\DepartmentsManagementController`
  -> `App\Http\Controllers\Web\Access\DepartmentsController`
- `App\Http\Controllers\Roles\Relations\AgendaEventsController`
  -> `App\Http\Controllers\Web\Agenda\AgendaEventsController`
- `resources/views/roles/super_admin/departments.blade.php`
  -> `resources/views/pages/access/departments/index.blade.php`
- `resources/views/roles/relations/agenda/create.blade.php`
  -> `resources/views/pages/agenda/events/create.blade.php`

---

## 6) ما تم تأجيله صراحةً
- إعادة هيكلة Domain/Application/Infrastructure.
- تقسيم ملفات routes إلى عدة ملفات.
- إعادة تصميم authorization/policies.
- تحسين CI/coverage.

> سيتم تنفيذ البنود المؤجلة في مرحلة لاحقة بعد استقرار تنظيم الكنترولرات والفيوات.
