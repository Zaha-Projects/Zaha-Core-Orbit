# Site Map — Zaha OPS

> خريطة صفحات النظام الحالية حسب `routes/web.php`، مع توضيح الرولز (Roles) والفورمز/الإجراءات (POST/PUT/PATCH/DELETE).

## 1) الصفحات العامة (Public)
- `GET /` الصفحة الترحيبية (`welcome`).

## 2) المصادقة (Guest)
- `GET /login` صفحة تسجيل الدخول.
- `POST /login` فورم تسجيل الدخول.
- `GET /register` صفحة إنشاء حساب.
- `POST /register` فورم إنشاء الحساب.

## 3) صفحات المستخدم الموثق (Authenticated)
- `POST /logout` تسجيل الخروج.
- `GET /dashboard` صفحة توجيه عامة حسب الرول.

---

## 4) Super Admin

### صفحات
- `GET /dashboard/admin` لوحة الأدمن.
- `GET /dashboard/admin/reports` تقارير الإدارة.
- `GET /dashboard/admin/roles` إدارة الأدوار.
- `GET /dashboard/admin/users` إدارة المستخدمين.
- `GET /dashboard/admin/branches` إدارة الفروع.
- `GET /dashboard/admin/centers` إدارة المراكز.
- `GET /dashboard/admin/departments` إدارة الأقسام.
- `GET /dashboard/admin/approvals` متابعة الاعتمادات.

### فورمز / إجراءات
- أدوار:
  - `POST /dashboard/admin/roles` إنشاء دور.
  - `PUT /dashboard/admin/roles/{role}` تحديث صلاحيات/بيانات الدور.
- مستخدمون:
  - `POST /dashboard/admin/users` إنشاء مستخدم.
  - `PUT /dashboard/admin/users/{user}` تعديل مستخدم.
  - `DELETE /dashboard/admin/users/{user}` حذف مستخدم.
- فروع:
  - `POST /dashboard/admin/branches` إنشاء فرع.
  - `PUT /dashboard/admin/branches/{branch}` تعديل فرع.
  - `DELETE /dashboard/admin/branches/{branch}` حذف فرع.
- مراكز:
  - `POST /dashboard/admin/centers` إنشاء مركز.
  - `PUT /dashboard/admin/centers/{center}` تعديل مركز.
  - `DELETE /dashboard/admin/centers/{center}` حذف مركز.
- أقسام:
  - `POST /dashboard/admin/departments` إنشاء قسم.
  - `PUT /dashboard/admin/departments/{department}` تعديل قسم.
  - `DELETE /dashboard/admin/departments/{department}` حذف قسم.

---

## 5) Relations (relations_manager / relations_officer)

### صفحات
- `GET /dashboard/relations/manager` لوحة مدير العلاقات.
- `GET /dashboard/relations/officer` لوحة ضابط العلاقات.
- `GET /dashboard/relations/agenda` قائمة الأجندة.
- `GET /dashboard/relations/agenda/create` إنشاء فعالية أجندة.
- `GET /dashboard/relations/agenda/{agendaEvent}/edit` تعديل فعالية أجندة.
- `GET /dashboard/relations/agenda/approvals` صفحة اعتمادات الأجندة.

### فورمز / إجراءات
- `POST /dashboard/relations/agenda` إنشاء فعالية.
- `PUT /dashboard/relations/agenda/{agendaEvent}` تحديث فعالية.
- `PATCH /dashboard/relations/agenda/{agendaEvent}/submit` إرسال للاعتماد.
- `PUT /dashboard/relations/agenda/approvals/{agendaEvent}` قرار اعتماد/إرجاع.

---

## 6) Programs (programs_manager / programs_officer)

### صفحات
- `GET /dashboard/programs/manager` لوحة مدير البرامج.
- `GET /dashboard/programs/officer` لوحة ضابط البرامج.
- `GET /dashboard/programs/monthly-activities` قائمة الأنشطة الشهرية.
- `GET /dashboard/programs/monthly-activities/create` إنشاء نشاط.
- `GET /dashboard/programs/monthly-activities/{monthlyActivity}/edit` تعديل نشاط.
- `GET /dashboard/programs/monthly-activities/approvals` اعتمادات الأنشطة.

