# Consolidated Operations Cycles, Roles, Forms, Permissions for

## Document set and what each artefact contributes

The materials you shared describe an operations system made of multiple *cycles* (workflows) and *forms* (data capture templates). Two artefacts define the event-management “core” most explicitly: the event-system specification document and the branch-relations SOP. fileciteturn0file1 fileciteturn0file0

The rest of the uploaded Excel templates and PNG flowcharts extend the scope beyond events into revenues, maintenance, and movement/transport (they are consistent with the presence of roles like `finance_officer`, `maintenance_officer`, `transport_officer`, and movement roles that are explicitly listed in the system roles). fileciteturn0file1

Across the set, there are clear signs of versioning and duplication (multiple “monthly plan” variants; repeated columns for team/supplies; mixed naming of departments and event types). The most important outcome of consolidation is to identify the *primary cycles* and then anchor each one to a single “source-of-truth” form design, with role-based permissions that match real operations.

## Primary cycle map and how cycles connect

A consolidated model of what you currently have, based on the documents and templates, is the following connected set of cycles:

**Events lifecycle (end-to-end):**
- Annual agenda creation (HQ planning) → Branch monthly plan → Event execution → Post-event media + evaluation/monitoring. fileciteturn0file1 fileciteturn0file0

**Supporting operational lifecycles (parallel modules):**
- Maintenance lifecycle (branch logs issue → maintenance/IT follow-up → branch closes complaint; viewable organisation-wide per the flow).  
- Revenue lifecycle (bookings, Zaha Time, cash support, other revenues; discount approval path shown in the flow).  
- Movement/transport lifecycle (create “trip” + optionally multiple “rounds”; non-movement users are view-only per flowchart).

In the event module, the specification is explicit that the system is intended to link **annual agenda**, **monthly plans**, **execution**, and **follow-up/evaluation**, and coordinate between branches and HQ in entity["place","Khalda","amman, jordan"]. fileciteturn0file1

## Role catalogue and permission model

### Business roles and system roles

The event-system document lists an RBAC set that already anticipates multiple modules (events + operations), including relations, programmes, finance, maintenance, transport, movement, communications, follow-up, workshops, reports, and staff. fileciteturn0file1

Below is a consolidated dictionary that maps the **business role wording** used in your forms/flows (Arabic) to the **system role naming convention** used in the specification.

| Business role (as appears in files/flows) | Likely system role (from spec) | Scope | Core permission intention |
|---|---|---|---|
| Super Admin | `super_admin` | HQ | Full configuration (users, lookups, global visibility). fileciteturn0file1 |
| مسؤول العلاقات في الفرع | `relations_officer` | Branch | Create/edit event records; prepare plans; coordinate execution. fileciteturn0file1 |
| رئيس وحدة العلاقات في الفرع | `relations_manager` | Branch | Approve/reject or request changes at branch stage. fileciteturn0file1 |
| ضابط ارتباط العلاقات في خلدا | `relations_officer` (HQ account) | HQ | Review branch submissions; enforce standardisation; follow-up. fileciteturn0file1 |
| مدير وحدة العلاقات في خلدا | `relations_manager` (HQ account) | HQ | Final relations approval; governance of agenda/standards. fileciteturn0file1 |
| ضابط ارتباط البرامج | `programs_officer` | HQ (and/or programme layer) | Approve programme-related events when flagged. fileciteturn0file1 |
| مدير وحدة البرامج | `programs_manager` | HQ | Final programme approval when required. fileciteturn0file1 |
| مدير المركز التنفيذي | `executive_manager` | HQ | Optional final approval for events; also shown as reviewer for discounts in revenue flow. fileciteturn0file1 |
| رئيس قسم الاتصال | `communication_head` | HQ | Media coverage workflow; select/approve published media outputs. fileciteturn0file1 |
| لجنة المشاغل / سكرتير المشاغل | `workshops_secretary` | HQ | Add notes on manufacturing/gifts; spec indicates notes (not approvals) in current design. fileciteturn0file1 |
| المتابعة والتقييم | `followup_officer` | HQ | Post-event evaluation + monitoring; manages evaluation questions via lookups. fileciteturn0file1 |
| المالية / محاسب / رئيس وحدة | `finance_officer` (plus a “unit head” concept) | Branch/HQ | Record revenues; manage discount workflow; produce revenue reporting. fileciteturn0file1 |
| الصيانة | `maintenance_officer` | HQ/Branch service | Receive/track maintenance; update resolution. fileciteturn0file1 |
| النقل / السائقين | `transport_officer` | Branch service | Manage driver schedules and transport requirements. fileciteturn0file1 |
| قسم الحركة (إنشاء/تعديل) | `movement_manager`, `movement_editor` | Service team | Create trips & rounds; edit operational movement data. fileciteturn0file1 |
| عرض فقط | `movement_viewer`, `reports_viewer`, `staff` | Org-wide | View-only access (depending on module). fileciteturn0file1 |

