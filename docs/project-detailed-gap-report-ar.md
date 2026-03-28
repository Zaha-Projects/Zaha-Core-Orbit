# التقرير التفصيلي لحالة المشروع الحالية (Gap Analysis + Roadmap + Roles + QA)

> هذا التقرير مبني على مراجعة الكود الحالي + ملفات التوثيق داخل `docs`، مع التركيز على طلبك: معرفة ما هو **موجود**، وما هو **ناقص**، وما هو **زائد/غير ضروري الآن**، ثم إعطاء خارطة طريق شاملة للصفحات والباثات والأدوار وطريقة الاختبار.

## 1) الملخص التنفيذي

- المشروع يغطي فعلياً الوحدات الأساسية التي ذكرتها: **العلاقات/الفعاليات + الأجندة + الحركة/النقل + الصيانة + السائقين**، بالإضافة إلى وحدات إضافية موجودة مثل **المالية والتقارير المؤسسية**.
- من ناحية البنية: يوجد تقدم جيد في الـ Workflow، الـ RBAC، والـ Migrations.
- أهم الفجوات الحالية ليست في “وجود الموديول” بل في:
  1) توحيد الأدوار بين Seeder/Routes/Workflow.
  2) تشديد الحماية لبعض المسارات غير المقيّدة بدور.
  3) إكمال طبقة الاختبارات والتنفيذ الفعلي (vendor/tests) قبل الإطلاق.
  4) تنظيف الملفات الزائدة (قوالب UI تجريبية غير مرتبطة بتدفق الإنتاج).

---

## 2) ما الذي يغطيه المشروع حالياً؟

| المحور | الحالة الحالية | الدليل التقني | الحكم |
|---|---|---|---|
| الأجندة (Agenda) | موجودة بمسارات CRUD + اعتماد + مشاركة فروع/جهات | Routes تحت `/dashboard/relations/agenda*` + موافقات | ✅ منجز وظيفياً (مع تحسينات حوكمة) |
| الخطة الشهرية للفعاليات | موجودة: إنشاء/تعديل/مزامنة من الأجندة/إرسال/إغلاق + ملحقات | Routes تحت `/dashboard/relations/monthly-activities*` + approvals + supplies/team/attachments | ✅ منجز جزئياً قوي |
| الحركة/النقل/السائقين | مركبات + سائقين + رحلات + جولات + مقاطع + حركات + طلبات حركة | Routes تحت `/dashboard/transport/*` + جداول movement/transport request | ✅ منجز جزئياً متقدم |
| الصيانة | طلبات + تفاصيل تنفيذ + مرفقات + اعتماد + إغلاق | Routes تحت `/dashboard/maintenance/*` + جداول صيانة | ✅ منجز جزئياً متقدم |
| التقارير/KPI | تقارير قطاعية + KPI + enterprise reports | Routes `/dashboard/reports/*` و `/dashboard/enterprise/*` | ✅ موجود |
| Lookup للتقييم/الفئات المستهدفة | جداول target_groups/evaluation_questions/responses موجودة | Migrations 2026-03-16 | ✅ موجود (يحتاج نضج تشغيلي) |

---

## 3) الفجوات (Missing) + الزيادات (Overbuild/Not Needed Now)

### 3.1 الفجوات/المخاطر الفعلية

| البند | الوصف | الأثر | الأولوية |
|---|---|---|---|
| تناقض تعريف الأدوار | `branch_relations_officer` مستخدم في routes/workflow/model لكنه غير واضح ضمن مصفوفة الأدوار الأساسية في seeding الرئيسي للأدوار | صلاحيات غير متوقعة/تعقيد تشغيلي | P0 |
| مسارات بدون role middleware | بعض مسارات طلبات النقل (`index/store/feedback`) تعمل داخل auth بدون تقييد role مباشر | ثغرة صلاحيات محتملة | P0 |
| بيئة غير جاهزة للتشغيل الفوري | فشل `php artisan route:list` بسبب غياب `vendor/autoload.php` | لا يمكن تنفيذ اختبار تكاملي حقيقي حالياً | P0 |
| تضخم تدريجي في web routes | ملف routes/web.php كبير جداً ويجمع موديولات كثيرة | صعوبة صيانة/مراجعة الصلاحيات | P1 |
| فجوة بين docs وخريطة التنفيذ الفعلية | بعض المتطلبات في docs ما تزال بصيغة خطة/اقتراح وليست fully hardened بالإختبارات | مخاطر UAT عند التسليم | P1 |

