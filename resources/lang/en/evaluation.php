<?php
return [
    'title' => 'Activity Evaluations', 'forms' => 'Evaluation forms and questions', 'post_execution' => 'Post-execution completion', 'submitted_value' => 'Submitted value', 'corrected_value' => 'Correct value',
    'statuses' => ['pending' => 'Pending verification', 'correct' => 'Correct', 'incorrect' => 'Incorrect', 'evaluated' => 'Evaluated'],
    'visibility' => ['branch_only' => 'Branch only', 'authorized_users' => 'Authorized users'],
    'validation' => ['corrected_required' => 'A corrected value is required when marked incorrect.', 'verification_incomplete' => 'All post-execution values must be verified first.', 'duplicate' => 'This activity has already been evaluated.', 'required_answer' => 'This answer is required.', 'score_range' => 'The score must be within the question range.', 'configuration' => 'The evaluation form configuration is invalid.', 'ineligible' => 'Cancelled or rejected activities cannot be evaluated.', 'integer' => 'The value must be an integer.', 'numeric' => 'The value must be numeric.', 'boolean' => 'The boolean value is invalid.'],
    'messages' => ['verification_saved' => 'Verification decisions saved.', 'submitted' => 'Evaluation submitted and activity status updated.', 'visibility_updated' => 'Evaluation visibility updated.'],
    'dashboard' => ['activities'=>'Total activities','branches'=>'Branches','pending_verification'=>'Pending verification','incorrect'=>'Incorrect data','pending_evaluation'=>'Pending evaluation','evaluated'=>'Evaluated','average'=>'Average score','this_month'=>'This month','latest'=>'Latest evaluations'],
    'notifications' => ['incorrect_title'=>'Incorrect post-execution data','incorrect_message'=>'A correction was recorded for :activity.','completed_title'=>'Activity evaluation completed','completed_message'=>':activity was evaluated at :score/10.'],
];
