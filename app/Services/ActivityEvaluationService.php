<?php

namespace App\Services;

use App\Models\ActivityEvaluation;
use App\Models\AuditLog;
use App\Models\EvaluationForm;
use App\Models\MonthlyActivity;
use App\Models\PostExecutionVerification;
use App\Models\User;
use App\Support\EvaluationVisibility;
use App\Support\PostExecutionVerificationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivityEvaluationService
{
    public function synchronizeVerificationFields(MonthlyActivity $activity): void
    {
        foreach ($this->flatten((array) $activity->post_execution_payload) as $key => $value) {
            PostExecutionVerification::query()->firstOrCreate(
                ['monthly_activity_id' => $activity->id, 'field_key' => $key],
                ['branch_id' => $activity->branch_id, 'field_label' => str_replace(['.', '_'], ' ', $key), 'value_type' => $this->valueType($value), 'original_value' => ['value' => $value]]
            );
        }
    }

    public function verify(MonthlyActivity $activity, User $user, array $items): void
    {
        DB::transaction(function () use ($activity, $user, $items) {
            $this->synchronizeVerificationFields($activity);
            foreach ($items as $id => $data) {
                $record = $activity->postExecutionVerifications()->lockForUpdate()->findOrFail($id);
                $status = $data['status'];
                if ($status === PostExecutionVerificationStatus::INCORRECT && ! array_key_exists('corrected_value', $data)) {
                    throw ValidationException::withMessages(["items.$id.corrected_value" => __('evaluation.validation.corrected_required')]);
                }
                $corrected = $status === PostExecutionVerificationStatus::CORRECT
                    ? $record->original_value
                    : ['value' => $this->normalizeValue($record->value_type, $data['corrected_value'] ?? null, "items.$id.corrected_value")];
                $old = $record->only(['status', 'corrected_value', 'note', 'verified_by', 'verified_at']);
                $record->update(['status' => $status, 'corrected_value' => $corrected, 'note' => $data['note'] ?? null, 'verified_by' => $user->id, 'verified_at' => now()]);
                AuditLog::create(['user_id' => $user->id, 'action' => 'post_execution_verified', 'module' => 'evaluation', 'entity_type' => PostExecutionVerification::class, 'entity_id' => $record->id, 'old_values' => $old, 'new_values' => $record->fresh()->toArray()]);
                if ($status === PostExecutionVerificationStatus::INCORRECT && $activity->creator) {
                    app(NotificationService::class)->notifyUsers(collect([$activity->creator]), 'post_execution_incorrect', __('evaluation.notifications.incorrect_title'), __('evaluation.notifications.incorrect_message', ['activity' => $activity->title]), route('role.relations.activities.show', $activity), ['activity_id' => $activity->id, 'verification_id' => $record->id]);
                }
            }
        });
    }

    public function submit(MonthlyActivity $activity, EvaluationForm $form, User $user, array $answers, ?string $notes = null): ActivityEvaluation
    {
        return DB::transaction(function () use ($activity, $form, $user, $answers, $notes) {
            abort_if(in_array($activity->status, ['cancelled', 'rejected'], true), 422, __('evaluation.validation.ineligible'));
            $this->synchronizeVerificationFields($activity);
            if ($activity->postExecutionVerifications()->where('status', PostExecutionVerificationStatus::PENDING)->exists() || ! $activity->postExecutionVerifications()->exists()) {
                throw ValidationException::withMessages(['verification' => __('evaluation.validation.verification_incomplete')]);
            }
            if ($activity->activityEvaluation()->exists()) {
                throw ValidationException::withMessages(['activity' => __('evaluation.validation.duplicate')]);
            }

            $questions = $form->questions()->where('is_active', true)->get();
            $weightedPoints = 0.0;
            $weightTotal = 0.0;
            foreach ($questions as $question) {
                $answer = $answers[$question->id] ?? null;
                if ($question->is_required && ! isset($answer['score'])) {
                    throw ValidationException::withMessages(["answers.{$question->id}.score" => __('evaluation.validation.required_answer')]);
                }
                if (! isset($answer['score'])) continue;
                $score = (float) $answer['score'];
                if ($score < (float) $question->minimum_score || $score > (float) $question->maximum_score) {
                    throw ValidationException::withMessages(["answers.{$question->id}.score" => __('evaluation.validation.score_range')]);
                }
                $normalized = (($score - (float) $question->minimum_score) / max(0.01, (float) $question->maximum_score - (float) $question->minimum_score)) * 9 + 1;
                $weightedPoints += $normalized * (float) $question->weight;
                $weightTotal += (float) $question->weight;
            }
            if ($weightTotal <= 0) throw ValidationException::withMessages(['form' => __('evaluation.validation.configuration')]);

            $evaluation = ActivityEvaluation::create(['monthly_activity_id' => $activity->id, 'evaluation_form_id' => $form->id, 'branch_id' => $activity->branch_id, 'evaluated_by' => $user->id, 'weighted_points' => $weightedPoints, 'weight_total' => $weightTotal, 'normalized_score' => round($weightedPoints / $weightTotal, 2), 'visibility' => EvaluationVisibility::BRANCH_ONLY, 'notes' => $notes, 'submitted_at' => now()]);
            foreach ($questions as $question) {
                if (! isset($answers[$question->id]['score'])) continue;
                $score = (float) $answers[$question->id]['score'];
                $normalized = (($score - (float) $question->minimum_score) / max(0.01, (float) $question->maximum_score - (float) $question->minimum_score)) * 9 + 1;
                $evaluation->answers()->create(['evaluation_question_id' => $question->id, 'question_ar' => $question->question_ar ?: $question->question, 'question_en' => $question->question_en ?: $question->question, 'minimum_score' => $question->minimum_score, 'maximum_score' => $question->maximum_score, 'weight' => $question->weight, 'score' => $score, 'weighted_score' => $normalized * (float) $question->weight, 'note' => $answers[$question->id]['note'] ?? null]);
            }
            $activity->update(['status' => 'evaluated', 'lifecycle_status' => 'Evaluated', 'evaluation_score' => $evaluation->normalized_score]);
            AuditLog::create(['user_id' => $user->id, 'action' => 'activity_evaluated', 'module' => 'evaluation', 'entity_type' => MonthlyActivity::class, 'entity_id' => $activity->id, 'old_values' => ['status' => $activity->getOriginal('status')], 'new_values' => ['status' => 'evaluated', 'score' => $evaluation->normalized_score]]);
            $relationsUsers = User::role(['relations_officer', 'relations_manager'])->where(function ($query) use ($activity) {
                $query->where('branch_id', $activity->branch_id)->orWhereHas('assignedBranches', fn ($branchQuery) => $branchQuery->where('branches.id', $activity->branch_id));
            })->get();
            app(NotificationService::class)->notifyUsers($relationsUsers, 'activity_evaluated', __('evaluation.notifications.completed_title'), __('evaluation.notifications.completed_message', ['activity' => $activity->title, 'score' => $evaluation->normalized_score]), route('evaluations.show', $evaluation), ['activity_id' => $activity->id, 'evaluation_id' => $evaluation->id]);
            return $evaluation->load('answers');
        });
    }

    private function flatten(array $payload, string $prefix = ''): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            $path = ltrim($prefix.'.'.$key, '.');
            if (is_array($value) && $value !== []) $result += $this->flatten($value, $path); else $result[$path] = $value;
        }
        return $result;
    }

    private function valueType($value): string
    {
        if (is_bool($value)) return 'boolean';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'decimal';
        return 'text';
    }

    private function normalizeValue(string $type, $value, string $key)
    {
        if ($type === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) throw ValidationException::withMessages([$key => __('evaluation.validation.integer')]);
        if ($type === 'decimal' && ! is_numeric($value)) throw ValidationException::withMessages([$key => __('evaluation.validation.numeric')]);
        if ($type === 'boolean' && ! in_array($value, [true, false, 0, 1, '0', '1'], true)) throw ValidationException::withMessages([$key => __('evaluation.validation.boolean')]);
        return $type === 'integer' ? (int) $value : ($type === 'decimal' ? (float) $value : ($type === 'boolean' ? (bool) $value : $value));
    }
}
