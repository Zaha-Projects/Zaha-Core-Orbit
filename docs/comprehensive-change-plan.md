# خطة التعديلات الشاملة لنظام Zaha Core Orbit

> هذه الوثيقة هي خطة تنفيذ عملية مبنية على المتطلبات التفصيلية المرفقة في `docs/newplan.md` والملفات/النماذج داخل مجلد `docs`، مع تحويلها إلى خارطة تطوير قابلة للتنفيذ خطوة بخطوة.

## 1) قراءة الوضع الحالي (As-Is)

### ما هو موجود فعلاً في المشروع حالياً
- النظام مبني على Laravel مع مسارات Web كثيرة مفصولة حسب أدوار موجودة حالياً (relations/programs/finance/maintenance/transport/reports/staff/super_admin).
- توجد جداول وModels لوحدات: الأجندة، الخطة الشهرية، النقل، الصيانة، الإيرادات، المرفقات، وسجل تدقيق عام.
- يوجد توثيق سابق في `docs/implementation-plan.md` و`docs/database-design.md` و`docs/newplan.md`.

### الفجوات مقابل المتطلبات الجديدة
1. **توسيع وحدة الأجندة** لتدعم هيكل الأعمدة الكبير (الأقسام + جميع الفروع + اعتمادين نهائيين).
2. **فصل أقوى بين "الأجندة العامة" و"خطة الفرع الشهرية"** مع استيراد تلقائي محسّن من الأجندة إلى كل فرع.
3. **إضافة أدوار جديدة/محدثة** مثل:
   - ضابط ارتباط المتابعة (صلاحية تقارير + إدخال تقييم الالتزام).
   - مأمور الحركة بصلاحيات تشغيل كاملة على النقل (مركبات/سائقين/طلبات).
4. **تحسين workflow الاعتماد** مع قواعد القفل قبل 5 أيام (قابلة للإعداد).
5. **تجهيز KPIs** المطلوبة (نسبة الالتزام، كفاءة الحشد، تقييم الفعالية 40/60، تقييم الفرع الشهري).
6. **إعادة تنظيم هيكل المشروع** لتقليل التشتت في Controllers والانتقال إلى هيكلة Domain واضحة.

---

## 2) الهدف من هذه الخطة (To-Be)

إنتاج نسخة MVP منضبطة تُغطي بالكامل:
- الفورم الأول (أجندة زها العامة).
- الفورم الثاني (خطة الفعاليات الشهرية لكل مركز/فرع).
- الفورم الثالث (حركة النقل والسائقين + تقييم الخدمة).
- الفورم الرابع (الصيانة متعدد الجهات).
- نواة التقارير والتقييم الشهري للفروع.

مع إبقاء الفورم الخامس كـ **Module قابل للإضافة** بدون كسر البنية.

---

## 3) التعديلات المقترحة على قاعدة البيانات (Delta Plan)

## 3.1 جداول مرجعية/تنظيمية
- الإبقاء على الجداول الحالية وإضافة/توسيع التالي:
  - `branches`: إضافة حقول تشغيلية (code, is_active, region).
  - `departments`: توحيد مفاتيح القسم (`relations`, `programs`, `communication`, `workshops`, ...).
  - `settings`: جدول إعدادات عامة (مفتاح/قيمة) ويتضمن `monthly_plan_lock_days = 5`.
  - `position_assignments`: ربط المستخدم بدور تشغيلي داخل فرع أو مركز (لتقييد التعديل على حقول معينة).

## 3.2 أجندة زها العامة
- الإبقاء على `agenda_events` وتوسيعها:
  - `event_scope` (general/branch_specific لاحقاً).
  - `event_type` (mandatory/optional).
  - `plan_type` (unified/non_unified).
  - `month_no`, `year` لتسهيل التقويم والفلاتر.
  - `created_by_role_snapshot` لتدقيق من أنشأ الحدث.
- إنشاء/تعديل:
  - `agenda_participations`: تدعم `entity_type` = `branch` أو `department_unit`.
  - `agenda_field_updates`: سجل من عدّل أي عمود حسّاس (مثل لجنة المشاغل، الاتصال، برامج خلدا...).
  - `agenda_approvals`: اعتماد مدير العلاقات + اعتماد المدير التنفيذي + أي مرحلة لاحقة.

## 3.3 خطة الفعاليات الشهرية للفروع
- اعتماد نموذج رئيسي:
  - `branch_monthly_plans` (header للشهر/الفرع).
  - `branch_plan_events` (تفاصيل كل فعالية).
