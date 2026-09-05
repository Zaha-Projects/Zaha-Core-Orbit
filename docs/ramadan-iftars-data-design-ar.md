# التصميم المعدّل للإفطارات الرمضانية والجداول المشتركة

## 1. القرار المعماري الملزم

الإفطارات الرمضانية **لن ترتبط بجدول `monthly_activities` ولن تستخدمه كسجل أب**. لكل إفطار سجل مستقل في جدول `ramadan_iftars`، ويرتبط مباشرة بحدث الأجندة السنوية في `agenda_events` عند وجوده.

يبقى الفصل كالتالي:

- `agenda_events`: تعريف الحدث في الأجندة السنوية ونوعه.
- `monthly_activities`: الخطط والأنشطة الشهرية فقط.
- `ramadan_iftars`: تخطيط الإفطار وتنفيذه الفعلي، مستقل تماماً عن النشاط الشهري.
- الجداول المشتركة: تفاصيل متكررة يمكن أن تخص نشاطاً شهرياً أو إفطاراً أو بازاراً مستقبلاً، باستخدام علاقة polymorphic عامة.

هذا الفصل يمنع تداخل دورة حياة الإفطار مع الحقول الكثيرة الخاصة بالخطة الشهرية، وفي الوقت نفسه يمنع نسخ جداول الفئات المستهدفة والاحتياجات والفرق واللوازم لكل نوع فعالية.

## 2. نمط الربط المشترك

### 2.1 العلاقة polymorphic

كل جدول تفاصيل مشترك يحتوي:

- `subject_type`: اسم ثابت وقصير لنوع الأصل، مثل `monthly_activity` أو `ramadan_iftar` أو `bazaar`.
- `subject_id`: رقم سجل الأصل.
- فهرس مركب على (`subject_type`, `subject_id`).

في Laravel يجب تعريف `Relation::enforceMorphMap()` حتى لا تحفظ أسماء PHP الكاملة داخل قاعدة البيانات، وحتى لا يؤدي تغيير namespace إلى كسر البيانات.

أمثلة:

| `subject_type` | `subject_id` | المعنى |
|---|---:|---|
| `monthly_activity` | 125 | التفاصيل تخص نشاطاً شهرياً |
| `ramadan_iftar` | 31 | التفاصيل تخص إفطاراً رمضانياً |
| `bazaar` | 8 | التفاصيل تخص بازاراً مستقبلياً |

لا يمكن لقاعدة البيانات إنشاء Foreign Key تقليدي من `subject_id` إلى أكثر من جدول. لذلك يلزم:

1. التحقق من وجود الأصل في Service موحدة قبل الإنشاء.
2. حذف التفاصيل ضمن transaction عند حذف الأصل.
3. اختبارات تمنع السجلات اليتيمة.
4. تطبيق صلاحيات وعزلة الفرع من خلال الأصل، لا من خلال `subject_id` وحده.

### 2.2 حقول `is_monthly_activity` و`is_ramadan_iftar`

طلب العمل يسمح بإضافة أعلام مثل `is_monthly_activity` و`is_ramadan_iftar` إلى **تعريفات الأنواع**. هذا مقبول في المرحلة الحالية، مثلاً في `execution_need_types`، لكن الأفضل على المدى الطويل جدول ربط عام لأن إضافة البازارات ستؤدي إلى عمود جديد لكل نوع.

الخيار الموصى به:

```text
feature_definitions / lookup table
    id
    ...

lookup_applicabilities
    lookup_type       // execution_need_type, target_group, supply_type...
    lookup_id
    subject_type      // monthly_activity, ramadan_iftar, bazaar
```

إذا اختيرت الأعلام في المرحلة الأولى، تضاف:

- `is_monthly_activity` boolean default false.
- `is_ramadan_iftar` boolean default false.
- `is_bazaar` boolean default false عند تنفيذ البازارات.

**الأعلام أو جدول الربط تستخدم فقط لتحديد ظهور الخيار في النماذج**. أما القيم المختارة فعلياً فتخزن في جداول التفاصيل polymorphic.

## 3. جدول الإفطار المستقل

### 3.1 `ramadan_iftars`

هذا هو السجل الأب للإفطار ولا يحتوي `monthly_activity_id`.

