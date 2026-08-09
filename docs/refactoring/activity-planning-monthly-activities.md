# خطة إعادة تنظيم ActivityPlanning / MonthlyActivities

> آخر تحديث: 2026-07-29 — المرحلة 1 قيد التنفيذ. هذه الوثيقة تصف العقود الحالية قبل نقل منطق الأعمال، ولا تمنح الإذن بتغييرها.

## القيود والثوابت

- Laravel `^8.75` وPSR-4 الحالي `App\\ => app/`؛ لذلك تعمل أصناف `App\\Modules` دون تعديل Composer.
- لا تغيير لأسماء routes أو URIs أو HTTP methods أو middleware أو view/JSON payloads.
- لا Repository عام، ولا migrations، ولا إعادة تنظيم لـ `AgendaEventsController` قبل إكمال هذا الجزء.
- لا يحتاج الموديول إلى Service Provider في المرحلة الحالية: Query classes concrete وتُحل تلقائيًا عبر container. ينشأ Provider فقط عند وجود bindings أو boot logic حقيقية.
- بيئة الحاوية تستخدم PHP `8.5.7-dev`، بينما `composer.lock` يقفل `nette/schema v1.2.5` و`nette/utils v3.2.10` اللذين لا يقبلان PHP 8.5. فشل `composer install` قبل التثبيت، ثم فشلت محاولة تشخيصية مع تجاهل شرط PHP بسبب HTTP 403 من GitHub؛ لذلك بقي `vendor/autoload.php` غير موجود. نجاح lint والتحقق الساكن ليس بديلًا عن الاختبارات.

## حدود النظام والتبعيات

`MonthlyActivitiesController` ينسق `MonthlyActivity` وعلاقاته مع `AgendaEvent`, `Branch`, workflow instances/logs، طلبات التعديل والحذف، supplies/team/sponsors/partners/volunteer needs، evaluation/follow-up، الملفات، وإشعارات workflow. الخدمات الحالية التي يجب إعادة استخدامها بدل نسخها: `DynamicWorkflowService`, `MonthlyActivityWorkflowService`, `MonthlyActivityLifecycleService`, `WorkflowNotificationService`, `NotificationService`, `MonthlyWorkflowPresenter`, `PlanChangeRequestWorkflowService`, و`ConflictDetectionService`.

لا توجد Jobs/Listeners تطبيقية لهذا المسار؛ الآثار الجانبية الحالية تنفذ داخل HTTP request. التكامل مع الأجندة موجود في `syncFromAgenda` وفي اختيار وربط `AgendaEvent` أثناء create/update.

## خريطة Public Methods والعقود الحالية

جميع المسارات أدناه داخل مجموعة `auth`. `branch.isolation` مذكور حيث أضيف صراحةً على route.