### 3.2 عناصر “زائدة حالياً” أو تحتاج تنظيف

| البند | لماذا يعتبر زائد/غير ضروري في هذه المرحلة | التوصية |
|---|---|---|
| قوالب `resources/views/layouts new/Duralux-admin-1.0.0/*` | قوالب كثيرة عامة/تجريبية وغير مرتبطة مباشرة بمسارات النظام التشغيلية | نقلها إلى `archive/` أو حذفها من build الإنتاج |
| توسع enterprise/reporting مقارنة بطلبك الحالي | طلبك الحالي يركز أكثر على الفعاليات/الحركة/الصيانة، بينما enterprise أوسع من الحاجة الأولى | إبقاءه Feature-flag أو مرحلة لاحقة |
| ازدواجية وثائق وخطط متعددة | وجود عدة خطط وتودوز قد يسبب تضارب “المرجعية الرسمية” | اعتماد وثيقة Canonical واحدة + changelog |

---

## 4) جدول مفصل: المطلوب مقابل الموجود (حسب الفورمات)

| الفورم/الوحدة | المطلوب من الوثائق | الموجود في الكود | الحالة | المفقود الدقيق |
|---|---|---|---|---|
| Form 1 – أجندة زها | إنشاء/مشاركة/اعتماد علاقات+تنفيذي + اختياري/إجباري + موحد/غير موحد | موجود CRUD + participation + approvals | منجز جزئياً قوي | تدقيق نهائي لقيود field-level + توحيد role naming |
| Form 2 – الخطة الشهرية | استيراد من الأجندة + فعاليات محلية + workflow متعدد + قفل 5 أيام + تقييم | موجود sync/workflow/lock/lookups/evaluation responses | منجز جزئياً قوي | مزيد من tests + توحيد شاشات follow-up/UX |
| Form 3 – الحركة والنقل | طلب حركة + مراجعة مأمور الحركة + تقييم الخدمة + إدارة مركبات/سائقين | موجود transport requests + vehicles/drivers + feedback | منجز جزئياً | إغلاق ثغرة middleware للمسارات غير المقيّدة |
| Form 4 – الصيانة | تسجيل/معالجة متعددة الجهات/إغلاق/توثيق كلفة وسبب | موجود maintenance requests/work details/approvals/attachments | منجز جزئياً | تدقيق ربط كل مسار مع الدور الصحيح (Branch vs HQ) |
| Form 5 – التقارير المتقدمة | نواة KPI وتقارير مقارنة | موجود reports + kpis + enterprise | منجز جزئياً | تعزيز jobs + اختبارات صحة المعادلات/التصدير |

---

## 5) خريطة الصفحات والباثات (Roadmap + Coverage)

## 5.1 خريطة تسليم مرحلية مقترحة (Roadmap)

| المرحلة | الهدف | الصفحات/الباثات الرئيسية | مخرج المرحلة |
|---|---|---|---|
| R0 (أمان وصلاحيات) | تثبيت RBAC وغلق أي مسار غير مقيّد | `/dashboard/transport/requests*` + مراجعة middleware لكل module | مصفوفة صلاحيات نظيفة |
| R1 (أجندة) | تثبيت سيناريو الأجندة end-to-end | `/dashboard/relations/agenda*` + approvals | UAT أجندة جاهز |
| R2 (الخطة الشهرية) | استقرار form2 كاملاً | `/dashboard/relations/monthly-activities*` + `/dashboard/programs/monthly-activities/approvals*` | UAT form2 جاهز |
| R3 (النقل/الحركة) | إغلاق form3 مع التقييم | `/dashboard/transport/requests*`, `/dashboard/transport/movements*`, `/dashboard/transport/drivers*` | UAT form3 جاهز |
| R4 (الصيانة) | إغلاق form4 متعدد المسار | `/dashboard/maintenance/*` | UAT form4 جاهز |
| R5 (التقارير) | تثبيت KPI + exports | `/dashboard/reports/*` + `/dashboard/enterprise/*` | تقارير تشغيلية للإدارة |
| R6 (تنظيف المنتج) | إزالة الزوائد وتوحيد docs | cleanup views/docs/routes split | جاهزية إنتاج |

## 5.2 خريطة الباثات الكاملة الحالية (من web.php)