### فورمز / إجراءات
- أنشطة شهرية:
  - `POST /dashboard/programs/monthly-activities` إنشاء نشاط.
  - `PUT /dashboard/programs/monthly-activities/{monthlyActivity}` تحديث نشاط.
  - `PATCH /dashboard/programs/monthly-activities/{monthlyActivity}/submit` إرسال للاعتماد.
  - `PATCH /dashboard/programs/monthly-activities/{monthlyActivity}/close` إغلاق النشاط.
- مستلزمات:
  - `POST /dashboard/programs/monthly-activities/{monthlyActivity}/supplies`
  - `PUT /dashboard/programs/supplies/{monthlyActivitySupply}`
  - `DELETE /dashboard/programs/supplies/{monthlyActivitySupply}`
- فريق عمل:
  - `POST /dashboard/programs/monthly-activities/{monthlyActivity}/team`
  - `PUT /dashboard/programs/team/{monthlyActivityTeam}`
  - `DELETE /dashboard/programs/team/{monthlyActivityTeam}`
- مرفقات:
  - `POST /dashboard/programs/monthly-activities/{monthlyActivity}/attachments`
  - `DELETE /dashboard/programs/attachments/{monthlyActivityAttachment}`
- اعتماد:
  - `PUT /dashboard/programs/monthly-activities/approvals/{monthlyActivity}`

---

## 7) Finance (finance_officer)

### صفحات
- `GET /dashboard/finance` لوحة المالية.
- `GET /dashboard/finance/donations` قائمة الدعم النقدي.
- `GET /dashboard/finance/donations/create` إنشاء دعم نقدي.
- `GET /dashboard/finance/donations/{donationCash}/edit` تعديل دعم نقدي.
- `GET /dashboard/finance/bookings` قائمة الحجوزات.
- `GET /dashboard/finance/bookings/create` إنشاء حجز.
- `GET /dashboard/finance/bookings/{booking}/edit` تعديل حجز.
- `GET /dashboard/finance/zaha-time` قائمة زها تايم.
- `GET /dashboard/finance/zaha-time/create` إنشاء حجز زها تايم.
- `GET /dashboard/finance/zaha-time/{zahaTimeBooking}/edit` تعديل حجز زها تايم.
- `GET /dashboard/finance/payments` قائمة المدفوعات.

### فورمز / إجراءات
- دعم نقدي:
  - `POST /dashboard/finance/donations`
  - `PUT /dashboard/finance/donations/{donationCash}`
- حجوزات:
  - `POST /dashboard/finance/bookings`
  - `PUT /dashboard/finance/bookings/{booking}`
- زها تايم:
  - `POST /dashboard/finance/zaha-time`
  - `PUT /dashboard/finance/zaha-time/{zahaTimeBooking}`
- مدفوعات:
  - `POST /dashboard/finance/payments`
  - `PUT /dashboard/finance/payments/{payment}`

---

## 8) Maintenance (maintenance_officer)

### صفحات
- `GET /dashboard/maintenance` لوحة الصيانة.
- `GET /dashboard/maintenance/requests` قائمة الطلبات.
- `GET /dashboard/maintenance/requests/create` إنشاء طلب صيانة.
- `GET /dashboard/maintenance/requests/{maintenanceRequest}/edit` تعديل طلب.
- `GET /dashboard/maintenance/approvals` صفحة اعتمادات الصيانة.

### فورمز / إجراءات
- طلبات:
  - `POST /dashboard/maintenance/requests`
  - `PUT /dashboard/maintenance/requests/{maintenanceRequest}`
  - `PATCH /dashboard/maintenance/requests/{maintenanceRequest}/close`
- تفاصيل عمل:
  - `POST /dashboard/maintenance/requests/{maintenanceRequest}/work-details`
  - `PUT /dashboard/maintenance/work-details/{maintenanceWorkDetail}`
- مرفقات:
  - `POST /dashboard/maintenance/requests/{maintenanceRequest}/attachments`
  - `DELETE /dashboard/maintenance/attachments/{maintenanceAttachment}`
- اعتماد:
  - `PUT /dashboard/maintenance/approvals/{maintenanceRequest}`

---

## 9) Transport (transport_officer)