| Method | Route / Middleware / Permissions | المسؤولية والمدخلات | Models / Side effects | View أو Response | Dependencies | Risk / Target / Tests |
|---|---|---|---|---|---|---|
| `index` | `GET /dashboard/relations/monthly-activities`, `role.relations.activities.index`; `role_or_permission:relations_manager\|relations_officer\|volunteer_coordinator\|programs_manager\|super_admin\|monthly_activities.view`, `branch.isolation` | قائمة، scope، status، branch، summary، year/month، deleted، per_page | قراءة MonthlyActivity/Branch/AgendaEvent/workflow؛ بلا كتابة | `pages.monthly_activities.activities.index` مع نفس مفاتيح view وPaginator | Queries الجديدة + presenters داخل controller مؤقتًا | متوسط؛ `Queries/MonthlyActivitiesIndexQuery` ثم Browse Controller؛ tests auth/403/branch/status/month/pagination |
| `calendar` | `GET .../calendar`, `role.relations.activities.calendar`; middleware مطابق index | JSON للتقويم بنفس filters، وحساب روابط/قدرات كل item | قراءة MonthlyActivity/Branch/AgendaEvent | JSON `{year,month,items}`، 200 | Calendar Query؛ permission helpers مؤقتًا | متوسط؛ Query ثم Browse Controller/Presenter؛ JSON contract tests |
| `trash` | `GET .../trash`, `role.relations.activities.trash`; roles موسعة أو permission view، `branch.isolation` | قائمة soft-deleted حسب branch/year/month | قراءة activities وWorkflowActionLog | `activities.trash` | visibility + trash query مستقبلًا | متوسط؛ `MonthlyActivitiesTrashQuery` وTrash Controller؛ tests branch/deleted actor/pagination |
| `restore` | `PATCH .../trash/{monthlyActivity}/restore`, `role.relations.activities.trash.restore`; `role:super_admin\|admin`, `branch.isolation` | يستقبل id وquery filters للعودة | restore؛ يحول cancelled status إلى draft ويمسح cancelled execution status | redirect إلى trash + flash عربي | Eloquent + visibility guard | مرتفع؛ `RestoreMonthlyActivityAction`/Trash Controller؛ auth/404/branch/state/flash tests |
| `create` | `GET .../create`, `role.relations.activities.create`; create role/permission، `branch.isolation` | تجهيز form/prefill/lookups/agenda visibility | قراءة lookups فقط؛ flash old input | `activities.create` | agenda visibility/status helpers | متوسط؛ FormData Query + Write Controller؛ scoped lookups tests |
| `syncFromAgenda` | `POST .../sync-from-agenda`, `role.relations.activities.sync_from_agenda`; relations roles، `branch.isolation` | تحقق من agenda event والفرع وإنشاء/تحديث نشاط مرتبط | writes Activity وعلاقات وربط agenda | redirect/validation flashes حسب التنفيذ الحالي | Agenda models/workflow | مرتفع جدًا؛ `Synchronization` action لاحقًا دون لمس Agenda controller؛ transaction/idempotency/ownership tests |
| `store` | `POST .../monthly-activities`, `role.relations.activities.store`; create role/permission، `branch.isolation` | validation وتطبيع التخطيط والتنفيذ وإنشاء النشاط وربط العلاقات/الملف وربما submit | writes activity, supplies/team/sponsors/partners/target groups/volunteer/correspondence/file/workflow/logs/notifications | redirects الحالية وflash/validation كما هي | Conflict, workflow, lifecycle, notifications, DB, Storage | مرتفع جدًا؛ Form Request ثم `CreateMonthlyActivityAction`; exhaustive validation/transaction/file/notification tests |
| `edit` | `GET .../{monthlyActivity}/edit`, `role.relations.activities.edit`; role list | visibility، mode planning/post، locks، lookups، workflow data | قراءة علاقات كثيرة وflash prefill | `activities.edit` | presenters/change requests/status helpers | مرتفع؛ FormData Query + Write Controller؛ role/branch/locked/unified/modes tests |
| `showDeleted` | `GET .../deleted/{monthlyActivity}`, `role.relations.activities.deleted.show`; role list | تحميل soft-deleted ثم نفس عرض التفاصيل | قراءة فقط | نفس مسار show عبر helper | presenters/change requests | متوسط؛ Read Controller/Show Query؛ 404/visibility/deleted tests |
| `show` | `GET .../{monthlyActivity}`, `role.relations.activities.show`; role list including programs_manager | visibility وتحضير workflow/change requests والعلاقات | قراءة فقط | `activities.show` | MonthlyWorkflowPresenter/PlanChangeRequestWorkflowService | متوسط؛ Read Controller/Show Query؛ exact view data/roles tests |
| `update` | `PUT .../{monthlyActivity}`, `role.relations.activities.update`; role list | validation وتطبيع، locks، partial role edits، versioning، files، relations، post-execution | writes multiple tables/files/workflow logs/notifications؛ قد ينشئ version ويلغي السابقة | redirects/flashes/validation الحالية | DB/Storage وكل workflow services | مرتفع جدًا؛ Form Request ثم `UpdateMonthlyActivityAction` مع actions فرعية للـworkflows؛ regression/version/dirty/file tests |
| `destroy` | `DELETE .../{monthlyActivity}`, `role.relations.activities.destroy`; relations/supervisor/super_admin | حذف مباشر أو بدء delete request حسب trail/status | soft delete أو change request، workflow log، notifications | redirect + flash | PlanChangeRequestWorkflowService | مرتفع؛ `DeleteMonthlyActivityAction`; allowed state/approval trail/branch/notification tests |
| `submit` | `PATCH .../{monthlyActivity}/submit`, `role.relations.activities.submit`; relations/supervisor/super_admin | guard ثم بدء/تقديم workflow وتغيير lifecycle/status | writes workflow instance/log/activity، sends notifications | redirect + flash/validation | Dynamic workflow/lifecycle/notifications | مرتفع؛ `SubmitMonthlyActivityAction`; duplicate/superseded/transition/recipient tests |
| `close` | `PATCH .../{monthlyActivity}/close`, `role.relations.activities.close`; relations/supervisor/super_admin | مراجعة واعتماد post-execution، إغلاق lifecycle وربما تحويل للتقييم | writes evaluation/post payload/status/logs، notifications | redirect index + translated flash | lifecycle/notifications | مرتفع جدًا؛ actions منفصلة review/close؛ role/state/score/notification tests |
| `returnedFeedback` | `GET .../returned-feedback`, `role.relations.activities.returned_feedback`; route roles + branch isolation، ويتحقق controller ثانية | يجمع approval/delete/edit/post-execution/execution-needs المرتجعة؛ filters activity_id/type | read خمس مجموعات وحد 50 لكل مجموعة | `activities.returned-feedback` مع items/counts/type/activityId | multiple models/history mapping | متوسط/مرتفع؛ ReturnedFeedback Query + Reports Controller؛ scope/count/order/filter tests |
| `postExecutionFeedback` | `GET .../post-execution-feedback`, `role.relations.activities.post_execution_feedback`; volunteer/super_admin + branch isolation والتحقق داخل controller | قائمة clarification/rejected post-execution | read paginated activities | `activities.post-execution-feedback` | branch approval scope | متوسط؛ Feedback Query + PostExecution Controller؛ role/branch/pagination tests |
| `changeRequestReports` | `GET /dashboard/admin/monthly-activities/change-requests/reports`, `role.super_admin.monthly_activities.change_requests.reports`; `role:super_admin\|admin` | تقارير delete/edit requests وفلاترها | read report collections | report view الحالية | Plan change request models | متوسط؛ Reports Query/Controller؛ admin/filter/count tests |