### مسارات other (4)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/` | `-` | `auth-only/no role middleware` |
| POST | `/ui/theme/{theme}` | `-` | `auth-only/no role middleware` |
| POST | `/ui/locale/{locale}` | `-` | `auth-only/no role middleware` |
| GET | `/dashboard` | `dashboard` | `auth-only/no role middleware` |

### مسارات auth (5)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/login` | `login` | `auth-only/no role middleware` |
| POST | `/login` | `-` | `auth-only/no role middleware` |
| GET | `/register` | `register` | `auth-only/no role middleware` |
| POST | `/register` | `-` | `auth-only/no role middleware` |
| POST | `/logout` | `logout` | `auth-only/no role middleware` |

### مسارات admin (21)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/admin` | `role.super_admin.dashboard` | `super_admin` |
| GET | `/dashboard/admin/reports` | `role.super_admin.reports` | `super_admin` |
| GET | `/dashboard/admin/roles` | `role.super_admin.roles` | `super_admin` |
| POST | `/dashboard/admin/roles` | `role.super_admin.roles.store` | `super_admin` |
| PUT | `/dashboard/admin/roles/{role}` | `role.super_admin.roles.update` | `super_admin` |
| GET | `/dashboard/admin/users` | `role.super_admin.users` | `super_admin` |
| POST | `/dashboard/admin/users` | `role.super_admin.users.store` | `super_admin` |
| PUT | `/dashboard/admin/users/{user}` | `role.super_admin.users.update` | `super_admin` |
| DELETE | `/dashboard/admin/users/{user}` | `role.super_admin.users.destroy` | `super_admin` |
| GET | `/dashboard/admin/branches` | `role.super_admin.branches` | `super_admin` |
| POST | `/dashboard/admin/branches` | `role.super_admin.branches.store` | `super_admin` |
| PUT | `/dashboard/admin/branches/{branch}` | `role.super_admin.branches.update` | `super_admin` |
| DELETE | `/dashboard/admin/branches/{branch}` | `role.super_admin.branches.destroy` | `super_admin` |
| GET | `/dashboard/admin/centers` | `role.super_admin.centers` | `super_admin` |
| POST | `/dashboard/admin/centers` | `role.super_admin.centers.store` | `super_admin` |
| PUT | `/dashboard/admin/centers/{center}` | `role.super_admin.centers.update` | `super_admin` |
| DELETE | `/dashboard/admin/centers/{center}` | `role.super_admin.centers.destroy` | `super_admin` |
| GET | `/dashboard/admin/approvals` | `role.super_admin.approvals` | `super_admin` |
| GET | `/dashboard/admin/events-lookups` | `role.super_admin.events_lookups.index` | `super_admin` |
| POST | `/dashboard/admin/events-lookups/target-groups` | `role.super_admin.events_lookups.target_groups.store` | `super_admin` |
| POST | `/dashboard/admin/events-lookups/evaluation-questions` | `role.super_admin.events_lookups.evaluation_questions.store` | `super_admin` |

