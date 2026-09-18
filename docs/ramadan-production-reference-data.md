> تحديث لاحق: راجع [متابعة مراجعة النطاقات والتشغيل المحلي](ramadan-scope-review.md) للنتائج الأحدث. نتائج التشغيل أدناه تخص الجولة السابقة.

# استكمال البيانات المرجعية للإفطارات ونطاق احتياجات التنفيذ

## نتيجة الفحص قبل التعديل

- كانت جداول `mobilization_methods` و`beneficiary_segments` و`target_groups` و`monitoring_methods` موجودة ومربوطة بالإفطارات، مع Seeders لها. لم تُنشأ جداول بديلة.
- كانت الإرشادات موجودة في `event_guidance_versions`، لكن Seeder السابق كان يلخص الوثيقة ويحذف بعض التفاصيل.
- كانت احتياجات التنفيذ تستخدم `is_monthly_activity` و`is_ramadan_iftar`، بينما النموذج الشهري يعرض الخيارات الثابتة دون الرجوع إلى هذه الأعلام.
- كانت فترة رمضان محفوظة في أربعة مفاتيح في `settings` لسنة واحدة.
- أنواع الاستضافة والموقع ومكونات الوجبة هي discriminators مرتبطة بسلوك التطبيق، فأُبقيت constants الموجودة. استُخدمت الفئات العمرية الموجودة في `beneficiary_segments` دون اختراع حدود عمرية.

## البنية والقرارات

### الإرشادات

المصدر هو `public/تعليمات افطارات رمضان 2026.pdf`، صفحتان. استُخرج النص بـpypdf وطُوبق بصريًا مع صور الصفحتين باستخدام Poppler. عنوان الوثيقة نفسها هو «تعليمات عامه لافطارات رمضان 2025»، رغم أن اسم الملف يذكر 2026. حُفظ العنوان الأصلي، والأقسام العشرة وترتيبها وتفاصيل القاعات والموارد وإبلاغ المتطوعين عند تخصيص/عدم تخصيص الوجبات. اقتصر تنظيف النص على فصل الأسطر والمسافات وأخطاء استخراج الحروف العربية والأرقام.

النص المراجع في `database/seeders/data/ramadan-guidance.json`، وبصمة المصدر:

```text
2b013e47f2d8d584f53cdf4d1af6c1cfc05edbe09a2729c0874abb328c7baf82
```

يتوقف Seeder إذا غاب الملف أو تغيرت بصمته. يُنشئ إصدارًا جديدًا ويعطّل الإصدار السابق، مع الاحتفاظ بنصوصه وموافقاته وربط الإفطارات التاريخية به. إعادة التشغيل لا تنشئ إصدارًا مكررًا ولا تعيد تفعيل إصدار عطّلته الإدارة. النشر الأول للنص الكامل يستلزم قبول المستخدمين للإصدار الجديد وفق نظام الموافقات القائم.

### البيانات المرجعية

- Seeders تستخدم الإدخال الآمن عند غياب المفتاح (`insertOrIgnore`) مع unique constraints، وتحافظ على تعديلات الأسماء والتفعيل والترتيب والأعمار.
- أُضيف `target_groups.code` كرمز فريد nullable. تُربط التسميات الأصلية بالسجل الأقدم عند وجود تكرار قديم، دون حذف أو دمج IDs مستخدمة. تبقى السجلات المخصصة القديمة دون رمز؛ لا تُفترض مطابقة أسماء عُدلت سابقًا.
- أُضيف `ramadan_iftar_gift_types` للقيم الموجودة فقط: `gifts`, `shields`, `both`. حُفظ حقل `ramadan_iftar_gifts.gift_type` وقيمه العامة، وأصبحت خيارات النموذج والتحقق تُقرأ من المرجع الفعال. تُدرج migration القيم الحالية للحفاظ على عمل النموذج دون انتظار Seeder.
- أُضيف `RamadanReferenceDataSeeder` لتجميع Seeders المطلوبة فقط؛ لا ينشئ مستخدمين أو إفطارات تجريبية أو فروعًا أو workflows.

### نطاق احتياجات التنفيذ

`ExecutionNeedType.usage_scope` accessor/mutator بأربع constants، يستخدم الحقلين الموجودين كمصدر تخزين واحد:

| usage_scope | is_monthly_activity | is_ramadan_iftar |
|---|---:|---:|
| monthly_plans | 1 | 0 |
| iftars | 0 | 1 |
| both | 1 | 1 |
| none | 0 | 0 |