### صفحات
- `GET /dashboard/transport` لوحة النقل.
- `GET /dashboard/transport/vehicles` قائمة المركبات.
- `GET /dashboard/transport/vehicles/create` إنشاء مركبة.
- `GET /dashboard/transport/vehicles/{vehicle}/edit` تعديل مركبة.
- `GET /dashboard/transport/drivers` قائمة السائقين.
- `GET /dashboard/transport/drivers/create` إنشاء سائق.
- `GET /dashboard/transport/drivers/{driver}/edit` تعديل سائق.
- `GET /dashboard/transport/trips` قائمة الرحلات.
- `GET /dashboard/transport/trips/create` إنشاء رحلة.
- `GET /dashboard/transport/trips/{trip}/edit` تعديل رحلة.

### فورمز / إجراءات
- مركبات:
  - `POST /dashboard/transport/vehicles`
  - `PUT /dashboard/transport/vehicles/{vehicle}`
- سائقون:
  - `POST /dashboard/transport/drivers`
  - `PUT /dashboard/transport/drivers/{driver}`
- رحلات:
  - `POST /dashboard/transport/trips`
  - `PUT /dashboard/transport/trips/{trip}`
  - `PATCH /dashboard/transport/trips/{trip}/close`
- مقاطع الرحلة:
  - `POST /dashboard/transport/trips/{trip}/segments`
  - `PUT /dashboard/transport/segments/{tripSegment}`
  - `DELETE /dashboard/transport/segments/{tripSegment}`
- جولات الرحلة:
  - `POST /dashboard/transport/trips/{trip}/rounds`
  - `PUT /dashboard/transport/rounds/{tripRound}`
  - `DELETE /dashboard/transport/rounds/{tripRound}`

---

## 10) Reports (reports_viewer)

### صفحات
- `GET /dashboard/reports` لوحة التقارير.
- `GET /dashboard/reports/overview` نظرة عامة.
- `GET /dashboard/reports/agenda` تقرير الأجندة.
- `GET /dashboard/reports/monthly` تقرير الخطة الشهرية.
- `GET /dashboard/reports/finance` تقرير المالية.
- `GET /dashboard/reports/maintenance` تقرير الصيانة.
- `GET /dashboard/reports/transport` تقرير النقل.

### فورمز / إجراءات
- تصدير:
  - `POST /dashboard/reports/overview/export`
  - `POST /dashboard/reports/agenda/export`
  - `POST /dashboard/reports/monthly/export`
  - `POST /dashboard/reports/finance/export`
  - `POST /dashboard/reports/maintenance/export`
  - `POST /dashboard/reports/transport/export`

---

## 11) Staff

### صفحات
- `GET /dashboard/staff` لوحة الموظف.
- `GET /dashboard/staff/agenda` عرض الأجندة.
- `GET /dashboard/staff/activities` عرض الأنشطة الشهرية.

### فورمز / إجراءات
- لا توجد فورمز تعديل ضمن هذا المسار (قراءة فقط في المسارات الحالية).

---

## 12) قائمة الرولز الفعّالة في الموقع
- `super_admin`
- `relations_manager`
- `relations_officer`
- `programs_manager`
- `programs_officer`
- `finance_officer`
- `maintenance_officer`
- `transport_officer`
- `reports_viewer`
- `staff`

> بالإضافة لدعم صلاحيات تفصيلية في بعض المسارات مثل: `departments.view` و `departments.manage`.

---

## 13) دليل اختبار السيناريوهات (Step-by-Step)

> الهدف: كيف تختبر كل سيناريو عملياً من الواجهة.

### A) سيناريو المصادقة
1. افتح `/login`.
2. أدخل بيانات مستخدم صحيح واضغط دخول.
3. تأكد من التحويل إلى `/dashboard`.
4. اختبر تسجيل الخروج من زر Logout.
5. أعد فتح أي صفحة محمية (مثلاً `/dashboard/admin`) وتأكد أنه يطلب تسجيل دخول.

### B) Super Admin — إدارة المستخدمين/الفروع/المراكز/الأقسام
1. سجل بحساب `super_admin`.
2. افتح `/dashboard/admin` وتأكد ظهور روابط الإدارة.
3. اذهب إلى `/dashboard/admin/users`:
   - أنشئ مستخدم جديد (POST).
   - عدّل المستخدم (PUT).
   - احذف المستخدم (DELETE).
