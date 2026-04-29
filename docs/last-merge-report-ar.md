# تقرير تفصيلي عن الميرج (من آخر 2 ميرج)

## الميرج المطابق لوصفك
- **Merge Commit:** `4dbffca167ec8ffe8b8cfbdc384928a590e56eeb`
- **التاريخ:** 2026-04-29
- **العنوان:** `Apply agenda event fields to monthly activities, update locked fields and forms`
- هذا هو الميرج الذي يحتوي على:
  1) **إضافة بوب-أب داخل نموذج الأجندة السنوية** (unified template modal).
  2) **تعديل آلية الحقول المقفلة (locked fields)** في الخطة الشهرية المرتبطة بالأجندة.

## البرانش/الكوميتات التي دخلت في هذا الميرج
هذا الميرج ضم كومتين أساسيين:

1. `186b1e796ef8c83834164fa8357289ce71b41618`
   - **Message:** `Enforce agenda-sourced monthly core fields and remove obsolete plan fields`
   - **أثره:** فرض قيم الحقول الأساسية القادمة من الأجندة على الخطة الشهرية، وتحديد حقول مقفلة للفرع عند كون النشاط موحدًا وإلزاميًا.

2. `0b377461e76825be11aa61e1266f8fb2c7a3bee3`
   - **Message:** `Add unified monthly template popup and propagate unified agenda plans`
   - **أثره:** إضافة نافذة منبثقة (Modal/Popup) لإدخال قالب الخطة الموحدة وربطها بسريان بيانات الأجندة.

## الملفات المتأثرة داخل هذا الميرج
- `app/Http/Controllers/Web/Agenda/AgendaEventsController.php`
- `app/Http/Controllers/Web/MonthlyActivities/MonthlyActivitiesController.php`
- `config/monthly_activity.php`
- `resources/views/pages/agenda/events/_form.blade.php`
- `resources/views/pages/monthly_activities/activities/edit.blade.php`

## تفاصيل التعديل الوظيفي
### 1) البوب-أب داخل الأجندة السنوية
- تمت إضافة قسم خاص بالقالب الموحد ضمن الفورم.
- تمت إضافة Modal باسم `unifiedTemplateModal` مع زر فتح وإغلاق.
- تمت إضافة منطق JavaScript لإظهار القسم والبوب-أب عند اختيار `plan_type = unified` والتحقق من تعبئة الحقول قبل الإرسال.

### 2) تعديل الـ Locked Fields
- تمت مركزة قائمة الحقول المقفلة ضمن إعدادات:
  - `config/monthly_activity.php` تحت `unified_branch_edit.locked_fields`.
- في `MonthlyActivitiesController` تمت إضافة/استخدام دوال:
  - `unifiedLockedFields()`
  - منطق يفرض القيم من الأجندة عند كون النشاط موحدًا وإلزاميًا.
  - حماية من تعديل حقول معينة من الفرع (مثل title/date/agenda linkage وغيرها حسب الإعداد).

## لماذا هذا هو الميرج الصحيح؟
لأنه يجمع بشكل مباشر بين:
- **Popup موحد داخل نموذج الأجندة** (واضح من ملف `_form.blade.php` في الأجندة).
- **تعديلات locked fields** (واضحة من `config/monthly_activity.php` ومنطق `MonthlyActivitiesController`).
