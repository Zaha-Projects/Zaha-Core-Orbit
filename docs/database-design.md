# تصميم قاعدة بيانات نظام Zaha OPS

هذا التصميم مبني على وثيقة المتطلبات التشغيلية (SRS) ووثائق التنفيذ المرفقة، مع هدف تحويل الجداول الحالية في ملفات Excel إلى مخطط علائقي موحد يدعم الاعتمادات، التتبع الزمني، والمرفقات المشتركة.

## مبادئ التصميم العامة
- **تتبع الحالة والملكية**: كل كيان رئيسي يحمل مالك/منشئ وحالة عمل (Draft/Submitted/Approved/Closed) لضمان تدفق الاعتماد كما ورد في SRS.
- **الحذف المنطقي**: استخدام حقول `deleted_at` عبر Soft Deletes للحفاظ على السجلات التاريخية.
- **سجل اعتماد زمني**: فصل جدول الاعتمادات عن الجداول الرئيسية لتسجيل كل خطوة بتوقيتها ومُعتمِدها وتعليقها.
- **مرفقات متعددة الأشكال**: توحيد المرفقات في جدول واحد قابل للربط بأي كيان لتقليل التكرار، مع دعم مرفقات متخصصة عند الحاجة.
- **النطاق التنظيمي**: معظم الجداول ترتبط بالفرع والمركز لتطبيق صلاحيات الوصول والتقارير حسب النطاق.
- **التدقيق والتغيير**: حفظ المستخدم المنشئ/المعدل، وخيار سجل تدقيق موحد للأحداث الحساسة.

## الوحدات الأساسية والجداول المقترحة
### 1) المستخدمون والصلاحيات (Access Control)
| الجدول | الهدف | الحقول البارزة |
| --- | --- | --- |
| `users` | تعريف المستخدمين وربطهم بالفرع/المركز | name, email (unique), phone, branch_id, center_id, status, created_at, updated_at, deleted_at |
| `roles` | الأدوار (Relations Manager، Finance Officer...) | key, name_ar, name_en |
| `permissions` | صلاحيات مفصلة حسب الوحدات | key (مثال: agenda.publish), module, action |
| `role_permissions` | ربط الأدوار بالصلاحيات | role_id, permission_id |
| `user_roles` | ربط المستخدم بالأدوار | user_id, role_id |

### 2) الهيكل التنظيمي
| الجدول | الهدف | الحقول |
| --- | --- | --- |
| `branches` | تعريف الفروع | name, city, address |
| `centers` | المراكز التابعة للفروع | branch_id (FK), name |
| `departments` | أقسام مثل العلاقات/البرامج/المالية | name |

### 3) الأجندة السنوية (Agenda)
| الجدول | الهدف | الحقول |
| --- | --- | --- |
| `agenda_events` | الفعاليات السنوية | month, day, event_name, event_category, status (draft/approved/published), created_by, approved_by_relations_at, approved_by_executive_at, notes |
| `agenda_event_targets` | الجهات المستهدفة بدل أعمدة Excel | agenda_event_id (FK), target_type (branch/center/department/committee), target_id, is_participant |
| `agenda_approvals` | سجل الاعتمادات | agenda_event_id (FK), step (relations/executive), decision, comment, approved_by, approved_at |

### 4) الخطة الشهرية (Monthly Activities)
| الجدول | الهدف | الحقول |
| --- | --- | --- |
| `monthly_activities` | النشاط الشهري الأساسي | month, day, title, proposed_date, modified_proposed_date, actual_date, is_in_agenda, agenda_event_id (nullable), description, has_official_attendance, official_attendance_details, needs_official_letters, location_type, location_details, time_from, time_to, media_coverage, status (draft/submitted/approved/completed), branch_id, center_id, created_by, deleted_at |
| `monthly_activity_supplies` | المستلزمات | monthly_activity_id (FK), item_name, available |
| `monthly_activity_team` | فريق العمل | monthly_activity_id (FK), user_id (nullable), member_name, role_desc |
| `monthly_activity_attachments` | مرفقات النشاط | monthly_activity_id (FK), file_type (image/letter/pdf), file_path, uploaded_by |
| `monthly_activity_approvals` | خطوات الاعتماد التفصيلية | monthly_activity_id (FK), step (relations_officer/relations_manager/programs_officer/programs_manager), decision, comment, approved_by, approved_at |
| `activity_attendance` | بيانات الحضور الفعلي | monthly_activity_id (FK), expected_count, actual_count, notes |

