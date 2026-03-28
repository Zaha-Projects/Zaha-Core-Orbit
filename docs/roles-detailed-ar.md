# ملف الأدوار التفصيلي (Roles Master Sheet)

> هذا الملف منفصل ومخصص للأدوار فقط، ويجمع: اسم الدور، الاسم العربي، الحالة المقترحة (إبقاء/دمج/تعديل/اختياري)، الصلاحيات، الواجهات المرتبطة، وحسابات Seeder (إن وجدت).

## 1) مصادر الاعتماد
- تعريف الأدوار والصلاحيات: `database/seeders/RolePermissionSeeder.php`
- حسابات المستخدمين المزروعة: `database/seeders/UsersSeeder.php`
- ربط الأدوار مع الشاشات/المسارات: `routes/web.php`

---

## 2) جدول الأدوار الكامل

| Role Key | الاسم العربي | الحالة المقترحة | القرار التنفيذي | الصلاحيات الأساسية (Permissions) | أهم المسارات المرتبطة | حسابات Seeder (Email) | Password |
|---|---|---|---|---|---|---|---|
| `super_admin` | مدير النظام العام | مطلوب | إبقاء | جميع الصلاحيات | `/dashboard/admin/*` + أغلب الوحدات | `super_admin@zaha.test` | `password` |
| `relations_manager` | مدير/رئيس وحدة العلاقات | مطلوب | إبقاء | `agenda.view`, `agenda.approve`, `agenda.publish`, `monthly.view`, `monthly.approve` | `/dashboard/relations/agenda*`, `/dashboard/relations/agenda/approvals*` | `relations_manager@khalda.zaha.test`, `relations_manager@zarqa.zaha.test`, `relations_manager@irbid.zaha.test` | `password` |
| `relations_officer` | ضابط/مسؤول العلاقات | مطلوب | إبقاء | `agenda.view`, `agenda.create`, `monthly.view`, `monthly.approve` | `/dashboard/relations/agenda*`, `/dashboard/relations/monthly-activities*` | `relations_officer@zarqa.zaha.test`, `relations_officer@irbid.zaha.test` | `password` |
| `branch_relations_officer` (مستخدم في routes/workflow) | ضابط ارتباط العلاقات (فرع) | مطلوب | **توحيد/تعديل** | غير معرف رسمياً في RolePermissionSeeder | مشارك في monthly/branch participation routes | غير موجود Seeded | — |
| `programs_officer` | ضابط البرامج | مطلوب | إبقاء | `monthly.view`, `monthly.create` | approvals + supplies/team | `programs_officer@khalda.zaha.test` | `password` |
| `programs_manager` | مدير وحدة البرامج | مطلوب | إبقاء | `monthly.view`, `monthly.approve`, `monthly.execute` | approvals + supplies/team + workshops/communications participation | `programs_manager@khalda.zaha.test` | `password` |
| `executive_manager` | المدير التنفيذي | مطلوب | إبقاء | `agenda.view`, `agenda.approve`, `agenda.publish`, `monthly.view`, `monthly.approve` | agenda/monthly approvals | `executive_manager@khalda.zaha.test` | `password` |
| `communication_head` | رئيس قسم الاتصال | مطلوب | إبقاء | `agenda.view`, `agenda.participation.update` | `/dashboard/programs/communications-requests*`, agenda unit participation | `communication_head@khalda.zaha.test` | `password` |
| `workshops_secretary` | مقرر لجنة المشاغل | مطلوب | إبقاء | `agenda.view`, `agenda.participation.update` | `/dashboard/programs/workshops-requests*`, agenda unit participation | `workshops_secretary@khalda.zaha.test` | `password` |
| `followup_officer` | ضابط ارتباط المتابعة | مطلوب | إبقاء + إضافة Seed | `reports.view`, `kpi.view`, `kpi.manage`, `agenda.view`, `monthly.view` | `/dashboard/reports/*`, `/dashboard/reports/kpis*` + edit monthly | غير موجود Seeded في UsersSeeder | — |
| `transport_officer` | مأمور/ضابط الحركة والنقل | مطلوب | إبقاء + إضافة Seed | `transport.view`, `transport.manage`, `movement.view`, `movement.manage` | `/dashboard/transport/*` | غير موجود Seeded في UsersSeeder | — |
| `movement_manager` | مدير الحركة | مطلوب/تنظيمي | دمج أو إبقاء مع Matrix واضح | `movement.view`, `movement.manage` | `/dashboard/transport/movements*` | غير موجود Seeded | — |
| `movement_editor` | محرر الحركة | اختياري | إبقاء مشروط/دمج | `movement.view`, `movement.manage` | `/dashboard/transport/movements*` | غير موجود Seeded | — |
| `movement_viewer` | مستعرض الحركة | اختياري | إبقاء مشروط | `movement.view` | `/dashboard/transport/movements*` (عرض) | غير موجود Seeded | — |
| `maintenance_officer` | ضابط/مسؤول الصيانة | مطلوب | إبقاء + إضافة Seed | `maintenance.view`, `maintenance.manage` | `/dashboard/maintenance/*` | غير موجود Seeded في UsersSeeder | — |
| `finance_officer` | ضابط المالية | اختياري مرحلياً | تأجيل إذا خارج نطاق النسخة الحالية | `revenues.view`, `revenues.collect` | `/dashboard/finance/*` | غير موجود Seeded في UsersSeeder | — |
| `reports_viewer` | مستعرض التقارير | مطلوب | إبقاء + إضافة Seed | `reports.view` | `/dashboard/reports/*`, `/dashboard/enterprise/*` | غير موجود Seeded في UsersSeeder | — |
| `staff` | موظف (عرض عام) | اختياري | إبقاء مشروط | `agenda.view`, `monthly.view` | `/dashboard/staff/*` | غير موجود Seeded في UsersSeeder | — |

---

## 3) فجوات يجب إغلاقها في ملف الأدوار

1. **توحيد role keys**:
   - إما اعتماد `branch_relations_officer` رسميًا داخل RolePermissionSeeder + Seeder users.
   - أو إزالته من routes/workflow واستبداله بـ `relations_officer` فقط.

2. **استكمال حسابات Seeded الأساسية قبل UAT**:
   - `followup_officer`, `transport_officer`, `maintenance_officer`, `reports_viewer` (حد أدنى).

3. **قرار استراتيجي لأدوار الحركة الثلاثية**:
   - إن لم يوجد تفويض فعلي متعدد المستويات، يفضل دمج `movement_*` داخل `transport_officer` لتقليل التعقيد.

---

## 4) مصفوفة تفعيل سريعة (Ready-to-Use)

| الدور | التفعيل المقترح في النسخة الحالية |
|---|---|
| super_admin | تفعيل إجباري |
| relations_manager / relations_officer | تفعيل إجباري |
| programs_manager / programs_officer | تفعيل إجباري |
| executive_manager | تفعيل إجباري |
| communication_head / workshops_secretary | تفعيل إجباري |
| followup_officer | تفعيل إجباري (مع Seed) |
| transport_officer | تفعيل إجباري (مع Seed) |
| maintenance_officer | تفعيل إجباري (مع Seed) |
| reports_viewer | تفعيل إجباري (مع Seed) |
| movement_manager/editor/viewer | تفعيل مشروط حسب الهيكل التشغيلي |
| finance_officer | تفعيل حسب نطاق الإصدار |
| staff | تفعيل اختياري |