### Cross-cutting permission rules that appear repeatedly

Several “permission patterns” repeat across your artefacts:

- **Everyone-on-system can view, but editing is restricted**: Key (“المفتاح”) sheets in Excel templates frequently say “view for all employees who use the system” while restricting editing to the role responsible for that field (e.g., unit head, branch head, liaison). (This pattern is explicit in multiple key sheets and flowcharts; the same logic should become a formal RBAC policy rather than free-text in a key sheet.)
- **Department ownership gates which section gets filled**: The monthly event plan key explicitly signals that workshops/lectures should be approved by programmes, while bazaars/videos are approved by relations, implying the form itself is split into “relations section” and “programmes section” and only the relevant one should be editable per event type.
- **“Notes-only” stakeholders**: The event spec explicitly frames workshops and communications as stakeholders who see the event and add notes/attachments rather than participating in the approval chain (unless you decide to change this policy later). fileciteturn0file1

## Events and community relations cycle

### What this cycle is for

The event-management system is designed to digitise, standardise, and track branch events by tying together three pillars: **annual agenda → monthly plan → execution & evaluation**, spanning both branches and HQ (Khalda). fileciteturn0file1

The branch relations SOP complements this by detailing *how events are executed operationally*: invitations, venue preparation, logistics, reception protocol, and performance indicators for compliance and quality. fileciteturn0file0

### Main sub-cycles, roles, approvals

**Annual agenda creation (HQ-led)**  
Purpose: define the yearly event list and high-level metadata (date/day/type; mandatory vs optional; unified vs non-unified). The spec states this is prepared at the end of the year for the next year by the HQ relations team. fileciteturn0file1  
Key roles:
- HQ relations (create draft agenda, standardise taxonomy). fileciteturn0file1  
- Departments/centres flag participation (programme, communications, workshops, branches) — this is strongly illustrated in the uploaded agenda flowchart and also reflected in the agenda spreadsheet structure (columns per branch and per department/participation), even if not all fields are consistently populated.
- Governance approvals: the spec includes multi-stage approvals downstream at event level; the agenda flowchart you shared also shows a “programme manager approval” then “executive approval” before publishing.

**Branch monthly planning (Branch-led, reviewed by HQ)**  
Purpose: every branch prepares a plan at the end of each month for the next month, including agenda events plus optionally branch-proposed events. fileciteturn0file1  
Key roles:
- Branch relations officer/manager create the plan (the spec assigns creation to branch relations officer, then branch relations unit head approves). fileciteturn0file1  
- HQ liaison and HQ relations manager review/approve. fileciteturn0file1  
- If programme involvement is required, programme officer/manager review; this conditional branch is explicitly described in the spec. fileciteturn0file1  
Important version conflict you need to resolve: the monthly plan key sheet in Excel sometimes states editing is by “branch head” (رئيس الفرع), whereas the system spec assigns creation to “relations officer (branch)” (مسؤول العلاقات). fileciteturn0file1  
Consolidation recommendation: keep **one** of these as the *true creator* role, then the other becomes a reviewer/approver—not both as primary editors—otherwise audit trails and responsibility KPIs become unreliable.

