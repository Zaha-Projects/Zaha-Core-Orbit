# Code Change Sitemap (Single Reference)

> هدف هذا الملف: يكون مرجع ثابت عند أي تعديل جديد لتجنب تكرار CSS/JS أو إعادة كتابة نفس المنطق في أكثر من مكان.

## 1) Shared Sources of Truth (لا تكرر)

### Shared CSS
- `public/assets/css/event-ui-shared.css`
  - أنماط الكاردز المشتركة (grid/card/meta/actions).
  - baseline لأزرار تبديل العرض وحالة الـ status badges.

### Shared JS
- `public/assets/js/ui-shared.js`
  - `ZahaUi.initViewToggle(root, initialView)`
  - `ZahaUi.readJsonScript(id, fallback)`

> أي صفحة جديدة تحتاج table/calendar toggle أو قراءة JSON boot data يجب أن تستخدم هذه الدوال بدل نسخ المنطق.

## 2) Page → Asset Map (خريطة الصفحات)

### Agenda
- `resources/views/pages/agenda/events/index.blade.php`
  - CSS: `event-ui-shared.css`, `agenda-events-index.css`
  - JS: `ui-shared.js`, `agenda-events-index.js`
- `resources/views/pages/agenda/events/_form.blade.php`
  - CSS: `agenda-events-form.css`
  - JS: `agenda-events-form.js`

### Monthly Activities
- `resources/views/pages/monthly_activities/activities/index.blade.php`
  - CSS: `event-ui-shared.css`, `monthly-activities-index.css`
  - JS: `ui-shared.js`, `monthly-activities-index.js`
- `resources/views/pages/monthly_activities/activities/_form.blade.php`
  - CSS: `monthly-activity-form.css`
  - JS: `monthly-activity-form.js`
- `resources/views/pages/monthly_activities/activities/edit.blade.php`
  - JS: `monthly-activity-edit.js`
- `resources/views/pages/monthly_activities/activities/show.blade.php`
  - CSS: `monthly-activity-show.css`

### Other Monthly pages
- `resources/views/pages/monthly_activities/approvals/index.blade.php`
  - CSS: `monthly-approvals.css`
  - JS: `monthly-approvals.js`
- `resources/views/pages/monthly_activities/lookups/admin.blade.php`
  - CSS: `lookups-admin.css`

## 3) Mandatory Change Workflow (قبل أي Merge)

1. **Reuse-first**: ابحث هل الكلاس/الدالة موجودة في shared assets قبل إنشاء ملف/منطق جديد.
2. **No inline blocks**: لا تضف `<style>` أو `<script>` inline (باستثناء JSON boot scripts عند الحاجة).
3. **JSON bootstrapping**: استخدم `ZahaUi.readJsonScript` بدل `JSON.parse(...)` المتكرر.
4. **View toggle**: استخدم `ZahaUi.initViewToggle` بدل كتابة switchView جديد.
5. **Update map**: إذا أضفت صفحة/asset جديدة، حدث هذا الملف فورًا.
6. **Run verification script**: شغّل `scripts/verify_view_assets.sh`.

## 4) Duplication Guardrails

- أي selector مشترك بين أكثر من صفحة index/card يجب أن ينتقل إلى `event-ui-shared.css`.
- أي helper JS مستخدم في صفحتين+ يجب نقله إلى `ui-shared.js`.
- أي إضافة جديدة للواجهات يجب أن تضيف/تحدث سطرًا في **Page → Asset Map**.

## 5) Quick Commands

```bash
# تأكد من عدم وجود inline style/script في الصفحات المستهدفة
scripts/verify_view_assets.sh

# فحص syntax للملفات المهمة
php -l resources/views/pages/agenda/events/index.blade.php
php -l resources/views/pages/monthly_activities/activities/index.blade.php
node -e "const fs=require('fs'); for (const f of fs.readdirSync('public/assets/js')) if(f.endsWith('.js')) new Function(fs.readFileSync('public/assets/js/'+f,'utf8'));"
```