| العمود | النوع | الغرض |
|---|---|---|
| `id` | bigint | المفتاح الرئيسي |
| `agenda_event_id` | FK nullable/unique | الربط المباشر بالأجندة السنوية |
| `branch_id` | FK | نطاق الفرع |
| `title` | string | اسم الإفطار |
| `description` | text nullable | الوصف |
| `relations_officer_id` | FK users | مسؤول العلاقات المكلف |
| `created_by` | FK users | منشئ الطلب |
| `planned_date` | date | التاريخ المخطط |
| `actual_date` | date nullable | التاريخ الفعلي |
| `time_from`, `time_to` | time nullable | وقت الإفطار/الفعالية |
| `location_type` | string | داخلي/خارجي |
| `location_name` | string nullable | اسم المكان |
| `address` | text nullable | العنوان |
| `google_maps_url` | string nullable | الموقع |
| `contact_name`, `contact_phone` | string nullable | تواصل المكان |
| `supporting_entity_name` | string nullable | الجهة الداعمة كنص حر حسب المطلوب |
| `host_type` | string | جمعية/مركز/مجتمع محلي |
| `community_organization_id` | FK nullable | جمعية أو مركز |
| `local_community_id` | FK nullable | مجتمع محلي |
| `mobilization_method_id` | FK nullable | طريقة الحشد |
| `mobilization_method_other` | text nullable | توضيح «أخرى» |
| `planned_meals_count` | unsigned integer | عدد الوجبات المخطط |
| `actual_meals_count` | unsigned integer nullable | العدد الفعلي |
| `expected_attendance` | unsigned integer | إجمالي مخطط مشتق من التفاصيل |
| `actual_attendance` | unsigned integer nullable | إجمالي فعلي مشتق من كشف الحضور |
| `status` | string | حالة الخطة |
| `execution_status` | string | مخطط/منفذ/مؤجل/ملغى/مغلق |
| `version_number` | unsigned integer | نسخة الخطة |
| `parent_version_id` | self FK nullable | النسخة السابقة |
| `guidance_version_id` | FK | نسخة الإرشادات المقبولة |
| `guidance_accepted_at` | timestamp | وقت الإقرار |
| `submitted_at`, `approved_at`, `closed_at` | timestamp nullable | دورة الحياة |
| timestamps + soft deletes | | التدقيق والحذف المنطقي |

يحتفظ الجدول فقط بالحقول الأساسية والإجماليات المفيدة للبحث. الوجبات والهدايا والفئات والفرق والاحتياجات واللوازم لا تضاف كأعمدة إليه.

## 4. الجداول المرجعية المشتركة

### 4.1 الفئات المستهدفة: `target_groups`

الجدول موجود حالياً ويعاد استخدامه لجميع الأنواع. القيم الأولية:

- أيتام.
- عائلات فقيرة.
- أخرى.

الحقول الحالية (`name`, `is_other`, `is_active`, `sort_order`) مناسبة. يضاف تحديد قابلية الاستخدام بإحدى طريقتين:

- الموصى بها: `lookup_applicabilities`.
- البديل المباشر: `is_monthly_activity`, `is_ramadan_iftar`.

لا يوضع نص «أخرى» في جدول التعريف؛ النص خاص بكل إفطار/نشاط ويحفظ في سجل الاختيار `subject_target_groups.custom_text`.

### 4.2 شرائح المستفيدين والأعمار: `beneficiary_segments`

المصطلحات المطلوبة «أطفال، شباب، يافعين، سيدات، أخرى» ليست كلها أعماراً؛ «سيدات» شريحة جنس/مستفيد. لذلك الاسم الأنسب `beneficiary_segments` بدلاً من `age_groups` فقط.

| العمود | النوع |
|---|---|
| `code` | string unique |
| `name_ar`, `name_en` | string |
| `dimension` | `age`, `gender`, `social`, `other` |
| `minimum_age`, `maximum_age` | tiny integer nullable |
| `is_other` | boolean |
| `is_active`, `sort_order` | boolean/integer |

القيم الأولية تضاف بعد حسم الحدود والتداخل بين طفل/يافع/شاب. ويمكن إبقاء «سيدات» كبعد `gender` مع عدم ادعاء أنها عمر.

### 4.3 احتياجات التنفيذ: `execution_need_types`

