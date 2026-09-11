# Ramadan Iftar submission and approval — Phase 1.10 gate

## Outcome

Phase 1.10 remains blocked before production lifecycle routes are introduced.

- **GUIDANCE BLOCKER RESOLVED.** `event_guidance_versions` now provides immutable, published Ramadan guidance versions; create/store requires server-controlled acceptance of the current version and `ramadan_iftars.guidance_version_id` retains the exact accepted version.
- **EXECUTION NEEDS BLOCK SUBMISSION.** The approved sequence requires execution needs to be captured with the rest of the planning details before the aggregate is submitted. Phase 1.2 established that the current Monthly Activities config/JSON representation has no safe canonical mapping to shared execution-need definitions. Ramadan must not copy that legacy storage or invent `subject_execution_needs` mappings in this phase.

Consequently, no submit, approval, decision, or notification endpoint is added. The existing draft-only planning flow remains unchanged and cannot imply that an incomplete plan is submission-ready.

## Existing workflow trace

The workflow foundation is **mostly reusable (Case B)**:

1. `Workflow` selects one active definition per `module`; ordered `WorkflowStep` rows identify roles/permissions, conditional applicability, editability, and main/sub steps.
2. `DynamicWorkflowService::forModel()` creates or finds one `WorkflowInstance` using the workflow ID plus the model's fully-qualified class in `entity_type` and its key in `entity_id`.
3. `currentStep()` skips inapplicable conditional steps. `currentStepForUser()` combines the current step role/permission with assignment scope, while `assertPrerequisites()` verifies prior applicable approvals.
4. Decisions are stored in `WorkflowLog`, including actor, step, action, comment, correction iteration, and timestamp. `WorkflowActionLog` is the generic module/entity audit stream used by the existing controllers.
5. Approval advances to the next applicable step; completion occurs only when no applicable step remains (or through explicit final approval). A changes-requested decision increments the correction iteration and returns to the first editable/applicable step. The generic service also supports rejection, but Ramadan requirements do not independently establish rejection, so it must not be exposed automatically.
6. Eligibility can be resolved from workflow roles/permissions, and notifications can address the eligible users and prior actors. Notification URLs are supplied by the calling module; some title/recipient presentation remains specialized for Agenda and Monthly Activities.
7. The generic instance/log schema already prevents numeric-ID collisions when every query includes both `entity_type` and `entity_id`. A future Ramadan registration must use `App\Modules\Events\Models\RamadanIftar` as `entity_type` and a distinct `ramadan_iftars` workflow module.

The remaining coupling that makes this Case B rather than Case A is important: branch-scoped workflow-step assignment is currently enabled only when the workflow module is `monthly_activities`. A future Ramadan workflow cannot safely reuse the service until that check is minimally generalized and covered by branch and entity-collision tests. Error translation keys and parts of notification presentation also retain Monthly Activity naming, but the persisted workflow identity and audit tables themselves are generic.

## Permissions decision

No dedicated Ramadan permissions are seeded in this blocked slice. Adding `ramadan_iftars.view/create/edit/submit/approve` without a usable submission workflow would create dormant capabilities and require guessing approval-role assignments. Phase 1.9 therefore continues to use its documented temporary create/edit authorization. The prerequisite slice must establish the final Ramadan actor matrix before dedicated permissions and the `ramadan_iftars` workflow are seeded together.

## Lifecycle not activated

Only the existing `draft` planning state is active. No `submitted`, `changes_requested`/`returned`, `approved`, or `rejected` Ramadan transition is introduced. `submitted_at`, `approved_at`, and `closed_at` remain untouched, and `execution_status` remains `planned`.

Guidance acceptance now augments new draft creation only. Submission, approval, execution, monitoring, attendance, plan versioning, and change-request data remain untouched; the only new schema is the versioned guidance source and its restrictive Ramadan Iftar reference.

## Required prerequisite

Before Phase 1.10 can resume:

1. normalize Execution Needs with approved canonical codes and a compatibility mapping, then provide the shared Ramadan subject storage required by the architecture;
2. confirm the Ramadan approval actor matrix, add dedicated permissions and a `ramadan_iftars` workflow definition, and minimally generalize branch-scoped step eligibility;
3. then implement and test atomic submission, filtered-before-pagination queues, current-step decisions, return/resubmission, final approval timestamps, entity collision protection, and server-side edit locking.