4. كرر نفس النمط على:
   - `/dashboard/admin/branches`
   - `/dashboard/admin/centers`
   - `/dashboard/admin/departments`
5. في صفحة الأقسام: اختبر البحث والترتيب ثم نفّذ create/update/delete وتأكد بقاء `search/sort` بعد العملية.

### C) صلاحيات الأقسام (Permission-based)
1. أنشئ مستخدم بدون دور `super_admin`.
2. امنحه `departments.view` فقط:
   - يجب يقدر يدخل `/dashboard/admin/departments`.
   - يجب **لا** يقدر ينفذ create/update/delete.
3. امنحه `departments.manage`:
   - يجب يقدر ينفذ create/update/delete على الأقسام.

### D) Relations — الأجندة
1. سجل بحساب `relations_officer` أو `relations_manager`.
2. افتح `/dashboard/relations/agenda`.
3. أنشئ فعالية جديدة من `/create`.
4. عدّل الفعالية من `/edit/{id}`.
5. نفّذ submit للاعتماد.
6. افتح `/dashboard/relations/agenda/approvals` وسجّل قرار الاعتماد/الإرجاع.

### E) Programs — الخطة الشهرية
1. سجل بحساب `programs_officer` أو `programs_manager`.
2. افتح `/dashboard/programs/monthly-activities`.
3. أنشئ نشاط جديد.
4. أضف supplies + team + attachments.
5. نفّذ submit ثم close.
6. افتح `/dashboard/programs/monthly-activities/approvals` واختبر تحديث حالة الاعتماد.

### F) Finance — الإيرادات والحجوزات
1. سجل بحساب `finance_officer`.
2. اختبر Donations:
   - إنشاء من `/finance/donations/create`.
   - تعديل من `/finance/donations/{id}/edit`.
3. اختبر Bookings:
   - إنشاء + تعديل.
4. اختبر Zaha Time:
   - إنشاء + تعديل.
5. اختبر Payments:
   - إضافة دفعة جديدة.
   - تعديل دفعة قائمة.

### G) Maintenance — الطلبات والاعتمادات
1. سجل بحساب `maintenance_officer`.
2. افتح `/dashboard/maintenance/requests` وأنشئ طلب صيانة.
3. عدّل الطلب.
4. أضف work details ومرفقات.
5. نفّذ close للطلب.
6. افتح `/dashboard/maintenance/approvals` واختبر تحديث قرار الاعتماد.

### H) Transport — المركبات/السائقون/الرحلات
1. سجل بحساب `transport_officer`.
2. أنشئ مركبة وعدّلها.
3. أنشئ سائق وعدّله.
4. أنشئ رحلة جديدة.
5. أضف segments وrounds وعدّل/احذف بعضها.
6. أغلق الرحلة عبر `close` وتأكد تغير الحالة.

### I) Reports — التقارير والتصدير
1. سجل بحساب `reports_viewer`.
2. افتح:
   - `/dashboard/reports/overview`
   - `/dashboard/reports/agenda`
   - `/dashboard/reports/monthly`
   - `/dashboard/reports/finance`
   - `/dashboard/reports/maintenance`
   - `/dashboard/reports/transport`
3. لكل صفحة، اختبر زر/فورم التصدير (POST export).
4. تحقق أن الملف الناتج يتم تنزيله أو أن الرسالة المناسبة تظهر بنجاح.

### J) Staff — القراءة فقط
1. سجل بحساب `staff`.
2. افتح `/dashboard/staff`, `/dashboard/staff/agenda`, `/dashboard/staff/activities`.
3. تأكد عدم وجود أزرار تعديل/حذف في هذه المسارات.

### K) سيناريوهات رفض الوصول (Authorization Negative Tests)
1. افتح مسار خاص برول آخر (مثال: مستخدم staff يدخل `/dashboard/admin/users`).
2. تأكد رجوع `403` أو إعادة التوجيه المناسبة.
3. كرر عينة على مسارات البرامج/المالية/النقل للتأكد من العزل بين الرولز.