الجدول موجود ويحتوي الكود والاسم والوصف والترتيب والحالة. يضاف له إما:

- `is_monthly_activity` و`is_ramadan_iftar` الآن؛ أو
- ربطه بجدول `lookup_applicabilities`، وهو الموصى به.

أمثلة خيارات مشتركة: متطوعون، مخاطبات رسمية، تغطية إعلامية، مواصلات، صيانة، هدايا، دعوات، ولوازم. يمكن للإدارة تفعيل خيار لنوع وإخفاؤه عن نوع آخر بلا تعديل برمجي.

### 4.4 طرق الحشد والرصد

جداول lookup مشتركة:

- `mobilization_methods`: طريقة حشد المجتمع المحلي، مع `is_other`, `is_active`, `sort_order` وقابلية الاستخدام.
- `monitoring_methods`: طريقة المتابعة، والقيم الأولية «متسوق خفي» (التسمية تحتاج تأكيداً)، «كاميرات»، «زيارة ميدانية».

### 4.5 الجهات والمجتمعات

- `community_organizations`: جمعية أو مركز، الاسم، ضابط الارتباط، الهاتف، الموقع، رابط الخريطة، الفرع، والحالة.
- `local_communities`: اسم المجتمع، الفرع، الموقع، ضابط الارتباط ووسيلة التواصل.

هذه مراجع مشتركة ويمكن استخدامها لاحقاً في النشاط الشهري والبازار، بينما اختيار الجهة في كل فعالية يخزن في الأصل أو جدول مشاركة جهات مشترك إذا سمح بأكثر من جهة.

## 5. جداول التفاصيل التشغيلية المشتركة

### 5.1 اختيار الفئات والأعداد: `subject_target_groups`

هذا الجدول يجمع الفئة المستهدفة وشريحة المستفيد والعدد، ويخدم النشاط الشهري والإفطار والبازار.

| العمود | النوع | الملاحظة |
|---|---|---|
| `subject_type`, `subject_id` | morph | الأصل |
| `target_group_id` | FK | أيتام/عائلات فقيرة/أخرى |
| `target_group_custom_text` | string nullable | إلزامي عند اختيار «أخرى» |
| `beneficiary_segment_id` | FK | أطفال/يافعون/شباب/سيدات/أخرى |
| `segment_custom_text` | string nullable | إلزامي عند اختيار «أخرى» |
| `planned_count` | unsigned integer | العدد المخطط من هذه الفئة |
| `actual_count` | unsigned integer nullable | العدد الفعلي |
| `notes` | text nullable | توضيحات |

مثال: أيتام + أطفال = 40، عائلات فقيرة + سيدات = 25. مجموع `planned_count` يزامن إجمالي الحضور المخطط في الأصل، ومجموع `actual_count` يقارن بكشف الحضور.

### 5.2 الاحتياجات المختارة: `subject_execution_needs`

بدلاً من `execution_needs_payload` JSON أو جدول خاص بكل نوع:

| العمود | النوع |
|---|---|
| `subject_type`, `subject_id` | morph |
| `execution_need_type_id` | FK |
| `is_required` | boolean |
| `planned_details` | text nullable |
| `responsible_user_id` | FK nullable |
| `required_by` | date nullable |
| `status` | pending/in_progress/available/unavailable/completed |
| `actual_details` | text nullable |
| `completed_at` | timestamp nullable |

قيد فريد على (`subject_type`, `subject_id`, `execution_need_type_id`). التفاصيل المنظمة لبعض الأنواع، مثل المتطوعين والهدايا، تبقى في جداولها المتخصصة ولا تحشر في `planned_details`.

### 5.3 فرق التنفيذ: `execution_teams` و`execution_team_members`

**`execution_teams`:**

- `subject_type`, `subject_id`.
- `name`.
- `leader_user_id` nullable.
- `planned_members_count`, `actual_members_count`.
- `notes`.

**`execution_team_members`:**

- `execution_team_id`.
- `user_id` nullable للموظف المسجل.
- `member_name`, `phone` nullable للعضو الخارجي.
- `role_name`.
- `task_description`.
- `task_completed` nullable.
- `actual_task_note` nullable.
- `confirmed_by`, `confirmed_at` nullable.

