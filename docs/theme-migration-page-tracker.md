# Theme Migration Page Tracker (Arabic-First)

> الهدف: تسجيل واضح للصفحات التي **تم تحويلها** إلى الثيم الجديد والتي **لا تزال Pending**، مع خطة تنفيذ أسبوعية قابلة للتحديث في كل PR.

## تعريف الحالات
- ✅ **Migrated**: الصفحة تعمل على `layouts.new-theme-dashboard` أو مكوّنات الثيم الجديدة بدون كسر لوجيك.
- 🟡 **In Progress**: بدأ التحويل لكن لم يكتمل التحقق النهائي.
- ⏳ **Pending**: لم يبدأ التحويل بعد.
- 🔁 **Shared Partial**: partial مشترك يحتاج التحويل قبل/أثناء تحويل الصفحات التابعة.

---

## الحالة الحالية (تحديث: 2026-04-26)

### 1) Core
| Page | Status | Notes |
|---|---|---|
| `resources/views/dashboard.blade.php` | ✅ Migrated | يعمل على الثيم الجديد + cards/tabs/notifications/calendar/date-time/pagination |

### 2) Finance
| Page | Status | Notes |
|---|---|---|
| `resources/views/pages/finance/payments/index.blade.php` | ✅ Migrated | تم الحفاظ على نفس الفورمز والـ routes |
| `resources/views/pages/finance/donations/index.blade.php` | ✅ Migrated | تم تحويل واجهة الإدخال + الجدول + pagination |
| `resources/views/pages/finance/bookings/index.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/bookings/create.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/bookings/edit.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/zaha_time/index.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/zaha_time/create.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/zaha_time/edit.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/donations/create.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/donations/edit.blade.php` | ✅ Migrated | |
| `resources/views/pages/finance/partials/sidebar.blade.php` | 🔁 Shared Partial | مرجع قديم؛ التنقل أصبح موحدًا داخل `new-theme-dashboard` |

### 3) Access
| Page | Status |
|---|---|
| `resources/views/pages/access/roles/index.blade.php` | ✅ Migrated |
| `resources/views/pages/access/users/index.blade.php` | ✅ Migrated |
| `resources/views/pages/access/workflows/index.blade.php` | ✅ Migrated |
| `resources/views/pages/access/branches/index.blade.php` | ✅ Migrated |
| `resources/views/pages/access/approvals/index.blade.php` | ✅ Migrated |
| `resources/views/pages/access/partials/sidebar.blade.php` | 🔁 Shared Partial |

### 4) Agenda
| Page | Status |
|---|---|
| `resources/views/pages/agenda/events/index.blade.php` | ✅ Migrated |
| `resources/views/pages/agenda/events/create.blade.php` | ✅ Migrated |
| `resources/views/pages/agenda/events/edit.blade.php` | ✅ Migrated |
| `resources/views/pages/agenda/events/show.blade.php` | ✅ Migrated |
| `resources/views/pages/agenda/events/_form.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/agenda/approvals/index.blade.php` | ✅ Migrated |
| `resources/views/pages/agenda/partials/sidebar.blade.php` | 🔁 Shared Partial |

### 5) Monthly Activities
| Page | Status |
|---|---|
| `resources/views/pages/monthly_activities/activities/index.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/activities/create.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/activities/edit.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/activities/show.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/activities/_form.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/monthly_activities/approvals/index.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/approvals/partials/activity-card.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/monthly_activities/approvals/partials/activity-details.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/monthly_activities/communications/index.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/workshops/index.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/lookups/index.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/lookups/admin.blade.php` | ✅ Migrated |
| `resources/views/pages/monthly_activities/partials/sidebar.blade.php` | 🔁 Shared Partial |

### 6) Maintenance
| Page | Status |
|---|---|
| `resources/views/pages/maintenance/requests/index.blade.php` | ⏳ Pending |
| `resources/views/pages/maintenance/requests/create.blade.php` | ⏳ Pending |
| `resources/views/pages/maintenance/requests/edit.blade.php` | ⏳ Pending |
| `resources/views/pages/maintenance/approvals/index.blade.php` | ⏳ Pending |
| `resources/views/pages/maintenance/partials/sidebar.blade.php` | 🔁 Shared Partial |

### 7) Transport
| Page | Status |
|---|---|
| `resources/views/pages/transport/vehicles/index.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/vehicles/create.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/vehicles/edit.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/drivers/index.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/drivers/create.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/drivers/edit.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/trips/index.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/trips/create.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/trips/edit.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/movements/index.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/movements/create.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/movements/edit.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/movements/show.blade.php` | ⏳ Pending |
| `resources/views/pages/transport/movements/form.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/transport/partials/sidebar.blade.php` | 🔁 Shared Partial |

### 8) Reports + Enterprise
| Page | Status |
|---|---|
| `resources/views/pages/reports/index.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/agenda.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/monthly.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/finance.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/maintenance.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/transport.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/kpis.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/enterprise/branch-performance.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/enterprise/printable.blade.php` | ⏳ Pending |
| `resources/views/pages/reports/partials/sidebar.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/enterprise/dashboard.blade.php` | ⏳ Pending |
| `resources/views/pages/enterprise/annual-planning-overview.blade.php` | ✅ Migrated |
| `resources/views/pages/enterprise/partials/kpis.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/enterprise/partials/charts.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/enterprise/partials/charts-scripts.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/enterprise/partials/branch-performance.blade.php` | 🔁 Shared Partial |

### 9) Shared Filters
| Page | Status |
|---|---|
| `resources/views/pages/shared/filters/status-select.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/shared/filters/select-field.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/shared/filters/month-and-year-select.blade.php` | 🔁 Shared Partial |
| `resources/views/pages/shared/filters/workflow-status-and-step.blade.php` | 🔁 Shared Partial |

---

## خطة التنفيذ القادمة (واضحة وقابلة للقياس)

### Wave 1 (Finance Complete)
1. `finance/bookings/index` ثم `create` ثم `edit`.
2. `finance/zaha_time/index` ثم `create` ثم `edit`.
3. `finance/donations/create` ثم `edit`.

### Wave 2 (Transport Core)
1. `transport/vehicles/index`.
2. `transport/drivers/index`.
3. `transport/trips/index`.

### Wave 3 (Agenda + Monthly Activities)
1. `agenda/events/index` + `_form`.
2. `monthly_activities/activities/index` + `_form`.

### Wave 4 (Reports + Access)
1. `reports/index` + finance/monthly/transport.
2. `access/users` + `roles` + `workflows`.

---

## قواعد التحديث في كل PR
- تحديث هذا الملف إجباري في كل PR ترحيل.
- نقل الصفحة من `⏳ Pending` إلى `✅ Migrated` فقط بعد:
  1) توصيلها بالثيم الجديد.
  2) الحفاظ على نفس routes/forms/logic.
  3) فحص AR/EN + RTL/LTR + Dark/Light.

## ملاحظة التوحيد
- السايدبار والنافبار أصبحا موحدين داخل `layouts/new-theme-dashboard.blade.php`.
- أي تعريفات قديمة من نوع `theme_sidebar_links` تمت إزالتها من الصفحات المهاجرة.