### مسارات relations (21)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/relations/manager` | `role.relations_manager.dashboard` | `relations_manager|super_admin` |
| GET | `/dashboard/relations/officer` | `role.relations_officer.dashboard` | `relations_officer|super_admin` |
| GET | `/dashboard/relations/agenda` | `role.relations.agenda.index` | `relations_manager|relations_officer|executive_manager|super_admin` |
| GET | `/dashboard/relations/agenda/create` | `role.relations.agenda.create` | `relations_manager|relations_officer|super_admin` |
| POST | `/dashboard/relations/agenda` | `role.relations.agenda.store` | `relations_manager|relations_officer|super_admin` |
| GET | `/dashboard/relations/agenda/{agendaEvent}/edit` | `role.relations.agenda.edit` | `relations_manager|relations_officer|super_admin` |
| PUT | `/dashboard/relations/agenda/{agendaEvent}` | `role.relations.agenda.update` | `relations_manager|relations_officer|super_admin` |
| PATCH | `/dashboard/relations/agenda/{agendaEvent}/submit` | `role.relations.agenda.submit` | `relations_manager|relations_officer|super_admin` |
| PATCH | `/dashboard/relations/agenda/{agendaEvent}/unit-participation` | `role.relations.agenda.unit_participation.update` | `relations_manager|workshops_secretary|communication_head|programs_manager|super_admin` |
| PATCH | `/dashboard/relations/agenda/{agendaEvent}/branch-participation` | `role.relations.agenda.branch_participation.update` | `relations_officer|branch_relations_officer|super_admin` |
| GET | `/dashboard/relations/agenda/approvals` | `role.relations.approvals.index` | `relations_manager|executive_manager|super_admin` |
| PUT | `/dashboard/relations/agenda/approvals/{agendaEvent}` | `role.relations.approvals.update` | `relations_manager|executive_manager|super_admin` |
| GET | `/dashboard/relations/monthly-activities` | `role.relations.activities.index` | `relations_manager|relations_officer|branch_relations_officer|super_admin` |
| GET | `/dashboard/relations/monthly-activities/calendar` | `role.relations.activities.calendar` | `relations_manager|relations_officer|branch_relations_officer|super_admin` |
| POST | `/dashboard/relations/monthly-activities/sync-from-agenda` | `role.relations.activities.sync_from_agenda` | `relations_manager|relations_officer|branch_relations_officer|super_admin` |
| GET | `/dashboard/relations/monthly-activities/create` | `role.relations.activities.create` | `relations_manager|relations_officer|branch_relations_officer|super_admin` |
| POST | `/dashboard/relations/monthly-activities` | `role.relations.activities.store` | `relations_manager|relations_officer|branch_relations_officer|super_admin` |
| GET | `/dashboard/relations/monthly-activities/{monthlyActivity}/edit` | `role.relations.activities.edit` | `relations_manager|relations_officer|branch_relations_officer|followup_officer|super_admin` |
| PUT | `/dashboard/relations/monthly-activities/{monthlyActivity}` | `role.relations.activities.update` | `relations_manager|relations_officer|branch_relations_officer|followup_officer|super_admin` |
| PATCH | `/dashboard/relations/monthly-activities/{monthlyActivity}/submit` | `role.relations.activities.submit` | `relations_manager|relations_officer|branch_relations_officer|super_admin` |
| PATCH | `/dashboard/relations/monthly-activities/{monthlyActivity}/close` | `role.relations.activities.close` | `relations_manager|relations_officer|branch_relations_officer|super_admin` |

### مسارات programs (16)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/programs/manager` | `role.programs_manager.dashboard` | `programs_manager` |
| GET | `/dashboard/programs/officer` | `role.programs_officer.dashboard` | `programs_officer` |
| POST | `/dashboard/programs/monthly-activities/{monthlyActivity}/supplies` | `role.programs.supplies.store` | `programs_manager|programs_officer` |
| PUT | `/dashboard/programs/supplies/{monthlyActivitySupply}` | `role.programs.supplies.update` | `programs_manager|programs_officer` |
| DELETE | `/dashboard/programs/supplies/{monthlyActivitySupply}` | `role.programs.supplies.destroy` | `programs_manager|programs_officer` |
| POST | `/dashboard/programs/monthly-activities/{monthlyActivity}/team` | `role.programs.team.store` | `programs_manager|programs_officer` |
| PUT | `/dashboard/programs/team/{monthlyActivityTeam}` | `role.programs.team.update` | `programs_manager|programs_officer` |
| DELETE | `/dashboard/programs/team/{monthlyActivityTeam}` | `role.programs.team.destroy` | `programs_manager|programs_officer` |
| POST | `/dashboard/programs/monthly-activities/{monthlyActivity}/attachments` | `role.programs.attachments.store` | `programs_manager|programs_officer|relations_manager|relations_officer|branch_relations_officer|super_admin` |
| DELETE | `/dashboard/programs/attachments/{monthlyActivityAttachment}` | `role.programs.attachments.destroy` | `programs_manager|programs_officer|relations_manager|relations_officer|branch_relations_officer|super_admin` |
| GET | `/dashboard/programs/monthly-activities/approvals` | `role.programs.approvals.index` | `relations_officer|relations_manager|programs_officer|programs_manager|executive_manager` |
| GET | `/dashboard/programs/workshops-requests` | `role.programs.workshops_requests.index` | `workshops_secretary|super_admin` |
| PUT | `/dashboard/programs/workshops-requests/{workshopsRequest}` | `role.programs.workshops_requests.update` | `workshops_secretary|super_admin` |
| GET | `/dashboard/programs/communications-requests` | `role.programs.communications_requests.index` | `communication_head|super_admin` |
| PUT | `/dashboard/programs/communications-requests/{communicationsRequest}` | `role.programs.communications_requests.update` | `communication_head|super_admin` |
| PUT | `/dashboard/programs/monthly-activities/approvals/{monthlyActivity}` | `role.programs.approvals.update` | `relations_officer|relations_manager|programs_officer|programs_manager|executive_manager` |

