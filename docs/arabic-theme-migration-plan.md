# خطة انتقال مشروع Zaha إلى الثيم العربي/الإنجليزي الجديد

## 1) دراسة سريعة للكوميت الأخير (Arabic Theame)

### ما يقدمه الثيم الجديد فعليًا
- يدعم **RTL/LTR** بشكل ديناميكي عبر تبديل `lang` و`dir` وتبديل ملف Bootstrap (نسخة RTL مقابل LTR).
- يدعم **Dark/Light mode** عبر CSS Variables في `Theme.css`.
- يحتوي صفحات مرجعية عملية للمكوّنات المطلوبة: Dashboard, Forms, Cards, Notifications, Calendar, DateTime, Sidebar, Toast, Pagination.
- يحتوي نظام ترجمة مبني على ملفات JSON (`locales/ar/common.json`, `locales/en/common.json`) مع fallback داخل JavaScript.
- يدمج مكونات UI تفاعلية مهمة: FullCalendar, Flatpickr, Toastr, SweetAlert2.

### أهم الملفات المرجعية في الثيم
- أسس الألوان والثيمين: `Arabic Theame/css/Theme.css`
- سلوك layout + sidebar + responsive + RTL/LTR: `Arabic Theame/css/Style.css`
- منطق التبديل (لغة/ثيم/سايدبار) + i18n + مكونات التنبيه/التقويم: `Arabic Theame/js/app.js`
- قاموس الترجمة: `Arabic Theame/locales/ar/common.json` و`Arabic Theame/locales/en/common.json`

---

## 2) الفجوة الحالية في المشروع مقارنة بالثيم الجديد

> الهدف هنا تحديد سبب المشاكل المتوقعة (ترجمة، RTL/LTR، dark/light) قبل البدء بالتنفيذ.

- في المشروع الحالي يوجد بداية تحديد للغة والاتجاه في layout الأساسي فقط (`resources/views/layouts new/app.blade.php`) بدون دمج كامل لنظام ثيم/JS/Assets.
- ملفات CSS/JS الأساسية للتطبيق الحالي شبه فارغة أو غير مفعلة (`resources/css/app.css`, `resources/js/app.js`) بالنسبة لمنطق الثيم الجديد.
- المشروع يحتوي ترجمة Laravel واسعة (`resources/lang/ar`, `resources/lang/en`) بينما الثيم الجديد يستخدم JSON مستقل داخل مسار آخر؛ يلزم توحيد الاستراتيجية.
- المكوّنات المطلوبة (Tabs/Alerts/Notifications/Calendars) موجودة كنماذج static في الثيم الجديد، لكنها ليست بعد جزءًا من صفحات Blade التشغيلية.

---

## 3) To-Do List تنفيذية (Actionable)

## A) تأسيس معماري (Design System + Layout)
- [ ] إنشاء **Base Layout موحد** (Blade) مبني على الثيم الجديد (Topbar + Sidebar + Content shell).
- [ ] استخراج CSS الجديد إلى ملفات أصول المشروع الرسمية (يفضل عبر Vite) بدل الاعتماد على ملفات static منفصلة.
- [ ] تعريف متغيرات الألوان رسميًا (tokens) مع توثيقها وربطها بــ dark/light.
- [ ] إضافة مبدأ CSS logical properties لكل الهوامش/المواضع الحساسة للاتجاه (`margin-inline`, `inset-inline`, `padding-inline`).

## B) إدارة اللغة والاتجاه RTL/LTR
- [ ] اعتماد **مصدر وحيد للحقيقة** للغة المستخدم (session/profile/localStorage) + middleware يضبط `app()->getLocale()`.
- [ ] إضافة Locale Switcher موحد يغيّر اللغة ويحدّث `dir` و`lang` بدون كسر الواجهة.
- [ ] فصل مكونات تتأثر بالاتجاه (Sidebar align, dropdown align, icons spacing) في utilities واضحة.
- [ ] إعداد اختبارات UI للانتقال AR↔EN على جميع المقاسات (Desktop/Tablet/Mobile).

## C) إدارة Dark/Light
- [ ] اعتماد `data-theme` على `<html>` مع persistence للمستخدم.
- [ ] توحيد كل الألوان في CSS Variables (عدم وجود ألوان hard-coded في الصفحات).
- [ ] مراجعة contrast وتوافق WCAG للعناصر الحرجة (inputs, table text, badges, alerts).
- [ ] اختبار تزامن الثيم مع الرسوم البيانية والتقويم والتنبيهات.

## D) الترجمة ومنع مشاكل النصوص
- [ ] وضع سياسة ترجمة واحدة: إما Laravel lang أو JSON frontend مع جسر واضح بينهما.
- [ ] بناء **Translation Key Registry** لمنع duplication/missing keys.
- [ ] تمرير جميع النصوص الثابتة في Blade/JS عبر مفاتيح ترجمة فقط.
- [ ] إضافة check آلي في CI لاكتشاف المفاتيح الناقصة بين العربية والإنجليزية.

