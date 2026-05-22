# ملخص قاعدة البيانات (التركيز: الأجندة السنوية والأنشطة الشهرية)

تم بناء هذا الملخص من ملفات الـ migrations والخدمات المرتبطة بسير العمل.

## 1) الجداول الأساسية للأجندة السنوية (Agenda)

### agenda_events
أهم الحقول:
- id
- event_date / event_day / month / day
- event_name
- department_id / owner_department_id
- event_category_id / event_category
- plan_type / event_type
- status
- relations_approval_status / executive_approval_status
- approved_by_relations_at / approved_by_executive_at
- is_mandatory / is_unified / is_active
- is_archived / archived_year
- notes / agenda_plan_file / version
- created_by
- timestamps

### agenda_participations
- id
- agenda_event_id
- entity_type / entity_id
- participation_status
- proposed_date / actual_execution_date
- branch_plan_file
- updated_by
- timestamps

### agenda_approvals
- id
- agenda_event_id
- step / decision / comment
- approved_by / approved_at
- timestamps

### agenda_event_targets
- id
- agenda_event_id
- target_type / target_id
- is_participant
- timestamps

### agenda_event_partner_departments
- id
- agenda_event_id
- department_id
- timestamps

### event_categories
- id
- department_id
- name
- active
- timestamps

## 2) الجداول الأساسية للأنشطة الشهرية (Monthly Activities)

### monthly_activities
أهم الحقول (بعد كل التوسعات):
- تعريف وتخطيط: id, month, day, activity_date, title, description, short_description
- الربط بالأجندة: is_in_agenda, is_from_agenda, agenda_event_id, plan_type, participation_status
- التواريخ: proposed_date, modified_proposed_date, rescheduled_date, actual_date
- إعادة الجدولة: reschedule_reason, cancellation_reason, relations_approval_on_reschedule
- الموقع: location_type, location_details, internal_location, building, room, outside_place_name, outside_google_maps_url, outside_address, outside_contact_number
- التوقيت: time_from, time_to, execution_time
- الفئات/النوع: target_group, target_group_id, target_group_other, event_type_id
- التطوع: needs_volunteers, required_volunteers, volunteer_age_range, volunteer_gender, volunteer_tasks_summary
- الحضور والتقييم: expected_attendance, expected_attendance_from, expected_attendance_to, actual_attendance, attendance_rate, attendance_gap, attendance_percentage, audience_satisfaction_percent, evaluation_score, evaluation_reason
- الإعلام والمراسلات: needs_media_coverage, media_coverage, media_coverage_notes, needs_official_correspondence, official_correspondence_reason, official_correspondence_target, official_correspondence_brief, correspondence_status
- الاحتياجات التنفيذية: requires_programs, requires_workshops, requires_communications, execution_needs_payload, execution_needs_followup, post_execution_payload
- سير العمل والحوكمة: status, lifecycle_status, execution_status, plan_stage, plan_version, previous_version_id, executive_review_required
- حالات الموافقات القديمة: relations_officer_approval_status, relations_manager_approval_status, programs_officer_approval_status, programs_manager_approval_status, liaison_approval_status, hq_relations_manager_approval_status, executive_approval_status
- الأرشفة والتحكم: lock_at, is_official, is_archived, archived_year
- الربط الإداري: branch_id, center_id, created_by, evaluation_assigned_user_id, evaluation_assigned_at
- timestamps + softDeletes

### monthly_activity_approvals
- id
- monthly_activity_id
- step / decision / comment
- approved_by / approved_at
- timestamps

### monthly_activity_team
- id
- monthly_activity_id
- member_name / member_role / assigned_tasks
- timestamps

### monthly_activity_supplies
- id
- monthly_activity_id
- item_name / required_qty / available
- provider_type / provider_name
- quantity
- timestamps

### monthly_activity_attachments
- id
- monthly_activity_id
- file_path / uploaded_by
- title
- timestamps

### monthly_activity_sponsors
- id
- monthly_activity_id
- sponsor_name
- timestamps

### monthly_activity_partners
- id
- monthly_activity_id
- partner_name / partner_role
- timestamps

### monthly_activity_change_logs
- id
- monthly_activity_id
- changed_by
- field_name / old_value / new_value
- changed_at
- timestamps

### activity_notes
- id
- activity_id
- user_id
- role / note / coverage_status
- timestamps

### monthly_activity_evaluation_responses
- id
- monthly_activity_id
- evaluation_question_id
- score / notes
- timestamps

### monthly_activity_followups
- id
- monthly_activity_id
- action_item / owner / due_date / status
- timestamps

## 3) جداول دورة الموافقات الديناميكية (Workflow)

### workflows
- id
- name_ar / name_en / code
- module (مثل: agenda, monthly_activities)
- is_active
- timestamps

### workflow_steps
- id
- workflow_id
- step_key
- name_ar / name_en
- step_order / approval_level
- step_type (main/sub)
- role_id / permission_id
- condition_field / condition_value
- is_editable
- timestamps

### workflow_instances
- id
- workflow_id
- entity_type / entity_id
- status
- current_step_id
- edit_request_count
- started_at / completed_at
- timestamps

### workflow_logs
- id
- workflow_instance_id
- workflow_step_id
- actor_id
- action (approved/changes_requested/rejected)
- comment
- acted_at
- timestamps

## 4) سيناريوهات السايكلز (Cycles)

## A) Cycle الأجندة السنوية (من منظور الحالة)
1. إنشاء الحدث السنوي بحالة draft.
2. إدخاله في workflow نشط لوحدة agenda.
3. خلال المراجعة: الحالة قد تصبح submitted أو relations_approved.
4. قرار نهائي:
   - approved => published
   - rejected => rejected
   - changes_requested => changes_requested
5. إذا أصبح published وكان event_type = mandatory و is_active=true:
   - يتم توليد/تحديث monthly_activities تلقائيًا لكل فرع مشارك (participant).

## B) Cycle الأنشطة الشهرية (Lifecycle status)
المسار الرسمي:
- Draft -> Submitted -> Branch Approved -> Khelda Liaison Approved -> Khelda Director Approved -> (Exec Director Approved اختياريًا) -> Scheduled -> Executed -> Evaluated -> Closed

ومسموح الرجوع للخلف في نقاط محددة حسب جدول transitions.

## C) Cycle الموافقات الديناميكي للأنشطة الشهرية (Workflow steps)
بحسب تهيئة الترحيل الأخيرة، المسار المتوقع غالبًا:
1. monthly_relations_officer_submit (sub/editable)
2. monthly_supervisor_review
3. monthly_branch_coordinator_review (شرطي)
4. monthly_relations_manager_review
5. monthly_executive_manager_final_approval (شرطي إذا executive_review_required=1)

مع سيناريوهات القرار بكل خطوة:
- approved: الانتقال للخطوة التالية
- changes_requested: rollback لخطوة قابلة للتحرير + زيادة edit_request_count
- rejected: إنهاء الدورة بالحالة rejected

## 5) علاقة الأجندة السنوية بالأنشطة الشهرية
- الربط عبر agenda_event_id داخل monthly_activities.
- عند نشر حدث سنوي إلزامي، النظام ينشئ خطة شهرية لكل فرع مشارك ويملأ:
  month/day/activity_date/title/proposed_date + أعلام الربط بالأجندة + status/lifecycle_status.
- بعض الحقول تُقفل عند التعديل للأنشطة المرتبطة بالأجندة، خاصة في الحالات الموحدة.