### مسارات finance (19)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/finance` | `role.finance_officer.dashboard` | `finance_officer` |
| GET | `/dashboard/finance/donations` | `role.finance.donations.index` | `finance_officer` |
| GET | `/dashboard/finance/donations/create` | `role.finance.donations.create` | `finance_officer` |
| POST | `/dashboard/finance/donations` | `role.finance.donations.store` | `finance_officer` |
| GET | `/dashboard/finance/donations/{donationCash}/edit` | `role.finance.donations.edit` | `finance_officer` |
| PUT | `/dashboard/finance/donations/{donationCash}` | `role.finance.donations.update` | `finance_officer` |
| GET | `/dashboard/finance/bookings` | `role.finance.bookings.index` | `finance_officer` |
| GET | `/dashboard/finance/bookings/create` | `role.finance.bookings.create` | `finance_officer` |
| POST | `/dashboard/finance/bookings` | `role.finance.bookings.store` | `finance_officer` |
| GET | `/dashboard/finance/bookings/{booking}/edit` | `role.finance.bookings.edit` | `finance_officer` |
| PUT | `/dashboard/finance/bookings/{booking}` | `role.finance.bookings.update` | `finance_officer` |
| GET | `/dashboard/finance/zaha-time` | `role.finance.zaha_time.index` | `finance_officer` |
| GET | `/dashboard/finance/zaha-time/create` | `role.finance.zaha_time.create` | `finance_officer` |
| POST | `/dashboard/finance/zaha-time` | `role.finance.zaha_time.store` | `finance_officer` |
| GET | `/dashboard/finance/zaha-time/{zahaTimeBooking}/edit` | `role.finance.zaha_time.edit` | `finance_officer` |
| PUT | `/dashboard/finance/zaha-time/{zahaTimeBooking}` | `role.finance.zaha_time.update` | `finance_officer` |
| GET | `/dashboard/finance/payments` | `role.finance.payments.index` | `finance_officer` |
| POST | `/dashboard/finance/payments` | `role.finance.payments.store` | `finance_officer` |
| PUT | `/dashboard/finance/payments/{payment}` | `role.finance.payments.update` | `finance_officer` |

### مسارات maintenance (13)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/maintenance/requests` | `role.maintenance.requests.index` | `maintenance_officer` |
| GET | `/dashboard/maintenance/requests/create` | `role.maintenance.requests.create` | `maintenance_officer` |
| POST | `/dashboard/maintenance/requests` | `role.maintenance.requests.store` | `maintenance_officer` |
| GET | `/dashboard/maintenance/requests/{maintenanceRequest}/edit` | `role.maintenance.requests.edit` | `maintenance_officer` |
| PUT | `/dashboard/maintenance/requests/{maintenanceRequest}` | `role.maintenance.requests.update` | `maintenance_officer` |
| PATCH | `/dashboard/maintenance/requests/{maintenanceRequest}/close` | `role.maintenance.requests.close` | `maintenance_officer` |
| POST | `/dashboard/maintenance/requests/{maintenanceRequest}/work-details` | `role.maintenance.work_details.store` | `maintenance_officer` |
| PUT | `/dashboard/maintenance/work-details/{maintenanceWorkDetail}` | `role.maintenance.work_details.update` | `maintenance_officer` |
| POST | `/dashboard/maintenance/requests/{maintenanceRequest}/attachments` | `role.maintenance.attachments.store` | `maintenance_officer` |
| DELETE | `/dashboard/maintenance/attachments/{maintenanceAttachment}` | `role.maintenance.attachments.destroy` | `maintenance_officer` |
| GET | `/dashboard/maintenance/approvals` | `role.maintenance.approvals.index` | `maintenance_officer` |
| PUT | `/dashboard/maintenance/approvals/{maintenanceRequest}` | `role.maintenance.approvals.update` | `maintenance_officer` |
| GET | `/dashboard/maintenance` | `role.maintenance_officer.dashboard` | `maintenance_officer` |