## مجموعات المسؤوليات وترتيب النقل

1. **Browse/read queries:** `index`, `calendar`, ثم `trash`, `show`, `showDeleted`.
2. **Form data:** `create`, `edit` lookups فقط.
3. **Synchronization:** `syncFromAgenda` يبقى مؤقتًا، ثم واجهة في `Synchronization` بعد تثبيت إنشاء النشاط.
4. **Write workflows:** `store`, `update`.
5. **Transitions:** `submit`, `destroy`, `restore`, `close` كـActions مستقلة.
6. **Post execution/evaluation:** الجزء الخاص بها من update/close + `postExecutionFeedback`.
7. **Reports:** `returnedFeedback`, `changeRequestReports`.
8. **Controller split:** نقل routes مجموعة بعد أخرى إلى Controllers متخصصة تحت `Modules/.../Http/Controllers`; لا ينقل الملف القديم كاملًا دفعة واحدة.

## Checklist مرحلية

### المرحلة 0 — Baseline

- [x] قراءة الملف كاملًا وحصر public methods والroutes.
- [x] توثيق العقود والتبعيات والمخاطر في هذه الوثيقة.
- [x] التحقق من PSR-4 وLaravel/PHP المطلوبين.
- [ ] تثبيت `vendor` من lock file على PHP مدعوم (يوصي المشروع بـ PHP `^7.3|^8.0` والحزم المقفلة تتطلب عمليًا <= 8.3) دون تحديث dependencies.
- [ ] تشغيل route list وحفظ snapshot method/URI/name/action/middleware.
- [ ] تشغيل كامل الاختبارات وتسجيل المشاكل السابقة.

### المرحلة 1 — Queries الآمنة

