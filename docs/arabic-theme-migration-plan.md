# الخطة المحسّنة لاعتماد الثيم الجديد بالكامل (Arabic Theme First)

## قرار معماري نهائي (حسب طلبك)

- **الاعتماد 100% على الثيم الجديد** كمرجع وحيد للواجهة (Styles + JS + Components + Pages).
- **عدم استخدام Vite** في خطة الدمج الحالية.
- **اللغة العربية هي الافتراضية** على مستوى النظام.
- المحافظة على الإنجليزية كلغة ثانية متكاملة بدون أي كسر RTL/LTR أو ترجمة.
- تفعيل جميع عناصر الثيم الجديدة تدريجيًا: **Cards / Tabs / Alerts / Toast / Notifications / Pagination / Calendar / DateTime / Sidebar / Auth pages**.

---

## 1) ما سيتم اعتماده من الثيم الجديد بشكل مباشر

### الأصول الأساسية (Core Assets)
- `Arabic Theame/css/Theme.css` (ألوان + dark/light tokens)
- `Arabic Theame/css/Style.css` (layout + sidebar + responsive + RTL/LTR)
- `Arabic Theame/js/app.js` (تبديل اللغة/الثيم + calendar/date + notifications)
- `Arabic Theame/locales/ar/common.json`
- `Arabic Theame/locales/en/common.json`
- صفحات المثال المرجعية: `index.html`, `card.html`, `pagination.html`, `notifications.html`, `calendar.html`, `datetime.html`, `Input.html`, `toast.htm`, `sidebar.html`, `Login.html`, `forgot-password.html`.

### سياسة التنفيذ
- أي عنصر UI موجود في النظام الحالي سيتم **مطابقته** مع نظيره في الثيم الجديد (وليس إعادة اختراعه).
- نستخدم نفس naming ونفس سلوك الـ classes قدر الإمكان لتقليل الانحرافات.

---

## 2) الفجوات الحالية التي يجب إغلاقها أولًا

- الـ layout الحالي في `resources/views/layouts new/app.blade.php` يضبط `lang/dir` فقط، لكنه لا يفعّل كامل عناصر الثيم الجديدة.
- `resources/css/app.css` و`resources/js/app.js` لا يحتويان حاليًا بنية تشغيل feature parity مع الثيم الجديد.
- يوجد نظام ترجمة Laravel كبير، بينما الثيم الجديد يعتمد JSON frontend؛ يجب وضع جسر واضح مع جعل العربية default.

---

## 3) خطة تنفيذ "صفحات صفحات" (عملي ومباشر)

## المرحلة A — Foundation (قبل أي صفحة أعمال)

### A1) تفعيل اللغة العربية افتراضيًا
- [ ] ضبط locale الافتراضي إلى `ar` على مستوى Laravel config/middleware.
- [ ] فرض `dir="rtl"` تلقائيًا عند أول زيارة للمستخدم.
- [ ] إبقاء زر التحويل للإنجليزية مع تذكر الاختيار.

### A2) اعتماد أصول الثيم بدون Vite
- [ ] نسخ ملفات CSS/JS/assets من `Arabic Theame` إلى مسارات public مع تنظيم واضح.
- [ ] ربط الملفات مباشرة عبر `<link>` و`<script>` في Blade (بدون pipeline Vite).
- [ ] توحيد bootstrap rtl/ltr switching كما هو في الثيم الجديد.

### A3) Base Layout مطابق للثيم
- [ ] إنشاء Layout Blade موحد يطابق `index.html` في الثيم (Topbar + Sidebar + Content).
- [ ] دمج نفس منطق sidebar collapse/mobile open + overlay.
- [ ] ربط locale/theme toggles بنفس سلوك `Arabic Theame/js/app.js`.

**معيار نجاح المرحلة A:**
- الصفحة الرئيسية تعمل فورًا بالعربية RTL + Dark/Light + Sidebar responsive + EN switch بدون مشاكل.

---

## المرحلة B — Shared Components (مرة واحدة تُستخدم بكل الصفحات)