يستبدل هذا التصميم `monthly_activity_team` مستقبلاً أو يرحل بياناته إليه. وبهذا تصبح الفرق مشتركة مع إثبات هل أدى العضو/الفريق مهامه بعد التنفيذ.

### 5.4 احتياجات المتطوعين: `subject_volunteer_requirements`

- `subject_type`, `subject_id`.
- `beneficiary_segment_id` nullable لتمثيل العمر/الشريحة.
- `gender` nullable.
- `planned_count`, `actual_count`.
- `tasks_summary`.
- `status`.

يسمح بأكثر من صف للأصل، بخلاف القيد الحالي الذي يسمح بحاجة متطوعين واحدة للنشاط الشهري.

### 5.5 اللوازم: `subject_supplies`

- `subject_type`, `subject_id`.
- `item_name`.
- `planned_quantity`, `actual_quantity` nullable.
- `is_available`.
- `provider_type`, `provider_name` nullable.
- `estimated_value` decimal nullable.
- `status`, `notes`.

يمكن ترحيل `monthly_activity_supplies` إلى هذا الجدول. لا تخلط الوجبات أو الهدايا باللوازم لأن لكل منها تفاصيل مختلفة وتقارير مستقلة.

### 5.6 المرفقات

جدول `attachments` الحالي polymorphic بالفعل (`attachable_type`, `attachable_id`) ويجب إعادة استخدامه للإفطار والوجبات وتقارير المتابعة بدلاً من إنشاء جدول مرفقات جديد. يوصى بإضافة `category` لتمييز صورة، فاتورة، كشف حضور، إثبات تسليم، أو ملف خطة.

### 5.7 الداعمون والشركاء (اختياري مشترك)

إذا كان للإفطار أكثر من داعم أو كانت الجهة الداعمة تختلف بين الوجبات والهدايا، ينشأ:

- `stakeholders`: مرجع جهة اختياري، مع السماح باسم نصي عند عدم وجودها.
- `subject_stakeholders`: `subject_type`, `subject_id`, `stakeholder_id` أو `free_text_name`, `role` (داعم/مستضيف/مستفيد/شريك), `estimated_contribution`, `notes`.

أما إذا أكد العمل وجود جهة داعمة واحدة فقط، يبقى `supporting_entity_name` في `ramadan_iftars` دون تعقيد زائد.

## 6. جداول خاصة بالإفطار فقط

هذه البيانات لا معنى عاماً مؤكداً لها في النشاط الشهري، ولذلك تبقى مرتبطة مباشرة بـ `ramadan_iftars`.

### 6.1 كشف الحضور: `ramadan_iftar_attendees`

- `ramadan_iftar_id` FK.
- `full_name`, `phone`, `age`.
- `target_group_id`, `beneficiary_segment_id` nullable.
- `attended`, `checked_in_at`, `notes`.

لا يجعل الهاتف unique لأن أفراد الأسرة قد يستخدمون رقم ولي أمر واحد. بيانات الاسم والهاتف والعمر حساسة وتحتاج صلاحية تصدير وسياسة احتفاظ وإخفاء جزئي للهاتف.

### 6.2 الوجبات: `ramadan_iftar_meals` و`ramadan_iftar_meal_items`

`ramadan_iftar_meals`:

- `ramadan_iftar_id`.
- وصف الوجبة.
- الكمية المخططة والفعلية.
- المصدر ونوعه واسمه.
- اسم المطعم ورقم التواصل.
- القيمة التقديرية.
- تقييم الوجبة وملاحظة التقييم.

`ramadan_iftar_meal_items`:

- الوجبة الأب.
- اسم الطبق/الصنف.
- النوع: رئيسي، مرفق، شراب، حلوى، أخرى.
- الكمية والملاحظات.

الـ placeholder مثل «قيّم جودة وكمية وتغليف ووقت وصول الوجبة» يعالج في الواجهة/الترجمة، وليس كعمود قاعدة بيانات.

### 6.3 الهدايا: `ramadan_iftar_gifts`

- `ramadan_iftar_id`.
- الوصف.
- العدد المخطط والفعلي.
- هل لها جهة داعمة.
- اسم الجهة الداعمة.
- قيمة القطعة والقيمة الإجمالية التقديرية.

