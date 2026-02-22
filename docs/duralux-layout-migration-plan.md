# خطة ترحيل واجهات النظام إلى قالب Duralux

## الهدف
توحيد جميع صفحات النظام على قالب **Duralux Admin 1.0.0** مع دعم:
- Light / Dark mode.
- التبديل بين العربية والإنجليزية (RTL/LTR).
- Sidebar ديناميكي حسب الصلاحيات.
- Header / Footer موحّدين.
- الحفاظ على Routes وBlade الحالية بدون كسر الوظائف.

## الأساس المعتمد من التوثيق
بناءً على توثيق القالب (`docs/html/layouts.html` و `docs/html/configuration.html`):
- الهيكل الأساسي: `nxl-sidebar` + `nxl-header` + `nxl-container` + `main-content` + `footer`.
- التخصيص البصري يجب أن يكون عبر ملف ثيم مخصص وعدم تعديل ملفات framework الأساسية مباشرة.
- الـ JS الخاص بالقالب (vendors/plugin/theme init) يجب تحميله بترتيب صحيح.

## نطاق الترحيل
1. ملفات Layout الأساسية:
   - `resources/views/layouts/app.blade.php`
   - `resources/views/layouts/app/header.blade.php`
   - `resources/views/layouts/app/sidebar.blade.php`
   - `resources/views/layouts/app/footer.blade.php`
2. كل صفحات `resources/views/pages/**` و `resources/views/roles/**` التي تعتمد على layout.
3. ملفات اللغات `resources/lang/ar` و `resources/lang/en` لإضافة مفاتيح UI الجديدة.
4. أي دعم خلفي بسيط (Session / Middleware / Controller helpers) لحفظ تفضيل الثيم واللغة.

---

## خطة التنفيذ (Phases)

## المرحلة 1: التأسيس البنيوي (Foundation)
**المخرجات:** Layout جديد يعمل على صفحة/صفحتين تجريبيًا.

- [ ] إنشاء هيكل Blade متوافق مع Duralux داخل `layouts/app.blade.php`:
  - `html[lang][dir]` ديناميكي.
  - body classes ديناميكية للـ light/dark.
  - stack للأصول `@stack('styles')` و `@stack('scripts')`.
- [ ] نقل Header / Sidebar / Footer إلى markup قريب من القالب الأصلي.
- [ ] ربط الأصول من `public/assets` (CSS/JS/fonts/icons) مع ترتيب التحميل الصحيح.
- [ ] الإبقاء على include الخاص بـ sidebar role-based الحالي مع إعادة تنسيقه.

## المرحلة 2: إدارة الثيم (Dark/Light)
**المخرجات:** زر تبديل ثيم + حفظ التفضيل.

- [ ] إضافة Theme switcher في الـ header.
- [ ] حفظ الثيم في session (ثم cookie/localStorage كنسخة client fallback).
- [ ] تطبيق الثيم على `<html>` أو `<body>` بحسب متطلبات القالب.
- [ ] ضبط أول تحميل للصفحة ليقرأ آخر تفضيل للمستخدم.

## المرحلة 3: إدارة اللغة وRTL/LTR
**المخرجات:** تبديل لغة فعّال على مستوى النظام.

- [ ] إنشاء/تحديث route لتبديل اللغة (`ar` / `en`).
- [ ] تحديث الـ header بقائمة language switcher.
- [ ] تطبيق `dir="rtl"` عند العربية و `dir="ltr"` عند الإنجليزية.
- [ ] تحميل CSS إضافي للـ RTL (إذا لزم) مع معالجة أي كسر بصري في القالب.

## المرحلة 4: ترحيل الصفحات تدريجيًا
**المخرجات:** جميع الصفحات تعمل على القالب الجديد.

- [ ] ترتيب الصفحات حسب الأولوية:
  1) Dashboard.
  2) صفحات العمليات اليومية (agenda/monthly activities/finance).
  3) الصفحات الأقل استخدامًا.
- [ ] استبدال wrappers/classes القديمة لكل صفحة بما يتماشى مع `main-content`.
- [ ] توحيد البطاقات والجداول والأزرار على أنماط Duralux.
- [ ] معالجة أي تعارض CSS قديم عبر ملف override واحد فقط.

## المرحلة 5: Sidebar والصلاحيات
**المخرجات:** Sidebar موحد، واضح، ومتوافق مع الأدوار.

- [ ] بناء عناصر Sidebar بتركيب Duralux (main item + submenu + active states).
- [ ] ربط العناصر بالـ route names الحالية.
- [ ] الحفاظ على إظهار القوائم حسب الدور الحالي.
- [ ] دعم collapse/expand + mobile toggle + active trail.

## المرحلة 6: الصقل والجودة (QA)
**المخرجات:** واجهة مستقرة وقابلة للتسليم.

- [ ] اختبار عرض الصفحات على desktop/tablet/mobile.
- [ ] اختبار dark/light على عدة صفحات.
- [ ] اختبار التبديل AR/EN وRTL/LTR.
- [ ] اختبار الصلاحيات وإظهار القوائم.
- [ ] فحص الأداء (الأصول المحمّلة، التكرار، حجم الملفات).

---

## تفاصيل التنفيذ المقترحة (تقنية)

### 1) إدارة حالة الواجهة (UI Preferences)
- مفاتيح موحّدة:
  - `ui.theme` = `light|dark`
  - `ui.locale` = `ar|en`
- الأولوية عند التحميل:
  1. Session (إن وجد).
  2. إعداد النظام الافتراضي.

### 2) ملفات مخصّصة لتقليل المخاطر
- `public/assets/css/zaha-duralux-overrides.css`
- `public/assets/js/zaha-duralux-init.js`

> الهدف: عدم تعديل ملفات القالب الأساسية مباشرة، وتسهيل تحديث القالب لاحقًا.

### 3) استراتيجية الترحيل بدون توقف
- Feature-flag بسيط في config (مثال: `ui.new_layout_enabled`).
- تفعيل القالب الجديد تدريجيًا على routes محددة أولًا.
- عند الاستقرار: تعميمه على كل النظام.

---

## خطة البداية الفعلية (سنبدأ بها الآن)
1. تحويل `layouts/app.blade.php` إلى skeleton Duralux متوافق مع locale/dir.
2. تحديث `header` لإضافة theme toggle + language switcher.
3. إعادة تركيب `sidebar` بنفس منطق الصلاحيات الحالي ولكن markup جديد.
4. تشغيل اختبار تصفح لصفحة dashboard كأول نقطة تحقق.

## معايير القبول (Definition of Done)
- كل الصفحات تفتح تحت layout واحد بدون كسر layout.
- dark/light يعمل ويحفظ التفضيل.
- AR/EN تعمل مع اتجاه صحيح.
- sidebar يعكس الصلاحيات ويدعم mobile.
- لا يوجد تعديل مباشر على core CSS للقالب.