## E) Tabs / Alerts / Notifications / Calendars (المطلوب صراحة)
- [ ] إنشاء مكوّن Tabs موحد (Blade component) يدعم RTL spacing وحالات Active/Hover في dark/light.
- [ ] إنشاء Alert component (success/warning/error/info) بألوان متسقة بين الثيمين.
- [ ] توحيد Notification layer:
  - [ ] Toasts (نجاح/تحذير/خطأ)
  - [ ] Modal alerts (info/confirm)
  - [ ] مركز إشعارات أعلى الصفحة مع عدّاد متوافق مع اللغتين.
- [ ] دمج Calendar module (FullCalendar) عبر wrapper موحد:
  - [ ] يتبدل locale + direction مباشرة مع اللغة.
  - [ ] صيغ التاريخ/الوقت تتوافق مع لغة الواجهة.
  - [ ] دعم fallback UX عند فشل تحميل المكتبة.

## F) تحويل الصفحات الحالية تدريجيًا
- [ ] جرد جميع صفحات `resources/views/pages/**` وتصنيفها حسب الأولوية (حرجة/متوسطة/منخفضة).
- [ ] تحويل الصفحات الحرجة أولًا (Dashboard + العمليات اليومية + النماذج).
- [ ] تطبيق migration template موحد لكل صفحة (Header actions, cards, forms, tables, filters).
- [ ] التحقق من consistency لكل صفحة في 4 حالات: (AR-light, AR-dark, EN-light, EN-dark).

## G) الجودة والاختبار قبل الإطلاق
- [ ] إعداد Visual Regression baseline لأهم الصفحات (4 حالات لغة/ثيم).
- [ ] E2E smoke tests: login, navigation, create/edit forms, notifications, calendar actions.
- [ ] اختبار الأداء بعد إضافة المكتبات الجديدة (تقليل payload، lazy load عند الحاجة).
- [ ] UAT ثنائي اللغة مع checklist واضحة للـ RTL/LTR + الترجمة + الثيم.

---

## 4) خطة تنفيذ مرحلية (Timeline مقترح)

### المرحلة 0 — تحضير (1–2 يوم)
- اعتماد قرار معماري للترجمة (Laravel vs JSON hybrid).
- تثبيت بنية الأصول (Vite) وتجهيز base layout.
- تعريف Definition of Done لصفحة "جاهزة".

### المرحلة 1 — Foundation (3–4 أيام)
- ترحيل Theme.css + Style.css كنظام تصميم قابل لإعادة الاستخدام.
- بناء Locale/Theme switchers داخل layout الرئيسي.
- دمج Sidebar/Topbar responsive وإغلاق فجوات RTL/LTR الأساسية.

### المرحلة 2 — Shared Components (3–5 أيام)
- بناء Tabs/Alerts/Notification/Calendar wrappers كمكوّنات موحدة.
- توحيد الترجمة داخل هذه المكوّنات.
- إعداد tests وحدات/واجهة للمكوّنات.

### المرحلة 3 — Page Migration (1–2 أسبوع بحسب عدد الصفحات)
- ترحيل الصفحات على دفعات (Batch-by-batch).
- بعد كل دفعة: QA سريع للحالات الأربع (AR/EN × Dark/Light).
- إصلاح regressions أولًا بأول.

### المرحلة 4 — Stabilization & Launch (2–3 أيام)
- Visual regression + E2E + UAT.
- إقفال مفاتيح الترجمة الناقصة.
- إطلاق تجريبي ثم إطلاق تدريجي.

---

## 5) Definition of Done (لكل صفحة قبل اعتبارها "مكتملة")

- [ ] لا يوجد أي نص hard-coded خارج نظام الترجمة.
- [ ] تعمل الصفحة بنفس الجودة في العربية والإنجليزية.
- [ ] لا توجد مشاكل RTL/LTR (محاذاة، أيقونات، padding، dropdown placement).
- [ ] لا توجد مشاكل Dark/Light (contrast, borders, disabled states).
- [ ] Tabs/Alerts/Notifications/Calendars تعمل دون كسر في تبديل اللغة/الثيم.
- [ ] الصفحة ناجحة في الاختبارات الآلية + مراجعة QA اليدوية.

---

## 6) مخاطر متوقعة + تخفيفها

- **ازدواجية مصادر الترجمة** → الحل: اعتماد registry موحد + CI check للمفاتيح.
- **انكسار بعض الصفحات القديمة مع RTL** → الحل: migration تدريجي + utility classes + visual tests.
- **تباين الألوان في الدارك** → الحل: ضبط tokens مركزيًا وعدم استخدام hard-coded colors.
- **تعقيد calendar/time format بين اللغات** → الحل: wrapper واحد مسؤول عن locale/direction/format.

---

## 7) ترتيب أولويات عملي مقترح

1. Layout الأساسي + theme/locale switchers.
2. Components المشتركة (Tabs, Alerts, Notifications, Calendar).
3. Dashboard والصفحات اليومية عالية الاستخدام.
4. باقي الصفحات الإدارية الأقل استخدامًا.
5. الإقفال النهائي عبر QA/E2E/Visual regression.

> بهذه الطريقة نضمن الانتقال "بدفعة واضحة" لكن **على مراحل مضبوطة** تمنع مشاكل الترجمة، وتضمن تطابق dark/light، وتمنع أخطاء التحويل بين RTL/LTR في جميع الصفحات.