### مسارات transport (34)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/transport/vehicles` | `role.transport.vehicles.index` | `transport_officer` |
| GET | `/dashboard/transport/vehicles/create` | `role.transport.vehicles.create` | `transport_officer` |
| POST | `/dashboard/transport/vehicles` | `role.transport.vehicles.store` | `transport_officer` |
| GET | `/dashboard/transport/vehicles/{vehicle}/edit` | `role.transport.vehicles.edit` | `transport_officer` |
| PUT | `/dashboard/transport/vehicles/{vehicle}` | `role.transport.vehicles.update` | `transport_officer` |
| GET | `/dashboard/transport/drivers` | `role.transport.drivers.index` | `transport_officer` |
| GET | `/dashboard/transport/drivers/create` | `role.transport.drivers.create` | `transport_officer` |
| POST | `/dashboard/transport/drivers` | `role.transport.drivers.store` | `transport_officer` |
| GET | `/dashboard/transport/drivers/{driver}/edit` | `role.transport.drivers.edit` | `transport_officer` |
| PUT | `/dashboard/transport/drivers/{driver}` | `role.transport.drivers.update` | `transport_officer` |
| GET | `/dashboard/transport/requests` | `role.transport.requests.index` | `auth-only/no role middleware` |
| POST | `/dashboard/transport/requests` | `role.transport.requests.store` | `auth-only/no role middleware` |
| PATCH | `/dashboard/transport/requests/{transportRequest}/process` | `role.transport.requests.process` | `transport_officer` |
| PATCH | `/dashboard/transport/requests/{transportRequest}/feedback` | `role.transport.requests.feedback` | `auth-only/no role middleware` |
| GET | `/dashboard/transport/trips` | `role.transport.trips.index` | `transport_officer` |
| GET | `/dashboard/transport/trips/create` | `role.transport.trips.create` | `transport_officer` |
| POST | `/dashboard/transport/trips` | `role.transport.trips.store` | `transport_officer` |
| GET | `/dashboard/transport/trips/{trip}/edit` | `role.transport.trips.edit` | `transport_officer` |
| PUT | `/dashboard/transport/trips/{trip}` | `role.transport.trips.update` | `transport_officer` |
| PATCH | `/dashboard/transport/trips/{trip}/close` | `role.transport.trips.close` | `transport_officer` |
| POST | `/dashboard/transport/trips/{trip}/segments` | `role.transport.segments.store` | `transport_officer` |
| GET | `/dashboard/transport/movements` | `role.transport.movements.index` | `transport_officer|movement_manager|movement_editor|movement_viewer|super_admin` |
| GET | `/dashboard/transport/movements/create` | `role.transport.movements.create` | `transport_officer|movement_manager|movement_editor|super_admin` |
| POST | `/dashboard/transport/movements` | `role.transport.movements.store` | `transport_officer|movement_manager|movement_editor|super_admin` |
| GET | `/dashboard/transport/movements/{movementDay}` | `role.transport.movements.show` | `transport_officer|movement_manager|movement_editor|movement_viewer|super_admin` |
| GET | `/dashboard/transport/movements/{movementDay}/edit` | `role.transport.movements.edit` | `transport_officer|movement_manager|movement_editor|super_admin` |
| PUT | `/dashboard/transport/movements/{movementDay}` | `role.transport.movements.update` | `transport_officer|movement_manager|movement_editor|super_admin` |
| DELETE | `/dashboard/transport/movements/{movementDay}` | `role.transport.movements.destroy` | `transport_officer|movement_manager|super_admin` |
| PUT | `/dashboard/transport/segments/{tripSegment}` | `role.transport.segments.update` | `transport_officer` |
| DELETE | `/dashboard/transport/segments/{tripSegment}` | `role.transport.segments.destroy` | `transport_officer` |
| POST | `/dashboard/transport/trips/{trip}/rounds` | `role.transport.rounds.store` | `transport_officer` |
| PUT | `/dashboard/transport/rounds/{tripRound}` | `role.transport.rounds.update` | `transport_officer` |
| DELETE | `/dashboard/transport/rounds/{tripRound}` | `role.transport.rounds.destroy` | `transport_officer` |
| GET | `/dashboard/transport` | `role.transport_officer.dashboard` | `transport_officer` |

