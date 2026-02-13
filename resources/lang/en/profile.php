<?php

return [

    // =========================
    // Page Meta / Titles
    // =========================
    'title' => 'Volunteer Profile',

    // =========================
    // Progress Bar (Profile Completion)
    // =========================
    'profile_completion' => 'Profile Completion',
    'profile_completion_hint' => 'Complete the required fields to increase your profile progress.',
    'profile_completion_cta' => 'Review and update your details',
    'profile_completion_complete' => 'Your profile is complete',
    'profile_completion_accessible' => 'Profile completion is :percent% — tap to review your details.',

    'highlights' => [
        'email_status'   => 'Email status',
        'email_verified' => 'Verified email',
        'email_pending'  => 'Awaiting verification',
        'member_since'   => 'Member since',
        'last_update'    => 'Last update',
    ],

    'alerts' => [
        'incomplete_profile' => 'Your profile is almost ready! Complete the remaining sections to boost your chances of being selected.',
        'missing_availability' => 'Add your weekly availability so coordinators can match you with the right opportunities.',
    ],

    'availability' => [
        'title' => 'Weekly availability',
        'day' => 'Day',
        'window' => 'Time window',
        'notes' => 'Notes',
    ],

    // =========================
    // Section Headings
    // =========================
    'section_personal_info' => 'Personal Information',

    // =========================
    // Personal Information: Names (Arabic & English)
    // =========================
    'first_name_ar' => 'First Name (Arabic)',
    'middle_name_ar' => 'Middle Name (Arabic)',
    'last_name_ar'  => 'Last Name (Arabic)',
    'first_name_en' => 'First Name (English)',
    'middle_name_en' => 'Middle Name (English)',
    'last_name_en'  => 'Last Name (English)',
    'certificate_name_hint' => 'Your full three-part name will appear on participation certificates.',

    // =========================
    // Personal Information: Demographics
    // =========================
    'date_of_birth'   => 'Date of Birth',
    'gender'          => 'Gender',
    'education_level' => 'Education Level',
    'nationality'     => 'Nationality',
    'marital_status'  => 'Marital Status',

    // =========================
    // Select / Placeholder Text
    // =========================
    'select_placeholder' => '-- Select --',

    // =========================
    // Marital Status Options
    // =========================
    'marital_status_option_single'   => 'Single',
    'marital_status_option_married'  => 'Married',
    'marital_status_option_divorced' => 'Divorced',
    'marital_status_option_widowed'  => 'Widowed',

    // =========================
    // Profile Image (Upload / Hints / A11y)
    // =========================
    'profile_photo'        => 'Profile Photo',
    'profile_photo_hint'   => 'Upload a clear headshot (JPG or PNG, max 2 MB).',
    'profile_photo_empty'  => 'No photo uploaded yet.',
    'change'               => 'Change',
    'profile_photo_alt'    => 'Profile photo of :name',

    // =========================
    // Guardian / Minor (Under 18)
    // =========================
    'is_minor'          => 'I am under 18',
    'guardian_section'  => 'Guardian Approval',
    'guardian_name'     => 'Guardian Name',
    'relation'          => 'Relation',
    'guardian_phone'    => 'Guardian Phone',
    'guardian_approved' => 'I confirm that the guardian has approved',

    // =========================
    // Employment / Contact (General Fields Used Elsewhere)
    // =========================
    'employer_name'   => 'Employer Name',
    'employer_type'   => 'Employer Type',
    'primary_phone'   => 'Primary Phone',
    'secondary_phone' => 'Secondary Phone',
    'health_status'   => 'Health Status',
    'skills_summary'  => 'Skills & Experience',

    // =========================
    // Common Buttons / Actions
    // =========================
    'save' => 'Save',
    'autosave' => [
        'idle' => 'Changes are saved automatically.',
        'saving' => 'Saving changes…',
        'saved' => 'All changes saved.',
        'error' => 'Could not save automatically. Please review the highlighted fields.',
        'last_saved' => 'Last saved: :time',
    ],
    'password' => [
        'section' => 'Password',
        'hint' => 'Update your password using your current password.',
        'submit_notice' => 'Password changes require pressing Save.',
        'updated' => 'Your password has been updated successfully.',
        'current' => 'Current password',
        'new' => 'New password',
        'confirm' => 'Confirm new password',
    ],
    'contact_email' => 'Email address',

    // =========================
    // Volunteer History / Experience / Courses
    // =========================
    'volunteer_histories'   => 'Volunteer Histories',
    'activity_placeholder'  => 'Activity',
    'host_placeholder'      => 'Host',
    'date_placeholder'      => 'Date',
    'hours_placeholder'     => 'Hours',

    'volunteer_experiences' => 'Work Experiences',
    'place_placeholder'     => 'Place',
    'role_placeholder'      => 'Role',

    'volunteer_courses'     => 'Volunteer Courses',
    'course_placeholder'    => 'Course',
    'organization_placeholder' => 'Organization',
    'year_placeholder'      => 'Year',

    'social_connections' => [
        'kicker' => 'Sharing & outreach',
        'title' => 'Link your social accounts',
        'description' => 'Connect LinkedIn or Facebook so you can quickly share certificates and new opportunities.',
        'connected' => 'Connected',
        'not_connected' => 'Not connected',
        'connected_no_email' => 'Connected account',
        'connect' => 'Connect',
        'disconnect' => 'Disconnect',
        'quick_share_hint' => 'Link to share certificates and opportunities with one tap.',
    ],


    // =========================
    // Section: Contact & Residence
    // =========================
    'section_contact_residence' => 'Contact & Residence',
    'governorate'   => 'Governorate',
    'city_town'     => 'City / Town',
    'residence_area'=> 'Neighborhood / Residence Area',
    'referral_question' => 'How did you hear about our platform?',
    'referral_liaison_label' => 'Branch liaison',
    'referral_liaison_placeholder' => 'Select a liaison officer',
    'referral_supervisor_label' => 'Branch supervisor',
    'referral_supervisor_placeholder' => 'Select a branch supervisor',
    'referral_ambassador_label' => 'Promotion ambassador / partner',
    'referral_ambassador_placeholder' => 'Select a promotion ambassador / partner',
    'referral_other_label' => 'Where did you hear about the platform?',
    'referral_other_placeholder' => 'Share the source',
    'referral_contact_required' => 'Please select or enter the referral source.',
    'nearest_branch_modal' => [
        'title' => 'Please select your nearest Zaha center',
        'message' => 'You must select your nearest Zaha center in your profile before continuing to use the platform.',
        'action' => 'Select now',
    ],
    'completion_reminder_modal' => [
        'title' => 'A small step that boosts your volunteer impact 🌟',
        'message' => 'Your profile is currently only :percent% complete. Every detail you add helps us match better opportunities and highlight your strengths. Take a minute to complete it whenever you can 💚',
        'action' => 'Complete profile now',
        'later' => 'Later',
    ],
    'referral' => [
        'options' => [
            'liaison' => 'Liaison officer',
            'supervisor' => 'Branch supervisor',
            'ambassador' => 'Promotion ambassador / partner',
            'social_media' => 'Social media',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'tiktok' => 'TikTok',
            'x' => 'X (Twitter)',
            'youtube' => 'YouTube',
            'snapchat' => 'Snapchat',
            'other' => 'Other',
            'tv' => 'Television',
            'family' => 'Family or relatives',
            'center_visit' => 'A visit to the center',
            'zaha_staff' => 'Through someone at Zaha Center',
        ],
    ],

    // =========================
    // Section: Work or Study Details
    // =========================
    'section_work_study'      => 'Work or Study Details',
    'current_employment_status' => 'Current employment or study status',
    'employer_institution_name' => 'Employer / Institution Name',
    'zaha_branch'             => 'Zaha Branch',
    'zaha_employment_type'    => 'Employment type at Zaha',
    'zaha_employment_option_amanah_employee' => 'Amanah employee',
    'zaha_employment_option_service_contract' => 'Service contract',
    'school_name'             => 'School / University name',
    'school_grade_year'       => 'School Grade / Year',

    // Employer Type Options
    'employer_type_option_government' => 'Government',
    'employer_type_option_zaha_center'=> 'Zaha Center',
    'employer_type_option_private'    => 'Private Sector',
    'employer_type_option_student'    => 'Student',
    'employer_type_option_other'      => 'Other',

    // =========================
    // Section: Health & Accessibility
    // =========================
    'section_health_accessibility' => 'Health & Accessibility',
    'section_health'               => 'Health details',
    'section_logistics'            => 'Transportation & logistics',
    'section_logistics_hint'       => 'Share how you plan to arrive so we can arrange support when needed.',
    'section_accessibility'        => 'Accessibility & photography',
    'accessibility_question'       => 'Do you need any accessibility arrangements to participate comfortably?',
    'accessibility_hint'           => 'Tell us what helps you thrive — mobility, hearing, vision or neurodiversity support.',
    'accessibility_categories_hint'=> 'Examples: mobility, hearing, vision support, or autism-friendly arrangements.',
    'accessibility_details_label'  => 'Describe the environment or tools that support you best',
    'accessibility_details_hint'   => 'Share details like ramps, sign language, visual aids, quiet rooms, or other supports.',
    'photo_consent_question'       => 'Do you agree to being photographed or featured on Zaha’s channels?',
    'photo_consent_hint'           => 'We may capture event photos or short clips for Zaha’s social media. Tell us your preference.',
    'health_condition_label'       => 'Health or wellbeing considerations',
    'health_condition_hint'        => 'Select any options that apply so our team can support you appropriately.',
    'other_health_notes'           => 'Other health notes',
    'health_notes_label'           => 'Additional notes for our team',
    'needs_transportation'         => 'I need transportation support',
    'transport_support_option'     => 'I need transportation support',
    'has_personal_vehicle'         => 'I have access to a personal vehicle',
    'yes'                          => 'Yes',
    'no'                           => 'No',

    // =========================
    // Section: Skills & Interests
    // =========================
    'section_skills_interests' => 'Skills & Interests',
    'other_skills'             => 'Other skills',
    'describe_strengths'       => 'Describe your strengths',
    'hobbies_interests'        => 'Hobbies and interests',

    // =========================
    // Section: Volunteering Preferences
    // =========================
    'section_volunteering_prefs' => 'Volunteering Preferences',
    'availability_pref_label'    => 'When can you usually volunteer?',
    'availability_pref_hint'     => 'Choose the periods that match your schedule. You can add extra notes if needed.',
    'availability_notes'         => 'Tell us more about your availability',
    'availability_intro'         => 'Preferred volunteering times',
    'preferred_volunteering_time_other' => 'Other preferred times',
    'volunteering_time_notes'    => 'Additional availability notes',
    'availability_validation_select_slot' => 'Please choose at least one availability slot or add a note about your availability.',
    'availability_validation_time_required' => 'Please add both a start and end time for custom availability slots.',
    'availability_validation_time_order' => 'The end time must be after the start time.',
    'availability_validation_date_order' => 'The end date must be after or equal to the start date.',
    'prev_volunteering_q'        => 'Have you volunteered with other entities before?',
    'prev_volunteering_where'    => 'Where did you volunteer?',
    'prev_volunteering_field'    => 'In which field?',
    'prev_volunteering_duration' => 'What was the duration?',
    'zaha_beneficiary_q'         => 'Are you benefiting from any Zaha services or programs?',
    'zaha_service_name'          => 'Service name',
    'zaha_service_branch'        => 'Branch',
    'anything_else'              => 'Anything else we should know?',
    'section_additional_notes'   => 'Additional notes',
    'green_wheelz' => [
        'section_title' => 'Green Wheelz roles',
        'section_hint' => 'Share how you contribute to Green Wheelz so we can match you with the right tasks.',
        'roles_title' => 'Roles',
        'roles' => [
            'supply' => 'Supply ambassador (covers / containers)',
            'support' => 'Support ambassador',
            'transport' => 'Transport ambassador',
            'promotion' => 'Promotion ambassador',
        ],
        'supply_title' => 'Supply ambassador details',
        'supply_source' => 'Supply source',
        'select_source' => 'Select source',
        'supply_sources' => [
            'individual' => 'Individual',
            'institution' => 'Institution',
            'personal' => 'Personal',
            'initiative' => 'Initiative',
        ],
        'supply_date' => 'Supply date',
        'supply_notes' => 'Notes',
        'supply_institution' => 'Institution',
        'select_institution' => 'Select institution',
        'supply_initiative' => 'Initiative',
        'select_initiative' => 'Select initiative',
        'transport_title' => 'Transport ambassador details',
        'transport_method' => 'Transport method',
        'transport_methods' => [
            'self_delivery' => 'Self-delivery to collection point',
            'needs_support' => 'Needs transport assistance',
        ],
        'transport_collection_point' => 'Nearest collection point',
        'transport_can_support_entities' => 'Can you transport caps and bottles from other entities?',
        'transport_entity_name' => 'Entity name',
        'transport_notes' => 'Notes',
        'support_title' => 'Support details',
        'support_ambassador_title' => 'Support Ambassador Details',
        'support_type' => 'Support type',
        'support_types' => [
            'financial' => 'Financial support',
            'in_kind' => 'In-kind support',
        ],
        'support_date' => 'Date',
        'support_amount' => 'Financial amount',
        'support_description' => 'Support notes',
        'support_delivery_entity' => 'Receiving entity',
        'support_attachment' => 'Attachment',
        'support_attachment_view' => 'View attachment',
        'promotion_title' => 'Promotion ambassador details',
        'promotion_date' => 'Promotion date',
        'promotion_notes' => 'Notes',
        'referrer_title' => 'How did you hear about Green Wheelz?',
        'referrer_hint' => 'Help us understand how you discovered the initiative.',
        'referrer_type' => 'Referral source',
        'referrer_types' => [
            'none' => 'No referral',
            'zaha_center' => 'Through a Zaha Center',
            'ambassador' => 'Through a Green Wheelz ambassador',
            'media' => 'Media / social / partner',
        ],
        'referrer_role' => 'Zaha contact type',
        'referrer_roles' => [
            'liaison' => 'Liaison officer',
            'supervisor' => 'Branch supervisor',
        ],
        'referrer_user' => 'Zaha Center contact',
        'referrer_ambassador' => 'Promotion ambassador',
        'referrer_description' => 'Referral details',
        'referrer_description_placeholder' => 'e.g., Instagram ad, partner event, TV segment',
        'referrer_description_hint' => 'Optional details help us track outreach.',
        'select_referrer_user' => 'Search for a Zaha team member',
        'select_referrer_liaison' => 'Search for a liaison officer',
        'select_referrer_supervisor' => 'Search for a branch supervisor',
        'select_referrer_ambassador' => 'Search for a promotion ambassador',
        'search_empty' => 'No results found.',
        'summary' => [
            'in_kind' => 'Total actual (exact) quantities',
            'estimated' => 'Total estimated quantities',
            'kg' => 'kg',
        ],
        'supply_source_required' => 'Please choose the supply source.',
        'supply_institution_required' => 'Please select the institution.',
        'supply_initiative_required' => 'Please select the initiative.',
        'supply_date_required' => 'Please choose the supply date.',
        'transport_method_required' => 'Please select the transport method.',
        'transport_collection_point_required' => 'Please select the nearest collection point.',
        'transport_entity_name_required' => 'Please specify the entity name.',
        'promotion_type_required' => 'Please choose the promotion type.',
        'promotion_date_required' => 'Please choose the promotion date.',
        'promotion_platform_required' => 'Please enter the platform name.',
        'promotion_partner_required' => 'Please enter the partner name.',
        'support_date_required' => 'Please provide the support date.',
        'support_amount_required' => 'Please enter the financial amount.',
        'support_description_required' => 'Please add support notes.',
        'support_delivery_required' => 'Please specify the receiving entity.',
        'support_attachment_required' => 'Please attach a supporting document.',
        'referrer_role_required' => 'Please select the Zaha contact type.',
        'referrer_user_required' => 'Please select the Zaha Center contact.',
        'referrer_ambassador_required' => 'Please select the promotion ambassador.',
    ],

    'availability' => [
        'days' => [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
        ],
        'slots' => [
            'morning' => [
                'label' => 'Morning',
                'range' => '09:00 – 12:00',
            ],
            'midday' => [
                'label' => 'Midday',
                'range' => '12:00 – 14:00',
            ],
            'afternoon' => [
                'label' => 'Afternoon',
                'range' => '14:00 – 17:00',
            ],
            'other' => [
                'label' => 'Other / custom',
                'range' => null,
            ],
        ],
    ],

    // Common Yes/No (if not already added)
    'yes' => 'Yes',
    'no'  => 'No',

    // =========================
    // Volunteer Histories (fields & actions)
    // =========================
    'location'            => 'Location',
    'sector'              => 'Sector',
    'duration'            => 'Duration',
    'notes'               => 'Notes',
    'delete'              => 'Delete',
    'no_history_recorded' => 'No history recorded.',

    // Actions / Common
    'add' => 'Add',

    // =========================
    // Volunteer Experiences (fields)
    // =========================
    'exp_title'   => 'Title',
    'organization'=> 'Organization',
    'paid_role'   => 'Paid Role',
    'started_at'  => 'Start Date',
    'ended_at'    => 'End Date',
    'description' => 'Description',

    // =========================
    // Volunteer Courses (fields)
    // =========================
    'provider'   => 'Provider',
    'zaha_course'=> 'Zaha Course',
    'issued_at'  => 'Issued on',

    // =========================
    // Organization Profile (Org-level fields)
    // =========================
    'org_title'         => 'Organization Profile',
    'org_name'          => 'Name',
    'org_logo'          => 'Organization logo',
    'org_logo_hint'     => 'Upload a clear, preferably square logo to feature on opportunity pages and reports.',
    'org_category'      => 'Category',
    'org_contact_phone' => 'Contact Phone',
    'org_city'          => 'City',
    'org_address'       => 'Address',
    'nearest_zaha_branch' => 'What is the nearest Zaha Center to you?',
    'registered_via_zaha_center' => 'Were you registered through the center?',
    'registered_via_zaha_center_options' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],
    'receive_opportunity_notifications' => 'Receive nearby opportunity notifications',
    'receive_opportunity_notifications_hint' => 'When enabled, you will receive notifications for published opportunities in your nearest branch or governorate, in addition to opportunities you are already affiliated with.',
    'org_profile'       => 'Profile',

    'branches'       => 'Branches',
    'branch_name'    => 'Branch Name',
    'branch_city'    => 'City',
    'branch_address' => 'Address',
    'branch_nearest_zaha' => 'Nearest Zaha branch to this location',
    'add_branch'     => 'Add Branch',
    'no_branches'    => 'No branches added yet.',

    // =========================
    // Opportunity evaluations
    // =========================
    'opportunity_evaluations' => 'Opportunity feedback',
    'opportunity'             => 'Opportunity',
    'score'                   => 'Score',
    'reviewer'                => 'Reviewer',
    'comment'                 => 'Comment',
    'date'                    => 'Date',
    'visibility'              => 'Status',
    'visibility_visible'      => 'Visible to you',
    'visibility_hidden'       => 'Hidden',
    'no_evaluations'          => 'No evaluations are available yet.',
    'unknown_reviewer'        => 'Unknown reviewer',
    'no_comment'              => 'No comments were left.',
    'evaluation_type_supervisor' => 'Supervisor',
    'evaluation_type_volunteer'  => 'Volunteer self-review',
    'no_evaluations_available' => 'No evaluations available yet.',

];