**Execution protocol (Branch-led, with support from programmes and volunteers)**  
The relations SOP defines concrete operational execution steps: invitation types, venue setup, logistical needs (chairs/tables/sound system/printing/gifts/refreshments), reception protocol, and on-site management. fileciteturn0file0  
Key roles:
- Branch relations team coordinates invitations, reception, seating, and sponsor/guest handling. fileciteturn0file0  
- Branch programmes team owns programme activities during events (activities for kids, exhibitions, etc.) with support from relations and volunteers. fileciteturn0file0

**Post-event follow-up and evaluation (HQ follow-up, branch inputs as needed)**  
The spec states evaluation happens after execution, with evaluation questions managed as lookup data by an admin and used by follow-up/evaluation staff. fileciteturn0file1  
It also identifies current gaps: dynamic evaluation is incomplete; attendance/volunteer management is incomplete; and some fields remain free text instead of structured lookups. fileciteturn0file1

### Core forms and “what each form must represent”

A clean consolidation yields four “canonical” forms (even if your current Excel combines several into one sheet):

- **Annual Agenda form** (اجندة زها): the *template of the year* (events as templates, not instances). Owned by HQ relations. fileciteturn0file1  
- **Monthly Plan form** (خطة الفعاليات الشهرية): the *branch commitment* (when/where/how the branch will execute the agenda items, plus branch-proposed events). fileciteturn0file1  
- **Execution Report form**: what was actually executed (actual date/time, actual attendance, what changed, attachments, media). The spec explicitly distinguishes pre-event and post-event data such as expected vs actual attendance and uploading media outputs. fileciteturn0file1  
- **Evaluation form**: follow-up KPIs + qualitative evaluation. The SOP includes operational KPIs (plan adherence, schedule adherence, compliance with instruction) and quantitative KPIs (number of events, beneficiaries, coverage of target segments, satisfaction). fileciteturn0file0

### Approval chain that should be implemented uniformly

The spec defines the event approval chain as:

Branch relations officer (data entry) → Branch relations unit head (approval) → HQ relations liaison → HQ relations manager → optional executive manager. fileciteturn0file1

It also explicitly requires tracking “how many times each stage requested edits” and “what edits were requested” (auditability and accountability requirement). fileciteturn0file1

## Maintenance cycle

### What the maintenance cycle is for

Your maintenance artefacts define a standard “maintenance complaint/case” lifecycle: a branch logs the issue with initial classification and priority; then the responsible service department (general maintenance or IT/computers) follows up with scheduling, team assignment, resources, cost estimation, and root-cause analysis; finally, the branch closes the complaint after confirmation of completion.

### Roles, tasks, and permissions implied by your templates

From the maintenance flow:
- **Branch head / branch responsible** logs the case: date, maintenance category, detailed description, priority, and an initial cost estimate.
- **Maintenance department** (general maintenance) updates execution details and marks completion.
- **Computer/IT department** does the same for computer-related maintenance.
- **Branch head** closes the complaint (اغلاق الشكوى).
- **All employees are view-only** for the full record (explicitly stated at the end of the flow).  

Your maintenance Excel template reinforces role-split sections: it contains separate column blocks labelled “رئيس الفرع”, “رئيس قسم الصيانة”, and “رئيس قسم الحاسوب”, each with their own fields such as maintenance duration, maintenance team, resources (internal/external), support entity, cost estimate, problem analysis, and notes.

### Canonical forms to keep after consolidation

For long-term automation, maintenance should be represented as:
- **Maintenance Case form** (single master record): case metadata (opened date, category, description, priority, status, closed date).
- **Maintenance Worklog entries** (repeatable child records): department updates (from/to time, team members, resources, costs, root cause, notes).
- **Closure confirmation** (branch sign-off): closure reason and confirmation notes.

This structure is strongly preferable to duplicating the same fields into separate role-specific columns, because it supports:
- multiple updates over time,
- parallel work (e.g., maintenance + IT involvement),
- accurate audit trail.

## Revenue cycle

### What the revenue cycle covers

The revenue flowchart and workbook show multiple revenue streams under a single “record revenue” entry point:
- bookings (individuals),
- bookings (supporting entities / organisations),
- “cash support” (donations/support payments),
- “other” revenues.

The same cycle includes a **discount decision** path: if a discount is applied, it is routed for review; if not approved, the system removes the discount and restores the original price before saving.

