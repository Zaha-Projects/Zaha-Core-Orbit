### ما يجب إضافته في سايد الإدارة (Super Admin)
- **صفحات/وحدات إضافية مطلوبة**:
  - إدارة الفروع (CRUD) مع ربط المراكز بكل فرع.
  - إدارة المراكز (CRUD) وربطها بالفرع.
  - إدارة المستخدمين (إنشاء، تفعيل/تعطيل، تعيين فرع/مركز، تعيين دور).
  - إدارة الأدوار والصلاحيات (تعيين صلاحيات بحسب المصفوفة).
  - نظرة شاملة على الاعتمادات (الأجندة، الخطة الشهرية، الصيانة، النقل).
- **الفورمز الأساسية**:
  - فورم إنشاء/تعديل فرع (name, city, address).
  - فورم إنشاء/تعديل مركز (branch_id, name).
  - فورم إنشاء/تعديل مستخدم (name, email, phone, branch_id, center_id, role, status).
  - فورم تعيين صلاحيات الدور (permissions).
- **الكنترولرات المقترحة**:
  - `BranchesManagementController` (index, store, update, destroy).
  - `CentersManagementController` (index, store, update, destroy).
  - `UsersManagementController` (index, store, update, destroy) ✅ موجود.
  - `RolesManagementController` (index, store, update) ✅ موجود.
  - `ApprovalsController` (index) ✅ موجود.

# خطة الصفحات حسب الدور (Role-Based Pages Plan)

هذه الخطة توضح الصفحات المتوقعة في الواجهة حسب الأدوار التشغيلية، مع ربطها بوحدات النظام في خطة التنفيذ لمركز زها الثقافي في الأردن.

## توضيح الفرق بين الفروع والمراكز
- **الفروع**: كيانات جغرافية/إدارية رئيسية تمثل فروع مركز زها الثقافي في الأردن (مثل: عمّان، الزرقاء، إربد)، وتشكل المستوى الأعلى في الهيكل التنظيمي.
- **المراكز**: مواقع تشغيلية تابعة لكل فرع (مثال: مركز أو وحدة خدمة داخل الفرع) وترتبط بالفرع عبر `branch_id`.

## 1) الإدارة العامة (super_admin)
- لوحة تحكم شاملة.
- إدارة المستخدمين والأدوار والصلاحيات.
- إدارة الفروع والمراكز.
- تقارير تشغيلية شاملة.

## 2) العلاقات العامة (relations_manager / relations_officer)
- لوحة الأجندة السنوية.
- إنشاء الفعاليات السنوية وتحديثها.
- اعتماد العلاقات.
- عرض تقارير الأجندة.

### الفورمز والكنترولرات
- **الفورمز الأساسية**:
  - فورم إنشاء/تعديل فعالية الأجندة (title, date, targets, description).
  - فورم إرسال الفعالية للاعتماد وتسجيل الملاحظات.
- **الكنترولرات المقترحة**:
  - `AgendaEventsController` (index, create, store, edit, update, submit).
  - `AgendaApprovalsController` (index, update).

## 3) البرامج والأنشطة (programs_manager / programs_officer)
- لوحة الخطة الشهرية.
- إنشاء الأنشطة وربطها بالأجندة.
- مسار الاعتماد للأنشطة.
- متابعة التنفيذ والمرفقات.

### الفورمز والكنترولرات
- **الفورمز الأساسية**:
  - فورم إنشاء/تعديل نشاط شهري (month, day, title, branch/center, agenda link, status).
  - فورم إضافة فريق/مستلزمات/مرفقات للنشاط.
  - فورم تحديث التنفيذ والإغلاق.
- **الكنترولرات المقترحة**:
  - `MonthlyActivitiesController` (index, create, store, edit, update, submit, close).
  - `MonthlyActivitySuppliesController` (store, update, destroy).
  - `MonthlyActivityTeamController` (store, update, destroy).
  - `MonthlyActivityAttachmentsController` (store, destroy).
  - `MonthlyActivityApprovalsController` (index, update).

## 4) الشؤون المالية (finance_officer)
- لوحة الإيرادات.
- الدعم النقدي.
- الحجوزات (بما في ذلك زها تايم).
- إدارة المدفوعات والخصومات.
- تقارير الإيرادات الشهرية.

### الفورمز والكنترولرات
- **الفورمز الأساسية**:
  - فورم تسجيل دعم نقدي.
  - فورم إنشاء/تعديل حجز مرفق وحجز زها تايم.
  - فورم تسجيل دفعة/خصم.
- **الكنترولرات المقترحة**:
  - `DonationsCashController` (index, create, store, edit, update).
  - `BookingsController` (index, create, store, edit, update).
  - `ZahaTimeBookingsController` (index, create, store, edit, update).
  - `PaymentsController` (store, update).

## 5) الصيانة (maintenance_officer)
- لوحة الصيانة.
- بلاغات الصيانة.
- تفاصيل التنفيذ والسبب الجذري.
- اعتماد الإغلاق.

### الفورمز والكنترولرات
- **الفورمز الأساسية**:
  - فورم بلاغ صيانة (type, category, description, priority).
  - فورم تفاصيل التنفيذ وإغلاق البلاغ.
  - فورم رفع مرفقات قبل/بعد.
- **الكنترولرات المقترحة**:
  - `MaintenanceRequestsController` (index, create, store, edit, update, close).
  - `MaintenanceWorkDetailsController` (store, update).
  - `MaintenanceAttachmentsController` (store, destroy).
  - `MaintenanceApprovalsController` (index, update).

## 6) النقل والحركة (transport_officer)
- لوحة النقل.
- إدارة المركبات والسائقين.
- جدولة الرحلات اليومية.
- طباعة جدول الرحلات.

### الفورمز والكنترولرات
- **الفورمز الأساسية**:
  - فورم تسجيل مركبة/سائق.
  - فورم إنشاء/تعديل رحلة يومية وتقسيم المقاطع.
  - فورم تحديث حالة الرحلة وإغلاقها.
- **الكنترولرات المقترحة**:
  - `VehiclesController` (index, create, store, edit, update).
  - `DriversController` (index, create, store, edit, update).
  - `TripsController` (index, create, store, edit, update, close).
  - `TripSegmentsController` (store, update, destroy).
  - `TripRoundsController` (store, update, destroy).

## 7) التقارير (reports_viewer)
- لوحة التقارير.
- تقارير الأجندة والأنشطة.
- تقارير الإيرادات.
- تقارير الصيانة والنقل.

### الفورمز والكنترولرات
- **الفورمز الأساسية**:
  - فورم فلاتر التقارير (الفترة، الفرع/المركز، الحالة).
- **الكنترولرات المقترحة**:
  - `ReportsController` (index, export).
  - `AgendaReportsController`, `MonthlyReportsController`, `FinanceReportsController`, `MaintenanceReportsController`, `TransportReportsController` (index, export).

## 8) موظف عام (staff)
- لوحة وصول سريعة.
- عرض الأجندة المعتمدة.
- عرض الأنشطة الشهرية ذات الصلة.

### الفورمز والكنترولرات
- **الفورمز الأساسية**:
  - فورم فلاتر بسيطة للعرض (تاريخ/فرع).
- **الكنترولرات المقترحة**:
  - `StaffDashboardController` (index).
  - `StaffAgendaController` (index).
  - `StaffMonthlyActivitiesController` (index).