### 5) الإيرادات (Revenues)
| الجدول | الهدف | الحقول |
| --- | --- | --- |
| `donations_cash` | الدعم النقدي | donor_type, donor_name, contact_person, phone, day, date, amount, payment_method, receipt_no, purpose_type (activity/center/general), monthly_activity_id (nullable), finance_status, created_by |
| `bookings` | حجوزات المرافق | received_at, booking_date, time_from, time_to, received_by, customer_name, facility_name, payment_type, receipt_ref, paid_at, discount_amount, discount_reason, status, branch_id, center_id |
| `zaha_time_bookings` | حجوزات زها تايم | received_at, booking_date, time_from, time_to, entity_type, contact_person, phone, children_count, payment_cash_ref, payment_electronic_ref, discount_amount, discount_reason, status, branch_id, center_id |
| `payments` | مدفوعات موحدة (Polymorphic) | payable_type (booking/zaha_time/donation), payable_id, method, amount, reference, paid_at, created_by |

### 6) الصيانة (Maintenance)
| الجدول | الهدف | الحقول |
| --- | --- | --- |
| `maintenance_requests` | بلاغات الصيانة | logged_at, type (preventive/emergency), category, description, priority, status, branch_id, center_id, created_by, closed_at |
| `maintenance_work_details` | تفاصيل التنفيذ | maintenance_request_id (FK), start_from, end_to, team_desc, resources_type (internal/external), support_party, estimated_cost, root_cause_analysis, notes, updated_by |
| `maintenance_approvals` | اعتماد الإغلاق | maintenance_request_id (FK), step (branch_head/maintenance_head/it_head), decision, comment, approved_by, approved_at |
| `maintenance_attachments` | مرفقات الصيانة | maintenance_request_id (FK), file_path, file_type, uploaded_by |

### 7) النقل والحركة (Transport)
| الجدول | الهدف | الحقول |
| --- | --- | --- |
| `vehicles` | مركبات زها | plate_no, vehicle_no, status, branch_id |
| `drivers` | السائقون | name, phone, status |
| `trips` | الرحلات اليومية | trip_date, day_name, driver_id, vehicle_id, status, notes, created_by |
| `trip_segments` | مقاطع الرحلة (بديل رحلة 1/2/3) | trip_id (FK), segment_no, location, team_companion, depart_time, return_time, notes |
| `trip_rounds` | الجولات التفصيلية (اختياري) | trip_id (FK), round_no, location, team, start_time, end_time, notes |

### 8) مرفقات وتدقيق مشتركة
| الجدول | الهدف | الحقول |
| --- | --- | --- |
| `attachments` | مرفقات متعددة الأشكال موحدة | attachable_type, attachable_id, file_path, file_type, uploaded_by |
| `audit_logs` | سجل تدقيق عام | user_id, action (create/update/approve/reject), module, entity_type, entity_id, old_values (JSON), new_values (JSON), created_at |

## العلاقات الرئيسية (ER Outline)
- الفرع `branch` يملك عدة مراكز `centers`، والمستخدم ينتمي إلى فرع/مركز اختياريًا.
- حدث الأجندة `agenda_event` يملك أهداف `agenda_event_targets` واعتمادات `agenda_approvals`.
- النشاط الشهري `monthly_activity` يرتبط اختياريًا بحدث أجندة، ويمتلك مستلزمات/فريق/مرفقات/اعتمادات/حضور.
- التبرع النقدي يمكن ربطه بنشاط شهري، والمدفوعات تعمل بطريقة تعدد الأشكال مع الحجوزات وزها تايم والتبرعات.
- طلب الصيانة يملك تفاصيل تنفيذ واعتمادات ومرفقات.
- الرحلة `trip` ترتبط بسائق ومركبة وتمتلك مقاطع وجولات.

## اعتبارات هندسية إضافية
- **الفهارس**: فهرسة الحقول المتكررة في الاستعلام مثل `branch_id`, `center_id`, `status`, `booking_date`, `trip_date` لتحسين تقارير الزمن الحقيقي.
- **السلامة المرجعية**: استخدام قيود FK مع حذف/تحديث متسلسل محدود (ON DELETE CASCADE للجداول الفرعية مثل المرفقات/الموافقات، وRESTRICT للكيانات الرئيسية).
- **التدفق الزمني للاعتماد**: الجداول الفرعية للاعتماد تحفظ التعليقات والوقت والمستخدم لكل خطوة لتلبية متطلبات SRS الخاصة بتتبع القرارات.
- **المرونة المستقبلية**: الحقول مثل `target_type`, `payable_type`, `resources_type` مصممة كمفاتيح نصية قابلة للتوسعة دون تعديل البنية.
- **التكامل مع التخزين**: حقل `file_path` يشير إلى تخزين محلي مع إمكانية النقل إلى S3/Wasabi كما ورد في خطة التنفيذ، مع حفظ نوع الملف لدعم سياسات الأمان.

