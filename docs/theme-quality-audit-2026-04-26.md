# Theme QA Audit — 2026-04-26

## نطاق التدقيق
- الصفحات المهاجرة إلى `layouts.new-theme-dashboard`.
- التحقق من:
  1) الترجمة AR/EN (مفاتيح واضحة بدون نصوص ثابتة إنجليزية حرجة)
  2) ربط الثيم (Light/Dark)
  3) ربط الاتجاه RTL/LTR
  4) توحيد الـ Sidebar/Navbar

## نتيجة التوحيد
- تم توحيد السايدبار داخل ملف layout واحد:
  - `resources/views/layouts/new-theme-dashboard.blade.php`
- تم إلغاء تعريفات `@section('theme_sidebar_links')` من الصفحات المهاجرة لضمان عدم تكرار السايدبار لكل صفحة.

## نتيجة التحقق البرمجي
- لا يوجد امتدادات `layouts.app` ضمن نطاق الصفحات المهاجرة المستهدفة (agenda/monthly/access/enterprise annual + dashboard).
- مفاتيح ترجمة عامة مضافة/مستخدمة: `save`, `create`, `alerts`, `pagination`, `communications_requests`, `permissions`.
- تم توحيد رابط "الخطط الشهرية للفروع الأخرى" إلى مفتاح ترجمة واحد.

## مصفوفة الصفحات المدققة
- Dashboard: ✅
- Access (roles/users/workflows/branches/approvals): ✅
- Agenda (events index/create/edit/show + approvals): ✅
- Monthly activities (activities index/create/edit/show + approvals + communications + lookups + workshops): ✅
- Enterprise annual overview: ✅

## ملاحظات
- صفحات Finance (bookings/zaha_time وبعض create/edit) ما زالت خارج موجة التدقيق الشامل الحالية لأنها غير مهاجرة بالكامل بعد، وتبقى في tracker كـ pending.
