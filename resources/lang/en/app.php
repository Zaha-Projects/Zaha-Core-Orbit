<?php

return [
    'common' => [
        'app_name' => 'Zaha OPS',
        'dashboard' => 'Dashboard',
        'logout' => 'Log out',
        'login' => 'Log in',
        'register' => 'Create account',
        'open_reports' => 'Open reports',
    ],
    'welcome' => [
        'title' => 'Welcome to Zaha OPS',
        'subtitle' => 'A unified platform to manage operations, reports, and approvals.',
        'login_cta' => 'Log in',
        'register_cta' => 'Create a new account',
    ],
    'auth' => [
        'login_title' => 'Log in',
        'register_title' => 'Create account',
        'full_name' => 'Full name',
        'email' => 'Email address',
        'password' => 'Password',
        'confirm_password' => 'Confirm password',
        'remember' => 'Remember me',
        'submit_login' => 'Sign in',
        'submit_register' => 'Register',
        'new_account' => 'Create a new account',
        'have_account' => 'Already have an account? Log in',
    ],
    'dashboard' => [
        'no_role_title' => 'Dashboard',
        'no_role_message' => 'No specific role was found for this user. Please contact administration to update permissions.',
    ],
    'roles' => [
        'super_admin' => [
            'title' => 'Super Admin Dashboard',
            'subtitle' => 'Comprehensive overview of modules, users, and permissions.',
            'actions' => [
                'users' => [
                    'title' => 'User management',
                    'description' => 'Add users and assign roles, branches, and centers.',
                ],
                'approvals' => [
                    'title' => 'Approval tracking',
                    'description' => 'Review agenda, monthly plan, and approval status.',
                ],
                'reports' => [
                    'title' => 'Reports',
                    'description' => 'View operational, financial, and detailed reports.',
                ],
            ],
        ],
        'relations_manager' => [
            'title' => 'Relations Manager Dashboard',
            'subtitle' => 'Manage the annual agenda and relations approvals.',
            'actions' => [
                'agenda' => [
                    'title' => 'Agenda planning',
                    'description' => 'Add annual events and review before approval.',
                ],
                'approvals' => [
                    'title' => 'Relations approvals',
                    'description' => 'Track approvals and handoff to the next step.',
                ],
                'reports' => [
                    'title' => 'Agenda reports',
                    'description' => 'View event summaries by branch and center.',
                ],
            ],
        ],
        'relations_officer' => [
            'title' => 'Relations Officer Dashboard',
            'subtitle' => 'Prepare the annual agenda and manage initial requirements.',
            'actions' => [
                'create_event' => [
                    'title' => 'Create event',
                    'description' => 'Add new events and link to target entities.',
                ],
                'notes' => [
                    'title' => 'Follow up notes',
                    'description' => 'Receive feedback and update required data.',
                ],
                'readiness' => [
                    'title' => 'Approval readiness',
                    'description' => 'Validate completeness before submission.',
                ],
            ],
        ],
        'programs_manager' => [
            'title' => 'Programs Manager Dashboard',
            'subtitle' => 'Oversee the monthly plan and approvals flow.',
            'actions' => [
                'approvals' => [
                    'title' => 'Activity approvals',
                    'description' => 'Review requests and send for execution.',
                ],
                'tracking' => [
                    'title' => 'Execution tracking',
                    'description' => 'Track closed activities and final attachments.',
                ],
                'reports' => [
                    'title' => 'Programs reports',
                    'description' => 'View activity performance by branch and center.',
                ],
            ],
        ],
        'programs_officer' => [
            'title' => 'Programs Officer Dashboard',
            'subtitle' => 'Prepare the monthly plan and execution documents.',
            'actions' => [
                'add_activity' => [
                    'title' => 'Add activity',
                    'description' => 'Create activities and link to the annual agenda.',
                ],
                'attachments' => [
                    'title' => 'Activity attachments',
                    'description' => 'Upload required documents and photos.',
                ],
                'approval_followup' => [
                    'title' => 'Approval follow-up',
                    'description' => 'Update data based on approval feedback.',
                ],
            ],
        ],
        'finance_officer' => [
            'title' => 'Finance Dashboard',
            'subtitle' => 'Track revenue, collections, and bookings.',
            'actions' => [
                'donations' => [
                    'title' => 'Cash support',
                    'description' => 'Record support and link to activities.',
                ],
                'bookings' => [
                    'title' => 'Bookings',
                    'description' => 'Manage bookings, collections, and discounts.',
                ],
                'reports' => [
                    'title' => 'Financial reports',
                    'description' => 'Prepare monthly reports by branch.',
                ],
            ],
        ],
        'maintenance_officer' => [
            'title' => 'Maintenance Dashboard',
            'subtitle' => 'Manage maintenance tickets and approvals.',
            'actions' => [
                'requests' => [
                    'title' => 'Maintenance requests',
                    'description' => 'Log requests and set priorities.',
                ],
                'work_details' => [
                    'title' => 'Work details',
                    'description' => 'Update remediation plans and root causes.',
                ],
                'closures' => [
                    'title' => 'Closure approvals',
                    'description' => 'Track approvals until closure.',
                ],
            ],
        ],
        'transport_officer' => [
            'title' => 'Transport Dashboard',
            'subtitle' => 'Manage vehicles, drivers, and trip schedules.',
            'actions' => [
                'scheduling' => [
                    'title' => 'Trip scheduling',
                    'description' => 'Create daily trips and update status.',
                ],
                'fleet' => [
                    'title' => 'Vehicles and drivers',
                    'description' => 'Monitor fleet readiness and driver status.',
                ],
                'reports' => [
                    'title' => 'Transport reports',
                    'description' => 'View trip summaries by date and branch.',
                ],
            ],
        ],
        'reports_viewer' => [
            'title' => 'Reports Dashboard',
            'subtitle' => 'Track operational reports and export.',
            'actions' => [
                'agenda' => [
                    'title' => 'Agenda reports',
                    'description' => 'View annual event status and approvals.',
                ],
                'revenue' => [
                    'title' => 'Revenue reports',
                    'description' => 'Track collections and bookings by period.',
                ],
                'ops' => [
                    'title' => 'Maintenance & transport',
                    'description' => 'Analyze monthly operational performance.',
                ],
            ],
        ],
        'staff' => [
            'title' => 'Staff Dashboard',
            'subtitle' => 'Quick access to permitted tasks.',
            'actions' => [
                'agenda' => [
                    'title' => 'View agenda',
                    'description' => 'Browse approved events.',
                ],
                'activities' => [
                    'title' => 'View activities',
                    'description' => 'Track related monthly activities.',
                ],
                'summary' => [
                    'title' => 'Task summary',
                    'description' => 'View tasks for the current role.',
                ],
            ],
        ],
    ],
    'reports' => [
        'title' => 'Super Admin Detailed Reports',
        'subtitle' => 'Comprehensive operational and financial reports with numeric indicators, narratives, and flowcharts.',
        'structure' => [
            'title' => 'Organization summary',
            'subtitle' => 'Distribution of core organizational resources.',
            'branches' => 'Branches',
            'centers' => 'Centers',
            'users' => 'Users',
            'vehicles' => 'Vehicles',
        ],
        'operations' => [
            'title' => 'Operations summary',
            'subtitle' => 'Operational volume by module.',
            'agenda' => 'Agenda',
            'monthly_activities' => 'Monthly activities',
            'bookings' => 'Bookings',
            'maintenance_requests' => 'Maintenance requests',
            'trips' => 'Trips',
        ],
        'financials' => [
            'title' => 'Financial indicators',
            'subtitle' => 'Payment totals and cash donations.',
            'payments' => 'Payments',
            'payments_total' => 'Total payments',
            'donations' => 'Cash donations',
            'donations_total' => 'Total donations',
        ],
        'narrative' => [
            'title' => 'Narrative summary',
            'body' => 'This report summarizes operational and financial performance. Indicators are drawn from system data to validate workflow integrity, focusing on responsiveness, operational density, and funding stability.',
            'points' => [
                'Resource balance improved across branches and centers when comparing activity density.',
                'Maintenance and approval flows are clearly tracked for governance.',
                'Financial monitoring remains consistent for payments and donations.',
            ],
        ],
        'status' => [
            'maintenance' => 'Maintenance status',
            'maintenance_subtitle' => 'Maintenance requests by status.',
            'agenda_approvals' => 'Agenda approvals',
            'agenda_approvals_subtitle' => 'Approval decisions distribution.',
            'bookings' => 'Booking status',
            'bookings_subtitle' => 'Bookings by status.',
            'no_data' => 'No data available.',
        ],
        'flowcharts' => [
            'title' => 'Workflow flowcharts',
            'subtitle' => 'Flowcharts describing key operational paths.',
            'maintenance' => 'Maintenance flow',
            'agenda' => 'Agenda and approval flow',
            'transport' => 'Transport flow',
            'bookings' => 'Bookings and payments flow',
        ],
        'flowchart_texts' => [
            'maintenance' => "flowchart TD\n    A[Request logged] --> B{Priority assessment}\n    B -->|Normal| C[Schedule maintenance]\n    B -->|Urgent| D[Immediate team dispatch]\n    C --> E[Execute work]\n    D --> E\n    E --> F[Document results]\n    F --> G[Close request]",
            'agenda' => "flowchart TD\n    A[Event proposal] --> B[Programs manager review]\n    B --> C{Approval decision}\n    C -->|Approved| D[Include in agenda]\n    C -->|Rejected| E[Return for revision]\n    D --> F[Execution follow-up]",
            'transport' => "flowchart TD\n    A[Trip request] --> B[Assign driver & vehicle]\n    B --> C[Confirm trip schedule]\n    C --> D[Execute trip]\n    D --> E[Log notes]",
            'bookings' => "flowchart TD\n    A[Receive booking] --> B[Validate details]\n    B --> C[Collect payment]\n    C --> D{Payment successful?}\n    D -->|Yes| E[Confirm booking]\n    D -->|No| F[Retry payment]",
        ],
    ],
];