### Roles, approvals, and access

Key governance points in your flow:
- Booking records are entered by the employee who processed the booking (often accounting/finance), with fields like receipt date, actual booking date, time window, facility, payment method, discount value, and discount justification.
- If “discount exists”, a review step occurs where the **executive manager** reviews discount and revenues and either approves or rejects; rejection triggers removal of discount and restoration of original price before saving.
- Viewing rights are called out explicitly: *executive manager* and *unit managers* have viewing rights across records (“صلاحيات المشاهدة”).  

Your revenue “key” sheets also encode the pattern that most fields are visible to “all employees using the system”, even when editing is restricted (for example: cash support fields edited by “unit head”; booking fields edited by the entering employee).

### Canonical forms to keep after consolidation

A clean model is to maintain:
- **Revenue Record** (master): revenue type, date, amount, branch, related entity (person/organisation), payment method, receipt reference, notes.
- **Revenue Type extensions** (child per type):
  - booking details (facility, time window, customer, discount),
  - Zaha Time details (number of children, booking entity type, liaison, phone),
  - donation/support details (support entity, objective, related event if “for event”),
  - other (free category + description).
- **Discount Approval Request** (if discount applied): approver, decision, timestamp, reason.

This removes the need for separate sheets per revenue type while keeping type-specific validation.

## Movement and transport cycle

### What the movement cycle is for

Your movement flowchart defines a “trip” record with optional multiple “rounds” (جولات). The movement department creates the trip and fills basic trip data (day/date/driver/vehicle). If rounds are added, each round specifies destination, accompanying team, departure time, return time, and notes; then the trip and its rounds are saved.

The flow explicitly states that non-movement users are **view-only** (“عرض البيانات فقط”), both for the “relevant department” and for “regular users”.

### Forms involved

Two artefacts represent this module:
- **Movement/Trip form** (flowchart-defined): trip header + repeatable rounds.
- **Driver schedule template** (Excel): month sheet with up to three trips per day/driver (each trip containing vehicle, destination, team, depart time, return time) plus notes.

### Consolidated role permissions

A coherent RBAC for this module (aligned with roles listed in the system spec) is:
- `movement_manager` / `movement_editor`: create & edit trips and rounds.
- `movement_viewer` and general `staff`: view-only.
- `transport_officer`: manage driver availability and schedules (may overlap with movement depending on how you split responsibilities).

## Duplication hotspots and the “final” consolidation decisions

### Conflicts you must decide (otherwise automation will keep breaking)

**Event creator role conflict**  
- Spec: branch relations officer creates events. fileciteturn0file1  
- Some planning templates: branch head is the editor for most event fields.  
You need one rule: either (A) branch relations officer is creator and branch head approves, or (B) branch head is creator and relations officer is editor-only for a subset. The system cannot be reliable if “everyone edits everything”.

**Multiple incompatible “monthly plan” schemas**  
You have at least two variants with different column counts and repeated blocks. Consolidate to one schema and migrate data.

### Structural improvements that are explicitly demanded by your own spec

The event-system document itself identifies critical gaps and necessary upgrades:
- move free-text fields to lookup tables for stable reporting,
- implement complete attendance/volunteers capture,
- implement dynamic evaluation questions and responses,
- improve approval UX: show edit-request counts and edit details per stage. fileciteturn0file1

### The minimum “final set” of cycles to keep

After consolidation, the smallest coherent set that covers your artefacts is:

- **Events**: annual agenda, monthly plan, execution report, evaluation.
- **Maintenance**: case + worklogs + closure.
- **Revenue**: unified revenue record + discount approval.
- **Movement/Transport**: trip + rounds + driver schedule.

Every additional cycle you add later (e.g., inventory, procurement, HR) should follow the same pattern: one master record, repeatable child entries where necessary, and a single approval policy with a clear audit trail.

### Why you currently feel “lost” (root cause from the files)

This is not a capability issue; it is a *model-consistency* issue. Your artefacts describe the same domains multiple times with different assumptions (who edits, where approval happens, and whether fields are structured vs free text). Until you pick a single “truth” per cycle—creator, approver chain, and canonical form—automation will always feel like you’re chasing a moving target.