### 6.4 فقرات الفعالية: `ramadan_iftar_program_segments`

- `ramadan_iftar_id`.
- الاسم، وقت البداية والنهاية/المدة، الترتيب.
- منفذ داخلي (`executor_user_id`) أو اسم منفذ خارجي.
- حالة التنفيذ والملاحظة الفعلية.

## 7. ما بعد التنفيذ والمطابقة المشتركة

### 7.1 تقارير الرصد: `monitoring_reports`

يجعل جدولاً مشتركاً:

- `subject_type`, `subject_id`.
- `monitoring_method_id`.
- `monitor_user_id`.
- `observed_at`, `general_notes`, `submitted_at`.
- `status`: مسودة/مرسل/معاد/معتمد.

وبذلك يستطيع مستخدم المتابعة رصد نشاط شهري أو إفطار بنفس الآلية ومن دون ربط الجدولين ببعضهما.

### 7.2 المطابقة: `field_verifications`

- `monitoring_report_id`.
- `detail_type`, `detail_id` nullable لتحديد وجبة/هدية/فريق/فئة/لوازم.
- `field_key`, `field_label`.
- `planned_value`, `actual_value` JSON للقيمة المفردة فقط.
- `match_status`: مطابق/غير مطابق/لم يرصد/لا ينطبق.
- `note`, `verified_by`, `verified_at`.

استخدام JSON هنا مقبول للقيمة snapshot لأنه يحفظ قيمة التدقيق التاريخية، وليس بديلاً عن جداول البيانات التشغيلية المنظمة. لا يعدّل مستخدم المتابعة الخطة المعتمدة؛ يسجل الفعلي والفرق فقط.

### 7.3 التقييم

يعاد استخدام بنية `evaluation_forms`, `evaluation_questions`, والإجابات بعد جعل علاقة التقييم عامة (`subject_type`, `subject_id`) بدلاً من اقتصارها على `monthly_activity_id`. ينشأ نموذج «تقييم الإفطار الرمضاني» يتضمن تقييم الوجبة، التنظيم، المكان، كفاية الكميات، وأداء الفريق.

## 8. الجداول والآليات الحالية: القرار النهائي

| الموجود | القرار |
|---|---|
| `agenda_events` | يبقى مشتركاً؛ `ramadan_iftars.agenda_event_id` يرتبط به مباشرة |
| `monthly_activities` | لا علاقة له بالإفطار ولا يضاف إليه `ramadan_iftar_id` |
| `target_groups` | يعاد استخدام تعريفاته مع قابلية ظهور حسب النوع |
| `execution_need_types` | يعاد استخدامه وتضاف applicability flags/pivot |
| `monthly_activity_team` | يرحل تدريجياً إلى فرق تنفيذ polymorphic مشتركة |
| `monthly_activity_volunteer_needs` | يرحل إلى متطلبات متطوعين polymorphic متعددة الصفوف |
| `monthly_activity_supplies` | يرحل إلى لوازم polymorphic مشتركة |
| `monthly_activity_attachments` | لا يستخدم للإفطار؛ الأفضل توحيد الجميع على `attachments` الحالي |
| `monthly_activity_followups` | يبقى للقديم؛ المتابعة الجديدة تستخدم `monitoring_reports` المشترك |
| `post_execution_verifications` | يرحل/يعمم إلى `field_verifications` المشترك |
| Dynamic workflows | يعاد استخدامه لأنه يعتمد `entity_type` و`entity_id` ولا يحتاج ربطاً بالنشاط الشهري |
| Workflow action logs | يعاد استخدامه لأنه عام حسب module/entity |
| طلبات التعديل والحذف | توحّد لاحقاً في جداول طلبات polymorphic بدلاً من جدول لكل نوع |

## 9. سير عمل الإفطار المستقل

