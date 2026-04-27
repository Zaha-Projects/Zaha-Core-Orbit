# Zaha Core Orbit

منصة تشغيل داخلية مبنية على **Laravel 8** لإدارة الأعمال المؤسسية في زها، وتشمل: الأجندة السنوية، الأنشطة الشهرية، المالية، الصيانة، النقل، والتقارير الموحدة مع صلاحيات حسب الأدوار.

---

## المحتوى
- [نظرة عامة](#نظرة-عامة)
- [أهم الميزات](#أهم-الميزات)
- [المتطلبات](#المتطلبات)
- [الإعداد المحلي السريع](#الإعداد-المحلي-السريع)
- [تشغيل الواجهات والأصول](#تشغيل-الواجهات-والأصول)
- [الاختبارات والأوامر المفيدة](#الاختبارات-والأوامر-المفيدة)
- [الوحدات الرئيسية في النظام](#الوحدات-الرئيسية-في-النظام)
- [الهيكل العام للمشروع](#الهيكل-العام-للمشروع)
- [ملاحظات النشر](#ملاحظات-النشر)
- [استكشاف الأخطاء](#استكشاف-الأخطاء)

---

## نظرة عامة

**Zaha Core Orbit** هو نظام إدارة مؤسسي متعدد الأدوار (Role-Based) مع لوحة تحكم ديناميكية، ويدعم العربية/الإنجليزية مع واجهات مبنية على Blade وواجهات إدارية موسعة.

النظام يركز على:
- ربط التخطيط السنوي (الأجندة) بالتنفيذ الشهري.
- دورة اعتماد (Workflow) قابلة للإدارة.
- تقارير تشغيلية ومؤسسية موحدة.
- عزل البيانات بحسب الفروع والصلاحيات.

---

## أهم الميزات

- **صلاحيات وأدوار متقدمة** عبر `spatie/laravel-permission`.
- **لوحات مخصصة لكل دور** (إدارة عليا، علاقات، برامج، مالية، صيانة، نقل، تقارير...).
- **أجندة سنوية** مع اعتماد ومتابعة ومشاركة الوحدات/الفروع.
- **أنشطة شهرية** متصلة بالأجندة + سير موافقات.
- **وحدات تشغيلية**: مالية، صيانة، نقل، حركة، تقارير.
- **تقارير وتصدير** لعدة مجالات (Agenda, Monthly, Finance, Maintenance, Transport, KPIs, Enterprise).
- **دعم الواجهة** للثيم واللغة عبر جلسة المستخدم.

---

## المتطلبات

> القيم التالية مناسبة للتشغيل المحلي والتطوير.

- PHP `^7.3|^8.0`
- Composer
- MySQL / MariaDB
- Node.js + npm
- Laravel 8

---

## الإعداد المحلي السريع

1) تثبيت الاعتماديات:

```bash
composer install
npm install
```

2) إعداد البيئة:

```bash
cp .env.example .env
php artisan key:generate
```

3) ضبط الاتصال بقاعدة البيانات في `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

4) ترحيل الجداول وتهيئة بيانات أولية:

```bash
php artisan migrate --seed
```

5) تشغيل المشروع:

```bash
php artisan serve
```

ثم افتح:
`http://127.0.0.1:8000`

---

## تشغيل الواجهات والأصول

أوامر البناء (Laravel Mix):

```bash
npm run dev
npm run watch
npm run prod
```

---

## الاختبارات والأوامر المفيدة

```bash
php artisan test
php artisan route:list
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## الوحدات الرئيسية في النظام

> أمثلة المسارات التالية من ملف `routes/web.php`.

- **Dashboard عام**: `/dashboard`
- **إدارة الأدوار والمستخدمين/سير العمل**: `/dashboard/admin/*`
- **الأجندة السنوية (العلاقات)**: `/dashboard/relations/agenda*`
- **الأنشطة الشهرية**: `/dashboard/relations/monthly-activities*`
- **المالية**: `/dashboard/finance/*`
- **الصيانة**: `/dashboard/maintenance/*`
- **النقل والحركة**: `/dashboard/transport/*`
- **التقارير**: `/dashboard/reports/*`
- **Enterprise / الأرشفة والإحصاءات المؤسسية**: `/dashboard/enterprise*`

---

## الهيكل العام للمشروع

```text
app/
  Http/
    Controllers/
      Roles/
      Web/
bootstrap/
config/
database/
  migrations/
  seeders/
public/
  assets/
resources/
  views/
routes/
  web.php
```

---

## ملاحظات النشر

- تأكد من ضبط `APP_ENV`, `APP_DEBUG`, و `APP_URL` للإنتاج.
- نفّذ:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- امنح صلاحيات الكتابة للمجلدين:
  - `storage/`
  - `bootstrap/cache/`

---

## استكشاف الأخطاء

- **خطأ `vendor/autoload.php` مفقود**:
  - نفّذ `composer install`.

- **الصفحة بدون CSS/JS محدث**:
  - نفّذ `npm install` ثم `npm run dev`.

- **مشاكل قاعدة البيانات**:
  - تحقق من `.env` ثم جرّب `php artisan migrate:fresh --seed` (في بيئة التطوير فقط).

---

## مراجع إضافية

- Laravel Documentation: https://laravel.com/docs/8.x
- Spatie Permission: https://spatie.be/docs/laravel-permission

