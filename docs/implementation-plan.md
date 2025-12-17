# خطة تنفيذ نظام OPS – زها (Laravel 11)

هذه الخطة تبني على وثيقة SRS المرفقة في الطلب، وتهدف إلى إطلاق نسخة MVP قابلة للتطوير لاحقاً. الخطة موزعة على دفعات (sprints) مع نتائج واضحة.

## 0. Bootstrap & الأساسيات
- تشغيل `scripts/bootstrap-laravel.sh` لإنشاء مشروع Laravel 11.
- إعداد قاعدة البيانات MySQL مع جداول المستخدمين/الأدوار/الفروع/المراكز (Spatie Permission + جداول التصنيفات الأساسية).
- تفعيل المصادقة عبر Laravel Breeze (Blade) + Sanctum للـ API.
- إعداد هيكلية الموديولات في مجلدات `Modules/*` أو داخل `app/Domain/*` حسب تفضيل الفريق.

## 1. المستخدمون والصلاحيات (RBAC)
- جداول: users, roles, user_roles, branches, centers.
- سياسات صلاحيات حسب Matrix الواردة في SRS (agenda.*, monthly.*, revenues.*, maintenance.*, transport.*, reports.*).
- واجهة إدارة المستخدمين والفروع والمراكز.
- سجل تدقيق عام (activity log) للأحداث المهمة.

## 2. الأجندة السنوية (Agenda)
- نماذج وجداول: agenda_events, agenda_event_targets, agenda_approvals.
- حالات: draft → submitted → relations_approved → executive_approved → published.
- واجهة إنشاء/إرسال/اعتماد مع Timeline القرارات.

## 3. الخطة الشهرية (Monthly Activities)
- نماذج وجداول: monthly_activities + supplies + team + attachments + approvals + attendance.
- حالات: draft → submitted → relations_officer → relations_manager → programs_officer → programs_manager → executed → closed.
- ربط اختياري مع الأجندة السنوية.
- رفع مرفقات وملاحظات تنفيذ بعد الإغلاق.

## 4. الإيرادات (دعم نقدي + حجوزات + زها تايم)
- جداول: donations_cash, bookings, zaha_time_bookings, payments.
- حالات الحجوزات: requested → confirmed → paid → completed/cancelled.
- تكامل مع صلاحيات FinanceOfficer للخصومات والتحصيل.
- تقارير شهرية حسب الفرع والجهة الداعمة.

## 5. الصيانة
- جداول: maintenance_requests, maintenance_work_details, maintenance_approvals, maintenance_attachments.
- حالات: logged → assigned/in_progress → completed → branch_approved → closed.
- دعم مرفقات قبل/بعد وتسجيل root cause.

## 6. النقل والحركة
- جداول: vehicles, drivers, trips, trip_segments, trip_rounds.
- حالات: draft → scheduled → driver_view → closed.
- شاشة جدول للسائق (read-only) + طباعة جدول الرحلات.

## 7. التقارير والتصدير
- تقارير Excel/PDF للفعاليات، الاعتمادات، الإيرادات، الصيانة، النقل.
- لوحات متابعة (Dashboards) مبسطة عبر Blade + Chart.js أو Vue 3 (مرحلة لاحقة).

## 8. البنية التقنية والإصدارات
- Laravel 11، PHP 8.2+، MySQL 8، Redis (اختياري للصفوف)، Queue للتقارير الثقيلة.
- Spatie Permission للـ RBAC.
- Laravel Excel للتصدير.
- Storage محلي مبدئياً مع استعداد للانتقال إلى S3/Wasabi.

## 9. خطوات الإطلاق الأولى (MVP)
1. Bootstrap + RBAC + Auth + فروع/مراكز.
2. Agenda + Monthly Activities مع مسارات الاعتماد والمرفقات.
3. Revenues (donations + bookings + zaha time) مع التقارير الأساسية.
4. Maintenance + Transport.
5. تقارير ولوحات متابعة.

## 10. معايير القبول
- تتبع كامل لسجل الاعتماد والزمن لكل خطوة.
- منع التعديل على السجل المعتمد إلا عبر Return/Request Change.
- تصدير Excel مطابق للحقول الأساسية الحالية.
- مرفقات مؤرشفة لكل كيان.
- تطبيق صلاحيات الدور/الفرع/المركز على كل إجراء.