- مع جداول فرعية:
  - `branch_plan_event_partners`
  - `branch_plan_event_requirements`
  - `branch_plan_event_team`
  - `branch_plan_event_media`
  - `branch_plan_approvals`
  - `branch_plan_change_log`
- حقول أساسية يجب التأكيد عليها:
  - `source_type` (agenda/manual)
  - `agenda_event_id` (nullable)
  - `is_in_agenda`
  - `lock_at`
  - `is_official` (يُحتسب آلياً عند تجاوز lock_at أو إغلاق الموافقات).

## 3.4 النقل والحركة
- تحويل من `trips` التقليدي إلى منطق "طلب حركة":
  - `transport_requests` (الطلب الرئيسي)
  - `transport_request_trips` (حتى 3 رحلات أو أكثر مستقبلاً)
  - `transport_request_actions` (قبول/رفض/تعديل + ملاحظات مأمور الحركة)
  - `transport_request_feedback` (تقييم مقدم الطلب بعد التنفيذ)
- جداول الأصول:
  - `vehicles`
  - `drivers`

## 3.5 الصيانة
- تطوير `maintenance_requests` ليغطي دورة كاملة.
- توحيد سجلات العمل متعدد الأدوار عبر:
  - `maintenance_worklogs` مع `owner_role` = `branch_head` / `maintenance_head` / `it_head`.
- إضافة `maintenance_closures` إذا لزم فصل الإغلاق الرسمي عن التنفيذ.

## 3.6 التقارير ومؤشرات الأداء
- إنشاء جداول مُجمّعة لحسابات KPI الشهرية:
  - `branch_kpi_monthly`
  - `compliance_evaluations`
- حسابات صريحة:
  - `attendance_efficiency_percent = actual / expected * 100`
  - `event_score_percent = (0.4 * satisfaction) + (0.6 * commitment)`
  - `branch_monthly_score_percent = avg(event_score_percent)`

---

## 4) تعديل الأدوار والصلاحيات (RBAC Matrix)

## 4.1 الأدوار المستهدفة
- `super_admin`
- `relations_manager`
- `relations_officer`
- `branch_manager` (أو branch_head)
- `programs_manager`
- `programs_officer`
- `communication_head`
- `workshops_secretary`
- `transport_officer` (مأمور الحركة)
- `maintenance_head`
- `it_head`
- `executive_manager`
- `followup_officer` (ضابط ارتباط المتابعة)
- `staff_viewer` (عرض فقط حسب الفرع/القسم)

## 4.2 مبادئ الصلاحيات
1. صلاحيات CRUD + اعتماد + نشر تُفصل Permission-by-Permission.
2. صلاحية الحقول الحساسة تكون **field-level** عبر Policies/Guards داخل الخدمات.
3. أي تعديل بعد اعتماد نهائي = يتطلب سبب + Audit + إعادة مرحلة اعتماد.
4. ضابط المتابعة يمتلك:
   - قراءة شاملة لكل الوحدات.
   - إدخال تقييم الالتزام.
   - مشاهدة تقارير مقارنة الفروع.

---

## 5) إعادة هيكلة المجلدات (Structure Refactor)

## 5.1 المشكلة الحالية
وجود Controllers كثيرة تحت `app/Http/Controllers/Roles/*` يسبب تضخمًا وربطًا مباشرًا بين الدور والمنطق التجاري.

## 5.2 الهيكل المقترح الأفضل (تدريجي)

```text
app/
  Domain/
    Agenda/
      Actions/
      DTOs/
      Models/
      Policies/
      Services/
    BranchPlan/
      Actions/
      DTOs/
      Models/
      Policies/
      Services/
    Transport/
    Maintenance/
    Reports/
    Shared/
  Http/
    Controllers/
      Dashboard/
      Agenda/
      BranchPlan/
      Transport/
      Maintenance/
      Reports/
    Requests/
    Resources/
  Support/
    Enums/
    Helpers/
    Traits/
```

### قواعد التنفيذ
- لا نكسر المسارات الحالية مباشرة.
- نستخدم **Strangler Pattern**:
  - أي شاشة/ميزة جديدة تُبنى على الهيكل الجديد.
  - الشاشات القديمة تُرحّل بالتدريج.

---

## 6) خطة التنفيذ المرحلية (Step-by-Step)

## المرحلة 0: التحضير (2–3 أيام)
- تثبيت قرارات البيانات النهائية:
  - قائمة الفروع الرسمية.
  - قائمة الأقسام الرسمية.
  - حالات الاعتماد الدقيقة.