لم يُضف عمود مكرر يمكن أن يختلف عن الأعلام القديمة. تُستخدم scopes الحالية ومطابقة أسماء الطلب الشهري القديمة مركزيًا في النموذج. تُخفى الخيارات والتفاصيل غير المتاحة من نموذج التخطيط الشهري، ويُرفض إرسال اختيار غير متاح من الخادم أيضًا، بما فيه أجزاء الشهادات/كتب الشكر وفريق التنفيذ. يظل النموذج الشهري القديم قادرًا على العمل عند غياب صفوف الكتالوج؛ هذه طبقة توافق مع خياراته السابقة، ولا تتجاوز صفًا مضبوطًا صراحةً على `none` أو غير فعال.

تُرقّي migration أنواع الكتالوج القديمة المعروفة وغير canonical إلى استعمالها السابق، وتحافظ على نطاقات الصفوف canonical الموجودة. يبقى default الأعلام القديمة `false/false` للأنواع الجديدة غير المضبوطة. لا تُحذف السجلات التاريخية أو بيانات الموافقات عند تعديل الكتالوج.

تعديل النطاق متاح في صفحة إعدادات الموقع المحمية بـ`super_admin`. أسماء حقول الخطط الشهرية والـroutes والصلاحيات القديمة باقية. يُسمح بإرسال إفطار عندما لا توجد احتياجات إلزامية متاحة، بدل اعتباره خطأ بسبب خلو القائمة.

### فترات رمضان

جدول `ramadan_periods`: `year` فريد، `start_date`, `end_date`, `is_active` وtimestamps. السنة هي **معرّف الموسم الميلادي المستخدم سابقًا** والتواريخ ميلادية؛ لا يوجد تحويل هجري مفترض. يوجد فهرس للفترات الفعالة وCHECK لترتيب التاريخين على MySQL.

- تنقل migration إعدادات الموسم القديمة إذا كانت كاملة وصالحة؛ لا تخمّن الفترة الناقصة أو غير الصالحة.
- Seeder يحافظ على تواريخ الجدول المعدلة، ويرفض الإعدادات القديمة الجزئية. عند غياب المفاتيح الأربعة تمامًا، يحتفظ بالقيم الافتراضية الموجودة سابقًا في المشروع: `2026-02-18` إلى `2026-03-19`؛ ليست هذه تواريخ جديدة مشتقة من تحويل تقويم.
- يمكن إضافة سنة أو تعديل سنة محفوظة من صفحة إعدادات الموقع. يبقى معرّف آخر موسم محفوظ في مفاتيح الإعدادات للتوافق مع dashboard الحالي، وتُحدَّث المرآة القديمة مع الجدول داخل transaction.
- الجدول هو مصدر التحقق من تاريخ الإفطار، وتُقبل الحدود inclusive لكل فترة فعالة. تعرض نماذج الإفطار جميع الفترات الفعالة بدل قصر واجهة التاريخ على موسم واحد.
- التعديل المباشر لمفاتيح `settings` القديمة بعد الترحيل لا يعدّل الجدول؛ استخدم شاشة الإدارة أو نموذج `RamadanPeriod`.

## الملفات

Migrations جديدة:

- `database/migrations/2026_09_16_000100_create_ramadan_periods_table.php`
- `database/migrations/2026_09_16_000200_add_guidance_source_hash.php`
- `database/migrations/2026_09_16_000300_preserve_monthly_execution_need_catalogue.php`
- `database/migrations/2026_09_16_000400_add_target_group_reference_codes.php`
- `database/migrations/2026_09_16_000500_create_ramadan_iftar_gift_types_table.php`

Models وSupport:

- `app/Modules/Events/Models/ExecutionNeedType.php`
- `app/Modules/Events/Models/TargetGroup.php`
- `app/Modules/Events/Models/RamadanIftarGift.php`
- `app/Modules/Events/Models/RamadanIftarGiftType.php` (جديد)
- `app/Modules/Events/Models/RamadanPeriod.php` (جديد)
- `app/Modules/Events/Support/RamadanPeriod.php`

Controllers وRequests وServices:

- `app/Http/Controllers/Roles/SuperAdmin/SiteSettingsController.php`
- `app/Modules/Events/Http/Controllers/MonthlyActivities/MonthlyActivityPlanningController.php`
- `app/Modules/Events/Http/Controllers/Ramadan/RamadanIftarController.php`
- `app/Modules/Events/Http/Requests/Ramadan/StoreRamadanIftarRequest.php`
- `app/Modules/Events/Services/RamadanIftarSubmissionService.php`