- [x] إضافة `MonthlyActivityListFilters` مع أسماء ومديات parameters الحالية.
- [x] إضافة `MonthlyActivityVisibilityQuery` لنطاق branch/draft/volunteer.
- [x] إضافة `MonthlyActivitiesIndexQuery` دون تغيير pagination أو eager loads.
- [x] إضافة `MonthlyActivitiesCalendarQuery` مع eager loading الحالي لمنع N+1.
- [x] جعل `index` و`calendar` يستدعيان Query classes.
- [x] إضافة characterization tests للمصادقة، all-branches authorization، pagination، مع الإبقاء على tests الحالية للbranch/status/JSON.
- [x] إضافة Route characterization يثبت name/URI/method/middleware/action/no parameters.
- [x] نقل `index` إلى `MonthlyActivitiesBrowseController::index` داخل الموديول.
- [x] نقل `calendar` إلى `MonthlyActivityCalendarController::index` داخل الموديول.
- [x] استخراج تحويل JSON الفعلي إلى `MonthlyActivityCalendarEventPresenter`.
- [x] تحديث route actions مع إبقاء names وURIs وHTTP methods وmiddleware حرفيًا.
- [x] حذف `index` و`calendar` وhelpers الخاصة بهما من Controller القديم بعد البحث عن الاستدعاءات المباشرة.
- [x] توثيق عائق الاختبارات الحقيقي وإكمال التحقق البديل: syntax لجميع الملفات المتغيرة، PSR-4 paths، route action search، مقارنة route definitions قبل/بعد، JSON/View contract review، و`git diff --check`.
- [ ] إعادة تشغيل Route/Feature tests على PHP مدعوم فور توفره؛ يبقى هذا دينًا تشغيليًا مسجلًا ولا توصف الاختبارات بأنها ناجحة.
- [ ] لا يبدأ استخراج trash ضمن هذا التغيير؛ تكون المجموعة التالية فقط بعد مراجعة نتائج Browse على بيئة تشغيل مدعومة.

### المرحلة 2 — Form Requests

- [ ] جرد قواعد `store/update/close/sync` والرسائل والتحضير السابق للvalidation.
- [ ] Characterization tests لكل rule مشروطة.
- [ ] استخراج Requests حرفيًا مع `authorize()` مطابق للسلوك الحالي.

### المرحلة 3 — Create

- [ ] اختبارات DB/file/workflow/notifications/redirects.
- [ ] `CreateMonthlyActivityAction` وtransaction boundary واضحة.
- [ ] نقل route إلى Write Controller متخصص بعد التكافؤ.

### المرحلة 4 — Update

- [ ] تثبيت matrix للحالات والأدوار والحقول المقفلة.
- [ ] اختبارات versioning وdirty attributes والملفات.
- [ ] `UpdateMonthlyActivityAction` دون Service ضخمة جامعة.

### المرحلة 5 — Transitions

- [ ] Actions منفصلة لـ submit/delete/restore/close الفعلية.
- [ ] اختبارات منع التكرار وrollback والإشعارات.

### المرحلة 6 — Attachments/security

- [ ] تدقيق routes الخاصة بـ `MonthlyActivityAttachmentsController` وIDOR.
- [ ] اختبارات view/download/store/delete عبر الفروع.
- [ ] اتساق DB/storage وتعويض orphan files؛ دون تغيير disk.

### المرحلة 7 — Post execution/evaluation

- [ ] استخراج workflows منفصلة للتحقق والمراجعة والإغلاق والتحويل للتقييم.
- [ ] تثبيت calculation/history/notification contracts.

### المرحلة 8 — Reports/integrations

- [ ] Returned feedback/report queries مع قياس أحجام `get()`.
- [ ] واجهة synchronization واضحة مع الأجندة، دون تعديل Agenda controller.

### المرحلة 9 — Controller cleanup

- [ ] جميع routes تشير إلى Controllers متخصصة في الموديول.
- [ ] حذف الملف القديم فقط بعد خلوه وإثبات route snapshot والاختبارات.
- [ ] إزالة imports/helpers غير المستخدمة.
- [ ] تقرير نهائي قبل بدء تحليل Agenda controller.

## تحقق المرحلة 1 المطلوب عند توفر البيئة

```bash
php artisan test --filter=MonthlyActivityBranchVisibilityTest
php artisan test --filter=ProductionReadinessMonthlyActivitiesTest
php artisan test
php artisan route:list
```

يجب مقارنة JSON التقويم، مفاتيح view، pagination، status grouping، branch scope، `all_branches` 403، وإخفاء drafts حرفيًا قبل/بعد.