- [ ] **Cards component** مطابق `card.html`.
- [ ] **Tabs component** بنمط الثيم.
- [ ] **Alerts component** (success/warning/error/info).
- [ ] **Toast + Notifications component** مطابق `notifications.html` و`toast.htm`.
- [ ] **Pagination component** مطابق `pagination.html`.
- [ ] **Calendar wrapper** مطابق `calendar.html`.
- [ ] **DateTime picker wrapper** مطابق `datetime.html`.

**معيار نجاح المرحلة B:**
- كل مكوّن قابل للاستخدام من أي صفحة، ويعمل في الحالات الأربع: `AR-light`, `AR-dark`, `EN-light`, `EN-dark`.

---

## المرحلة C — ترحيل الصفحات دفعات (Batches)

## Batch 1 (أولوية قصوى)
- [ ] Dashboard
- [ ] صفحات النماذج الأساسية (Inputs/Forms)
- [ ] صفحة الإشعارات (alerts/toasts)
- [ ] صفحة التقويم والتاريخ

## Batch 2
- [ ] صفحات الجداول والقوائم التي تحتاج pagination/cards بكثافة
- [ ] صفحات العمليات اليومية الأكثر استخدامًا

## Batch 3
- [ ] الصفحات الأقل استخدامًا
- [ ] صفحات الإعدادات التفصيلية

## Batch 4
- [ ] صفحات Auth (`Login`, `Forgot Password`) بتصميم الثيم الجديد
- [ ] أي صفحات legacy متبقية

**قاعدة التنفيذ في كل Batch:**
1. مطابقة الواجهة مع صفحة مرجعية من الثيم الجديد.
2. تمرير كل النصوص على الترجمة.
3. فحص RTL/LTR + dark/light.
4. اعتماد المكوّنات المشتركة بدل CSS/JS مكرر.

---

## 4) سياسة الترجمة بعد اعتماد العربية افتراضيًا

- العربية هي default في أول تحميل.
- الإنجليزية اختيارية عبر toggle.
- جميع النصوص الجديدة يجب أن تمتلك مفتاحًا بالعربية والإنجليزية قبل الدمج.
- منع hard-coded text في Blade/JS.
- فحص دوري للمفاتيح الناقصة بين `ar` و`en`.

---

## 5) قائمة فحص الجودة (لكل صفحة قبل اعتمادها)

- [ ] الشكل مطابق للثيم الجديد (spacing, typography, colors, controls).
- [ ] تعمل الصفحة طبيعيًا بالعربية RTL كحالة افتراضية.
- [ ] التبديل إلى الإنجليزية LTR لا يسبب كسر محاذاة/أيقونات.
- [ ] الدارك/لايت متطابقان وظيفيًا وبصريًا.
- [ ] العناصر المطلوبة موجودة عند الحاجة: cards/tabs/alerts/pagination/notifications/calendar.
- [ ] لا يوجد JavaScript errors في المتصفح.

---

## 6) التنفيذ الفوري (سنبدأ من هنا الآن)

## الخطوة 1 (الآن)
- تجهيز **Base Layout عربي افتراضي** + ربط CSS/JS من الثيم الجديد **بدون Vite**.

## الخطوة 2
- ترحيل Dashboard بالكامل ليطابق `Arabic Theame/index.html`.

## الخطوة 3
- ترحيل صفحات `notifications`, `calendar`, `datetime`, `cards`, `pagination` كحزمة Features موحدة.

## الخطوة 4
- البدء صفحة صفحة من صفحات المشروع الفعلية وربطها بالمكوّنات الجديدة.

---

## 7) ترتيب الأولويات النهائي المختصر

1. Foundation (Arabic default + no Vite + base layout)
2. Shared features (cards/tabs/alerts/toast/notifications/pagination/calendar)
3. Dashboard + pages عالية الاستخدام
4. باقي الصفحات
5. Auth + polishing

> بهذه الخطة سننفذ **جزء جزء / صفحات صفحات** مع اعتماد كامل للثيم الجديد كما طلبت، وبدون Vite، وبافتراضية عربية واضحة، ثم نغلق أي فجوات ترجمة أو RTL/LTR أو dark/light بشكل منهجي.
