# TODO إعادة هيكلة مشروع Zaha OPS (Controllers + Views + Structure)

> الهدف: تنظيف هيكل المشروع الحالي، توحيد طريقة التنظيم، وفصل المنطق التشغيلي (Domain/Application/Infrastructure) بحيث يسهل التطوير والصيانة والاختبار.

## 1) المشاكل الحالية في الهيكل
- التنظيم الحالي في `Controllers` يعتمد جزئياً على **Role-based** وأحياناً على **Module-based** (اختلاط نمطين).
- تكرار في مسارات العرض بين:
  - `roles/<module>/...`
  - `roles/<role_name>/dashboard`
- عدم وجود فصل واضح بين:
  - Validation (Form Requests)
  - Business logic (Use Cases / Services)
  - Persistence (Repositories)
- تضخم `routes/web.php` في ملف واحد كبير.

---

## 2) الهيكلية المقترحة (أفضل ممارسة للمشروع)

## 2.1 هيكل `app/`

```text
app/
  Domain/
    Shared/
      ValueObjects/
      Enums/
      Contracts/
    Access/
      Models/
      Policies/
      Services/
    Agenda/
      Models/
      Enums/
      Services/
    MonthlyActivities/
      Models/
      Enums/
      Services/
    Finance/
      Models/
      Enums/
      Services/
    Maintenance/
      Models/
      Enums/
      Services/
    Transport/
      Models/
      Enums/
      Services/
    Reports/
      Services/

  Application/
    Shared/
      DTOs/
      Actions/
      Queries/
    Access/
      Actions/
      DTOs/
    Agenda/
      Actions/
      DTOs/
      Queries/
    MonthlyActivities/
      Actions/
      DTOs/
      Queries/
    Finance/
      Actions/
      DTOs/
      Queries/
    Maintenance/
      Actions/
      DTOs/
      Queries/
    Transport/
      Actions/
      DTOs/
      Queries/
    Reports/
      Actions/
      Queries/

  Infrastructure/
    Persistence/
      Eloquent/
        Repositories/
    Services/
      Export/
      Storage/
      Notifications/

  Http/
    Controllers/
      Web/
        Access/
        Agenda/
        MonthlyActivities/
        Finance/
        Maintenance/
        Transport/
        Reports/
        Staff/
      Auth/
    Requests/
      Access/
      Agenda/
      MonthlyActivities/
      Finance/
      Maintenance/
      Transport/
      Reports/
    Resources/
    Middleware/
```

> ملاحظة: الموديلات الحالية يمكن إبقاؤها مرحلياً في `app/Models` ثم نقلها تدريجياً إلى `Domain/*/Models` مع `class_alias` مؤقت إذا لزم.

## 2.2 هيكل `resources/views/`

```text
resources/views/
  layouts/
    app.blade.php
    guest.blade.php
    components/
      nav/
      forms/
      tables/
      badges/

  pages/
    dashboard/
      super_admin.blade.php
      relations_manager.blade.php
      relations_officer.blade.php
      programs_manager.blade.php
      programs_officer.blade.php
      finance_officer.blade.php
      maintenance_officer.blade.php
      transport_officer.blade.php
      reports_viewer.blade.php
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
      partials/

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
      overview/
      agenda/
      monthly/
      finance/
      maintenance/
      transport/

    staff/
      agenda/
      activities/

  auth/
  welcome.blade.php
```

---

## 3) هيكل Routes المقترح

```text
routes/
  web.php                  # bootstrap فقط
  web/
    auth.php
    dashboard.php
    access.php             # users/roles/branches/centers/departments/approvals
    agenda.php
    monthly_activities.php
    finance.php
    maintenance.php
    transport.php
    reports.php
    staff.php
```

- داخل `web.php`: تحميل الملفات الفرعية عبر `require`.
- كل ملف routes خاص بموديول واحد مع middleware واضح.

---

## 4) TODO List تنفيذية (إعادة الهيكلة)