Seeders: `BeneficiarySegmentSeeder`, `CanonicalExecutionNeedTypeSeeder`, `MobilizationMethodSeeder`, `MonitoringMethodSeeder`, `RamadanIftarGuidanceSeeder`, `RamadanPeriodSeeder`, `TargetGroupSeeder`؛ والجديدان `RamadanIftarGiftTypeSeeder`, `RamadanReferenceDataSeeder`، وملف النص المراجع JSON.

الواجهات:

- `resources/views/pages/admin/site-settings/index.blade.php`
- `resources/views/pages/events/ramadan/_execution_need_details.blade.php`
- `resources/views/pages/events/ramadan/_form.blade.php`
- `resources/views/pages/monthly_activities/activities/_form.blade.php`

الاختبارات: `RamadanProductionReferenceTest` (جديد)، `RamadanPeriodAndGuidanceTest`, `TargetGroupSelectionTest`.

## التشغيل

بعد مراجعة التغييرات على staging:

```bash
php artisan migrate --force
php artisan db:seed --class=RamadanReferenceDataSeeder --force
```

لا يلزم تشغيل `DatabaseSeeder` أو `RamadanIftarStagingSeeder` أو Demo Seeder لهذه التغييرات. يجب تضمين ملف PDF وملف JSON المراجع في الحزمة المنشورة. لا تستخدم `migrate:fresh` على الإنتاج.

## التحقق المنفذ

استُخدم PHP 8.3.30 وMySQL 8.4.3 وقاعدتا اختبار منفصلتان عن قاعدة التطبيق. لم تُنفذ migrations أو seeders على قاعدة التطبيق.

- المجموعة المركزة النهائية: **31 اختبارًا، 148 assertion، جميعها ناجحة**. تشمل idempotency وحفظ تعديلات الإدارة، النطاقات الأربعة وعرض النماذج ورفض التلاعب، تفرد السنة، الحدود الزمنية، الإعدادات الجزئية، المراجع المعطلة، الفصل بين الفروع، صلاحيات الإدارة، إصدارات الإرشادات والمخطط الجديد للفئات.
- المجموعة الموسعة `Ramadan|Monthly|ExecutionNeed|EventReference`: **230 اختبارًا، 1850 assertion، 14 error و24 failure**. شُغلت أيضًا على نسخة HEAD معزولة قبل التعديل: **217 اختبارًا، 1739 assertion، 20 error و24 failure**. مقارنة أسماء الاختبارات أثبتت عدم وجود إخفاق جديد؛ تحسنت 6 حالات وبقيت 38 مشكلة سابقة. المجموعة الموسعة سبقت إضافة اختبار الإعدادات الجزئية الأخير، الذي نجح في المجموعة المركزة النهائية.
- نجح `php -l` للـ28 ملف PHP المعدلة/الجديدة، وتجميع وفحص PHP للقوالب الأربعة المعدلة، و`git diff --check`.
- لا توجد أدوات PHPStan/Psalm/Pint/PHPCS محلية مهيأة في المشروع؛ لم يُدّع تشغيلها.
- تعذر استخدام SQLite للمجموعة الكاملة بسبب migration قديمة تستخدم `SHOW COLUMNS` الخاص بـMySQL، لذلك استُخدم MySQL للاختبارات الفعلية.

إعادة المجموعة المركزة، بعد ضبط اتصال **قاعدة اختبار مستقلة**:

```bash
php vendor/phpunit/phpunit/phpunit --do-not-cache-result --filter "RamadanProductionReferenceTest|RamadanPeriodAndGuidanceTest|RamadanReferenceFoundationTest|CommonEventExecutionNeedsFoundationTest|RamadanIftarBusinessReconciliationTest|TargetGroupSelectionTest"
```

## نقاط مراجعة الفريق

- اعتماد أن الوثيقة ذات اسم 2026 وعنوان 2025 هي المرجع المقصود. لم تُغيّر سنة العنوان.
- اعتماد تواريخ 2026 الموجودة سابقًا في المشروع وإدخال مواسم لاحقة من الإدارة؛ لم تُخترع تواريخ لسنوات جديدة.
- معالجة الإخفاقات السابقة في مجموعة الانحدار؛ لا تعني نتيجة المجموعة المركزة أن المشروع كله اجتاز الاختبارات أو أن اعتماده للإنتاج قد اكتمل.
- أي فئات قديمة متكررة أو معاد تسميتها تُراجع تجاريًا قبل دمجها؛ لم يُحذف أي سجل أو يُفترض أنه مرادف لسجل آخر.