### مسارات reports (20)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/reports/overview` | `role.reports.index` | `reports_viewer|followup_officer` |
| POST | `/dashboard/reports/overview/export` | `role.reports.export` | `reports_viewer|followup_officer` |
| GET | `/dashboard/reports/agenda` | `role.reports.agenda.index` | `reports_viewer|followup_officer` |
| POST | `/dashboard/reports/agenda/export` | `role.reports.agenda.export` | `reports_viewer|followup_officer` |
| GET | `/dashboard/reports/monthly` | `role.reports.monthly.index` | `reports_viewer|followup_officer` |
| POST | `/dashboard/reports/monthly/export` | `role.reports.monthly.export` | `reports_viewer|followup_officer` |
| GET | `/dashboard/reports/finance` | `role.reports.finance.index` | `reports_viewer|followup_officer` |
| POST | `/dashboard/reports/finance/export` | `role.reports.finance.export` | `reports_viewer|followup_officer` |
| GET | `/dashboard/reports/maintenance` | `role.reports.maintenance.index` | `reports_viewer|followup_officer` |
| POST | `/dashboard/reports/maintenance/export` | `role.reports.maintenance.export` | `reports_viewer|followup_officer` |
| GET | `/dashboard/reports/transport` | `role.reports.transport.index` | `reports_viewer|followup_officer` |
| POST | `/dashboard/reports/transport/export` | `role.reports.transport.export` | `reports_viewer|followup_officer` |
| GET | `/dashboard/reports/kpis` | `role.reports.kpis.index` | `reports_viewer|followup_officer` |
| POST | `/dashboard/reports/kpis` | `role.reports.kpis.store` | `followup_officer` |
| GET | `/dashboard/reports/enterprise/branch-performance` | `role.reports.enterprise.branch_performance` | `reports_viewer|followup_officer|super_admin` |
| GET | `/dashboard/reports/enterprise/agenda-export` | `role.reports.enterprise.agenda_export` | `reports_viewer|followup_officer|super_admin` |
| GET | `/dashboard/reports/enterprise/monthly-export` | `role.reports.enterprise.monthly_export` | `reports_viewer|followup_officer|super_admin` |
| GET | `/dashboard/reports/enterprise/approval-export` | `role.reports.enterprise.approval_export` | `reports_viewer|followup_officer|super_admin` |
| GET | `/dashboard/reports/enterprise/printable` | `role.reports.enterprise.printable` | `reports_viewer|followup_officer|super_admin` |
| GET | `/dashboard/reports` | `role.reports_viewer.dashboard` | `reports_viewer|followup_officer` |

### مسارات enterprise (2)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/enterprise` | `role.enterprise.dashboard` | `reports_viewer|followup_officer|super_admin` |
| GET | `/dashboard/enterprise/annual-planning` | `role.enterprise.annual_planning` | `reports_viewer|followup_officer|super_admin` |

### مسارات notifications (1)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| PATCH | `/dashboard/notifications/{notification}/read` | `role.notifications.read` | `auth-only/no role middleware` |

### مسارات archive (2)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| POST | `/dashboard/archive/year` | `role.enterprise.archive.year` | `reports_viewer|followup_officer|super_admin` |
| POST | `/dashboard/archive/year/restore` | `role.enterprise.archive.year.restore` | `reports_viewer|followup_officer|super_admin` |

### مسارات staff (3)
| Method | Path | Route Name | Roles |
|---|---|---|---|
| GET | `/dashboard/staff/agenda` | `role.staff.agenda.index` | `staff` |
| GET | `/dashboard/staff/activities` | `role.staff.activities.index` | `staff` |
| GET | `/dashboard/staff` | `role.staff.dashboard` | `staff` |

---

## 6) ملخص الأدوار الموجودة وربط كل دور بالفورمز/الحلقات (Workflow)

## 6.1 الأدوار المعرفة في النظام (RolePermissionSeeder)
`super_admin, relations_manager, relations_officer, programs_manager, programs_officer, finance_officer, maintenance_officer, transport_officer, movement_manager, movement_editor, movement_viewer, executive_manager, followup_officer, communication_head, workshops_secretary, reports_viewer, staff`

## 6.2 جدول الأدوار والمهام والصلاحيات المرتبطة