1. عرض نسخة الإرشادات الفعالة وقبولها قبل فتح الإنشاء.
2. إنشاء `ramadan_iftars` وإدخال الموقع والجهة المستضيفة/الداعمة والتاريخ.
3. إدخال الفئات والأعداد والاحتياجات والفرق والمتطوعين واللوازم من الجداول المشتركة.
4. إدخال الوجبات والهدايا والفقرات من جداول الإفطار الخاصة.
5. التحقق من المجاميع والحقول الشرطية ثم إرسال الإفطار إلى workflow ديناميكي مستقل module=`ramadan_iftars` وبنفس ترتيب أدوار الخطط الشهرية.
6. طلبات التعديل والحذف تنتج نسخة إفطار جديدة وتحافظ على النسخة المعتمدة وسجل الموافقات.
7. بعد التنفيذ يسجل مستخدم المتابعة طريقة الرصد، الحضور الفعلي، الكميات، الفرق، تقييم الوجبة وإنجاز مهام الفريق.
8. لا يغلق الإفطار حتى تحسم المطابقات والبيانات المطلوبة ويعتمد تقرير المتابعة.

## 10. قواعد تمنع تضخم الجداول

- الحقل الذي يتكرر بعدد غير ثابت يكون صفوفاً في جدول تفاصيل، لا أعمدة مثل `target_group_1` و`target_group_2`.
- التعريفات التي يديرها الأدمن تكون lookup مشتركة، والاختيارات الفعلية في جداول ربط.
- التفاصيل المشتركة ترتبط بـ `subject_type/subject_id`، والتفاصيل الخاصة بالإفطار ترتبط بـ `ramadan_iftar_id`.
- لا تحفظ قوائم الوجبات أو الفرق أو الفئات في JSON؛ JSON يقتصر على snapshots أو metadata غير الأساسية.
- الإجماليات في `ramadan_iftars` قيم مشتقة/متزامنة لتحسين التقارير، ومصدر الحقيقة هو جداول التفاصيل.
- لا نضيف أعمدة `is_*` إلى الجداول التشغيلية؛ أعلام قابلية الاستخدام تخص جداول التعريف فقط.
- كل وحدة مشتركة تحصل على Service/Policy واحدة تطبق قواعد الأصل وصلاحياته وعزلة فرعه.

## 11. ترتيب الـ migrations المقترح

1. إنشاء `ramadan_iftars` وربطه مباشرة بـ `agenda_events`.
2. إنشاء/تعديل lookups: قابلية الاستخدام، شرائح المستفيدين، الحشد والرصد.
3. إنشاء `subject_target_groups` و`subject_execution_needs`.
4. إنشاء فرق التنفيذ وأعضائها ومتطلبات المتطوعين واللوازم المشتركة.
5. إنشاء جداول كشف الحضور والوجبات وعناصرها والهدايا والفقرات الخاصة بالإفطار.
6. إنشاء تقارير الرصد والمطابقة المشتركة وتعميم التقييم.
7. إضافة workflow الإفطار والصلاحيات والإشعارات وطلبات النسخ/التعديل/الحذف.
8. ترحيل بيانات الجداول الشهرية القديمة إلى الجداول المشتركة تدريجياً بعد اختبارات مقارنة، ثم إبقاء طبقة توافق إلى أن تعتمد الشاشات الجديدة.

## 12. نقاط يجب حسمها قبل التنفيذ

1. هل يسمح بأكثر من جمعية/مركز/مجتمع محلي في الإفطار الواحد؟ إن كان نعم ننقل المضيف إلى جدول `subject_stakeholders` متعدد.
2. هل الجهة الداعمة واحدة أم متعددة، وهل داعم الوجبة قد يختلف عن داعم الهدية؟
3. هل طريقة الحشد واحدة أم متعددة؟
4. ما الحدود الرسمية لطفل/يافع/شاب، وهل «سيدات» محور مستقل يمكن دمجه مع عمر آخر؟
5. هل كشف الحضور مطلوب لكل إفطار أم فقط عند حضور المجتمع المحلي؟
6. هل بيانات `actual_count` لكل فئة تدخل يدوياً أم تحسب حصراً من كشف الحضور؟
7. هل يمكن وجود أكثر من تقرير رصد وأكثر من مستخدم متابعة للإفطار الواحد؟
8. هل تقييم الوجبة درجة واحدة أم نموذج أسئلة متعدد؟
9. هل الموافقة التنفيذية إلزامية لكل إفطار أم مشروطة؟
10. هل يراد ترحيل الأنشطة الشهرية فوراً إلى الجداول المشتركة، أم تستخدم الجداول المشتركة للإفطارات أولاً ثم يرحل القديم في مرحلة لاحقة؟
