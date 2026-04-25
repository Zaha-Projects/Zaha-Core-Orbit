# ZAHA Flow Theme

قالب Dashboard حديث مبني على Bootstrap 5 مع دعم كامل للغة العربية والإنجليزية، واتجاهي العرض RTL/LTR، ووضعي العرض Light/Dark.

---

## نظرة عامة

المشروع يوفر واجهة إدارة جاهزة تشمل:
- نظام تنقل جانبي (Sidebar) متجاوب.
- شريط علوي (Topbar) مع إجراءات سريعة.
- مجموعة صفحات UI عملية (Dashboard، تقويم، إشعارات، نماذج، ألوان، Pagination...).
- مكوّنات تفاعلية مثل Toast وSweetAlert وFullCalendar وFlatpickr.
- ترجمة ديناميكية من ملفات JSON.

---

## المزايا الأساسية (Core Features)

### 1) دعم RTL/LTR بشكل كامل
- التحويل بين العربية والإنجليزية يتم بضغطة زر.
- عند تغيير اللغة يتم تحديث:
  - `lang` و`dir` على مستوى الصفحة.
  - نسخة Bootstrap المناسبة (`bootstrap.rtl` أو النسخة العادية).
  - اتجاه التقويم والمحتوى.
  - مكان السايدبار (يمين في العربية / يسار في الإنجليزية) على الشاشات الكبيرة والصغيرة.

### 2) وضع Light / Dark
- ثيمات جاهزة تعتمد CSS Variables.
- جميع العناصر الرئيسية (الخلفيات، النصوص، البطاقات، السايدبار، الحدود) تتبدّل تلقائيًا عند تغيير الثيم.

### 3) Sidebar احترافي ومتجاوب
- تصميم Sidebar حديث بألوان متوافقة مع الثيم.
- دعم Collapse على الشاشات الكبيرة.
- فتح/إغلاق بأسلوب Offcanvas على الشاشات الصغيرة.
- نقل اتجاه السايدبار تلقائيًا مع اللغة.
- لوجو موحد جديد بصيغة SVG مع عرض أيقونة فقط داخل السايدبار.

### 4) نظام ترجمة i18n
- ملفات ترجمة منفصلة:
  - `locales/ar/common.json`
  - `locales/en/common.json`
- كل النصوص المهمة تعتمد `data-i18n`.
- fallback تلقائي إذا تعذر تحميل ملف الترجمة.

### 5) مكوّنات تفاعلية جاهزة
- **FullCalendar**: عرض تقويم شهري/أسبوعي مع دعم اللغة والاتجاه.
- **Flatpickr**: حقل تاريخ/وقت.
- **Toastr**: إشعارات نجاح/تحذير.
- **SweetAlert2**: رسائل تنبيه معلوماتية.

### 6) صفحات Authentication
- صفحة Login.
- صفحة Forgot Password.
- تصميم متناسق مع هوية المشروع.

---

## شرح الصفحات والفيتشرز

### `index.html` — Dashboard الرئيسي
- بطاقات إحصائية.
- عناصر واجهة مختصرة للحالة العامة.
- شريط علوي + سايدبار + أدوات تبديل اللغة والثيم.

### `calendar.html` — التقويم
- عرض التقويم عبر FullCalendar.
- يتغير اتجاهه تلقائيًا مع اللغة.

### `datetime.html` — التاريخ والوقت
- حقل اختيار تاريخ/وقت عبر Flatpickr.
- متوافق مع الثيم العام.

### `notifications.html` — الإشعارات
- أمثلة عملية على إشعارات Toastr وSweetAlert2.

### `card.html` — البطاقات
- نماذج بطاقات UI متعددة الاستخدامات.

### `Chart.html` — التحليلات
- صفحة مخصصة لعرض كروت/مؤشرات التحليل.

### `Input.html` — النماذج
- عناصر إدخال (Forms) متنوعة وجاهزة للتوسعة.

### `sidebar.html` — توضيح السايدبار
- صفحة مرجعية لسلوك وتنسيق السايدبار.

### `toast.htm` — رسائل Toast
- أمثلة مركزة للإشعارات السريعة.

### `pagination.html` — ترقيم الصفحات
- أنماط جاهزة للـ Pagination.

### `colors.html` — لوحة الألوان
- توضيح ألوان الهوية البصرية والثيمات.

### `zaha-identity.html` — هوية المشروع
- عرض عناصر الهوية: الشعار + الألوان + التوجه البصري.

### `feature-readme.html` — شرح سريع للميزات
- صفحة داخل القالب لتوضيح الفيتشرز الأساسية للمستخدم النهائي.

### `Login.html` و`forgot-password.html`
- صفحات دخول واستعادة كلمة المرور بتصميم متسق مع القالب.

---

## هيكل المشروع

```text
Arabic Theame/
├── assets/
│   ├── favicon/
│   │   ├── favicon.ico
│   │   ├── favicon-16x16.png
│   │   ├── favicon-32x32.png
│   │   ├── apple-touch-icon.png
│   │   ├── android-chrome-192x192.png
│   │   ├── android-chrome-512x512.png
│   │   └── site.webmanifest
│   ├── images/
│   └── logos/
│       ├── logo.svg
│       ├── logo2.svg
│       ├── logo.png
│       └── logo2.png
├── css/
│   ├── Theme.css
│   └── Style.css
├── js/
│   └── app.js
├── locales/
│   ├── ar/common.json
│   └── en/common.json
├── *.html / *.htm
├── vendor/
└── README.md
```

---

## كيفية التشغيل

1. شغّل أي static server داخل مجلد المشروع.
2. افتح الصفحة الرئيسية `index.html`.
3. جرّب:
   - تبديل اللغة (AR/EN).
   - تبديل الثيم (Light/Dark).
   - فتح/إغلاق السايدبار على Desktop وMobile.

مثال سريع عبر Python:

```bash
cd "Arabic Theame"
python -m http.server 8080
```

ثم افتح:
`http://localhost:8080/index.html`

---

## التقنيات المستخدمة

- Bootstrap 5.3
- Font Awesome
- FullCalendar
- Flatpickr
- Toastr
- SweetAlert2
- Vanilla JavaScript (بدون إطار عمل)

---

## ملاحظات للتطوير

- لإضافة ترجمة جديدة: أضف الملف داخل `locales/<lang>/common.json`.
- لإضافة صفحة جديدة: انسخ أحد القوالب الحالية وحافظ على نفس بنية الـ Topbar/Sidebar.
- للمحافظة على التوافق RTL/LTR: استخدم الخصائص المنطقية CSS مثل `inset-inline-*` و`margin-inline-*`.