- اعتماد معادلات KPI النهائية.
- تجهيز ملف Seeds مركزي للمرجعيات.

## المرحلة 1: الأساس (3–5 أيام)
- Migration package #1:
  - settings
  - position_assignments
  - تحسين branches/departments
- تحديث RBAC:
  - إضافة الأدوار الجديدة.
  - بناء صلاحيات granular.

## المرحلة 2: أجندة زها العامة (5–7 أيام)
- Migration package #2 (agenda delta).
- Services:
  - CreateAgendaEvent
  - UpdateParticipation
  - SubmitAgendaApproval
- شاشة Calendar + Grid + فلترة شهر/قسم/فرع.

## المرحلة 3: خطة الفروع الشهرية (7–10 أيام)
- Migration package #3 (branch plans + child tables).
- وظيفة الاستيراد التلقائي من الأجندة.
- Workflow الموافقات التسلسلي.
- Lock mechanism قبل الموعد بـ N أيام (من settings).

## المرحلة 4: النقل والحركة (5–7 أيام)
- Migration package #4 (transport requests).
- إدارة مركبات/سائقين.
- تنفيذ الطلب + تقييم لاحق.

## المرحلة 5: الصيانة (5–7 أيام)
- Migration package #5 (maintenance worklogs/closures).
- نموذج موحّد مع Tabs حسب الجهة (فرع/صيانة/حاسوب).

## المرحلة 6: التقارير وKPI (5–8 أيام)
- Jobs لاحتساب KPI شهريًا.
- لوحات مقارنة الفروع.
- تقارير تصدير Excel/PDF.

## المرحلة 7: التحسينات والتهيئة للإطلاق (3–5 أيام)
- UAT مع أصحاب الأدوار.
- تدقيق الأداء والصلاحيات.
- تجهيز دليل الاستخدام.

---

## 7) أولويات التنفيذ الفعلية (Backlog Ready)

## أولوية P0 (لازم قبل أي شيء)
- RBAC الجديد.
- جدول الإعدادات العام.
- توحيد enum للقيم الأساسية (نوع الفعالية، نوع الخطة، حالة المشاركة، حالة الاعتماد).

## أولوية P1
- أجندة زها العامة كاملة.
- خطة الفروع مع الاستيراد التلقائي + القفل + الموافقات.

## أولوية P2
- النقل.
- الصيانة.
- KPIs الأساسية.

## أولوية P3
- تحسينات UX.
- أرشفة متقدمة.
- النسخة الأولى للفورم الخامس عند اعتماد تفاصيله.

---

## 8) قواعد تقنية إلزامية أثناء التنفيذ
1. **Audit Log** لكل إنشاء/تعديل/اعتماد/رفض.
2. **Soft Deletes** للكيانات التشغيلية المهمة.
3. **DB Transactions** في مسارات الاعتماد الحساسة.
4. **Form Request Validation** صارم لكل فورم.
5. **Policy + Gate** بدل if/else داخل Controllers.
6. **اختبارات**:
   - Feature tests لمسارات الموافقات.
   - Unit tests للحسابات (KPI/evaluation).

---

## 9) القرارات المطلوبة منكم قبل البدء بالتطوير
1. اعتماد أسماء الأدوار النهائية (عربي/إنجليزي) وربطها بالمسميات الوظيفية الفعلية.
2. اعتماد قائمة الفروع الرسمية النهائية (أسماء + أكواد).
3. اعتماد قائمة أصناف الفعالية لكل قسم.
4. تأكيد ما إذا كان التقييم 40/60 ثابت أم متغير من الإعدادات.
5. تحديد آلية رضا الجمهور (نموذج داخلي أم رابط خارجي).
6. تأكيد هل الحد الأقصى للرحلات في طلب النقل = 3 دائمًا أم قابل للزيادة.

---

## 10) كيف سنبدأ "خطوة خطوة" من الآن

### خطوة البدء المقترحة (الدفعة الأولى)
1. اعتماد هذه الخطة.
2. تنفيذ **Migration & RBAC Foundation** فقط.
3. مراجعة الجداول الجديدة معكم على بيانات فعلية (seed).
4. بعدها نبدأ مباشرة في وحدة الأجندة العامة.

> بعد موافقتكم على هذه الخطة، التنفيذ يبدأ بالمرحلة 1 مباشرة.

- مرجع حوكمة الأدوار ومنع التضارب: `docs/role-conflict-analysis-and-kickoff-plan.md`.

- قائمة المتابعة التنفيذية (TODO): `docs/todo-zaha-ops-execution.md`.