| الدور | الموديولات المرتبطة | أهم الصفحات/الباثات | نوع الصلاحية العملية |
|---|---|---|---|
| super_admin | إدارة النظام كاملة + lookups + حوكمة | `/dashboard/admin/*` + جزء كبير من modules | إدارة/ضبط/رقابة شاملة |
| relations_manager | الأجندة + مراحل اعتماد | `/dashboard/relations/agenda*`, approvals | إنشاء/اعتماد/نشر ضمن العلاقات |
| relations_officer | إدخال الأجندة/الخطة الشهرية | agenda + monthly routes | إدخال/تحديث/إرسال |
| branch_relations_officer (مستخدم بالكود) | الخطة الشهرية الفرعية ومشاركة الفروع | monthly + branch participation | تشغيل فرعي محدود (يجب توحيد تعريفه رسمياً) |
| programs_officer | مراجعة متطلبات البرامج في الفعاليات | approvals + supplies/team | مراجعة وتنفيذ برامج |
| programs_manager | اعتماد برامج + متابعة التنفيذ | approvals + supplies/team | اعتماد برامجي |
| communication_head | ملاحظات التغطية الإعلامية | communications requests + agenda participation | Notes/coordination |
| workshops_secretary | ملاحظات المشاغل | workshops requests + agenda participation | Notes/coordination |
| executive_manager | اعتماد نهائي للفعاليات | agenda approvals + monthly approvals | Final approval |
| followup_officer | متابعة وتقييم/KPI | reports + kpis + edit/update monthly (حسب routes) | تقييم ومؤشرات |
| maintenance_officer | الصيانة | `/dashboard/maintenance/*` | إدارة دورة الصيانة |
| transport_officer | النقل/السائقين/المركبات/الرحلات/الحركة | `/dashboard/transport/*` | إدارة تشغيل النقل |
| movement_manager/editor/viewer | شاشات الحركة (movements) | `/dashboard/transport/movements*` | إدارة/تعديل/عرض حسب الدور |
| finance_officer | المالية (إضافة على نطاقك الحالي) | `/dashboard/finance/*` | تحصيل/حجوزات/مدفوعات |
| reports_viewer | التقارير | `/dashboard/reports/*`, `/dashboard/enterprise/*` | عرض وتحليل |
| staff | عرض فقط | `/dashboard/staff/*` | استعراض |

## 6.3 ملاحظة ضبط مهمة
- يوجد فرق بين “الأدوار الموجودة في Seeder” و”الأدوار المستخدمة فعلياً داخل route middleware/workflow” (خصوصاً `branch_relations_officer`) ويجب توحيده قبل الإطلاق.

---

## 7) طريقة فحص واختبار المشروع (QA / Test Strategy)

## 7.1 فحوصات سريعة (Static/Structure)
1. فحص المسارات والأدوار:
   - التأكد أن كل route داخل dashboard يحتوي role middleware مناسب (باستثناء ما هو مقصود read-notification).
2. فحص التطابق بين:
   - RolePermissionSeeder
   - Routes middleware roles
   - Workflow services
3. فحص الوثائق المرجعية:
   - اعتماد وثيقة واحدة Canonical للمجال التشغيلي.

## 7.2 فحوصات تشغيلية (عند اكتمال البيئة)
1. تثبيت البيئة:
   - `composer install`
   - `cp .env.example .env`
   - `php artisan key:generate`
   - إعداد DB ثم `php artisan migrate --seed`
2. فحص route map:
   - `php artisan route:list`
3. اختبارات الوحدة/المزايا:
   - `php artisan test`
4. سيناريوات UAT حرجة:
   - Agenda end-to-end (create → participation → approval)
   - Monthly activity flow (sync/manual → approvals → close → evaluation)
   - Transport request flow (request → process → feedback)
   - Maintenance flow (log → work details → approvals → close)

## 7.3 قائمة قبول قبل الإطلاق (Go-Live Checklist)
- [ ] لا يوجد route تشغيلي حساس بدون role middleware.
- [ ] لا يوجد دور مستخدم في routes وغير معرّف رسمياً في RBAC.
- [ ] كل workflow لديه audit trail واضح.
- [ ] اختبار UAT لكل فورم (1-4) ناجح على الأقل في 2 فروع.
- [ ] تصدير تقارير شهرية يعمل ويطابق أرقام KPI.

---

## 8) قرار تنفيذي مقترح الآن

- إذا كان هدفك الحالي هو التركيز على **الفعاليات + الحركة + الصيانة + السائقين**:
  1) نفعل Sprint سريع لإغلاق ثغرات RBAC والـ route middleware.
  2) نوحّد naming الأدوار (خصوصاً branch_relations_officer).
  3) نجمد أي توسعات enterprise/finance غير لازمة مرحلياً.
  4) ننفذ UAT رسمي على الفورمز الأربعة الأساسية.

بهذا تصبح النسخة أكثر أماناً واتساقاً، وقابلة للإطلاق المرحلي بثقة.