## Phase 0 — تحضير (بدون كسر النظام)
- [ ] تجميد naming conventions واعتماد standard موحد (Modules أولاً ثم Roles كصلاحية فقط).
- [ ] إنشاء هذا المستند كمرجع رسمي للهيكل.
- [ ] توثيق خريطة انتقال `Old Path -> New Path` لكل Controller/View/Route.

## Phase 1 — Routes Split
- [ ] تقسيم `routes/web.php` إلى ملفات فرعية لكل موديول.
- [ ] إبقاء نفس أسماء الـ route names الحالية لتجنب كسر الواجهات.
- [ ] إضافة اختبار smoke لكل route group.

## Phase 2 — Controllers Refactor
- [ ] نقل الكنترولرز من `App\Http\Controllers\Roles\*` إلى `App\Http\Controllers\Web\<Module>`.
- [ ] فصل Dashboards في `Web/Dashboard` أو `pages/dashboard/*`.
- [ ] استبدال Validation inline بـ Form Request classes.
- [ ] تقليل منطق الكنترولر إلى orchestration فقط (استدعاء Action/Service).

## Phase 3 — Views Refactor
- [ ] نقل `resources/views/roles/*` إلى `resources/views/pages/*` حسب الموديول.
- [ ] توحيد partials/components (جداول، فورمز، أزرار، alerts).
- [ ] توحيد naming convention للملفات: `index/create/edit/show/partials/*`.

## Phase 4 — Application/Domain Layer
- [ ] إنشاء Actions لكل use-case (Create/Update/Submit/Approve/Close).
- [ ] إنشاء DTOs للمدخلات والمخرجات.
- [ ] نقل business rules من controllers/models إلى services/actions.
- [ ] توحيد enums للحالات (status workflows) لكل موديول.

## Phase 5 — Authorization & Policies
- [ ] تحويل الاعتماد الأساسي من role-only إلى permission/policy per action.
- [ ] إضافة Policies للموديلات الأساسية (AgendaEvent, MonthlyActivity, Booking, MaintenanceRequest, Trip, Department).
- [ ] تغطية tests للصلاحيات الموجبة والسالبة.

## Phase 6 — Tests & Quality Gate
- [ ] Feature tests لكل CRUD + workflow transitions لكل موديول.
- [ ] Unit tests لـ Actions/Services.
- [ ] إقرار حد أدنى للتغطية قبل الدمج (مثلاً 70%).
- [ ] إضافة CI checks: Pint + PHPUnit + static analysis.

## Phase 7 — Cleanup & Deprecation
- [ ] إزالة المسارات/الملفات القديمة بعد اكتمال migration.
- [ ] حذف aliases المؤقتة إن استخدمت.
- [ ] تحديث جميع وثائق المشروع (Site Map / TODO / Developer Guide).

---

## 5) خريطة انتقال مقترحة (أمثلة)
- `App\Http\Controllers\Roles\SuperAdmin\UsersManagementController`
  -> `App\Http\Controllers\Web\Access\UsersController`
- `App\Http\Controllers\Roles\Relations\AgendaEventsController`
  -> `App\Http\Controllers\Web\Agenda\AgendaEventsController`
- `resources/views/roles/super_admin/users.blade.php`
  -> `resources/views/pages/access/users/index.blade.php`
- `resources/views/roles/relations/agenda/create.blade.php`
  -> `resources/views/pages/agenda/events/create.blade.php`

---

## 6) ترتيب الأولوية (Roadmap)
1. Access + Departments (أقل مخاطرة، أعلى أثر تنظيمي).
2. Agenda + Monthly Activities (مسارات الاعتماد).
3. Finance.
4. Maintenance + Transport.
5. Reports + Staff.

---

## 7) مخرجات متوقعة بعد الهيكلة
- هيكل موحد واضح (module-driven).
- تقليل التكرار في views/controllers.
- سهولة إضافة ميزات جديدة دون تشابك.
- اختبارات أكثر استقراراً وقابلة للتوسع.
- onboarding أسرع لأي مطور جديد في المشروع.
