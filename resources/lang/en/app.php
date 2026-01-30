<?php

return [
    'common' => [
        'app_name' => 'Zaha OPS',
        'dashboard' => 'Dashboard',
        'logout' => 'Log out',
        'login' => 'Log in',
        'register' => 'Create account',
        'open_reports' => 'Open reports',
        'open_section' => 'Open section',
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
                'branches' => [
                    'title' => 'Branch management',
                    'description' => 'Create and update Zaha Cultural Center branches.',
                ],
                'centers' => [
                    'title' => 'Center management',
                    'description' => 'Add operational centers under each branch.',
                ],
                'approvals' => [
                    'title' => 'Approval tracking',
                    'description' => 'Review agenda, monthly plan, and approval status.',
                ],
                'roles' => [
                    'title' => 'Role management',
                    'description' => 'Define role permissions and checklists.',
                ],
                'reports' => [
                    'title' => 'Reports',
                    'description' => 'View operational, financial, and detailed reports.',
                ],
            ],
            'sidebar' => [
                'title' => 'Admin navigation',
                'dashboard' => 'Overview',
                'users' => 'Users',
                'branches' => 'Branches',
                'centers' => 'Centers',
                'roles' => 'Roles',
                'approvals' => 'Approvals',
                'reports' => 'Reports',
            ],
            'branches' => [
                'title' => 'Branch management',
                'subtitle' => 'Maintain Zaha Cultural Center branches in Jordan.',
                'create_title' => 'Add a new branch',
                'list_title' => 'Current branches',
                'fields' => [
                    'name' => 'Branch name',
                    'city' => 'City',
                    'address' => 'Address',
                ],
                'table' => [
                    'name' => 'Branch',
                    'city' => 'City',
                    'address' => 'Address',
                    'actions' => 'Actions',
                    'unassigned' => 'Unassigned',
                    'empty' => 'No branches available.',
                ],
                'actions' => [
                    'create' => 'Create branch',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                    'save' => 'Save changes',
                ],
                'created' => 'Branch created successfully.',
                'updated' => 'Branch updated: :branch.',
                'deleted' => 'Branch removed: :branch.',
            ],
            'centers' => [
                'title' => 'Center management',
                'subtitle' => 'Maintain operational centers for each branch.',
                'create_title' => 'Add a new center',
                'list_title' => 'Current centers',
                'fields' => [
                    'branch' => 'Branch',
                    'branch_placeholder' => 'Select branch',
                    'name' => 'Center name',
                ],
                'table' => [
                    'name' => 'Center',
                    'branch' => 'Branch',
                    'actions' => 'Actions',
                    'unassigned' => 'Unassigned',
                    'empty' => 'No centers available.',
                ],
                'actions' => [
                    'create' => 'Create center',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                    'save' => 'Save changes',
                ],
                'created' => 'Center created successfully.',
                'updated' => 'Center updated: :center.',
                'deleted' => 'Center removed: :center.',
            ],
            'roles' => [
                'title' => 'Role management',
                'subtitle' => 'Create roles and define access checklists for each role.',
                'create_title' => 'Create a new role',
                'permissions_title' => 'Role permissions checklist',
                'fields' => [
                    'name' => 'Role name',
                ],
                'actions' => [
                    'create' => 'Create role',
                    'save' => 'Save permissions',
                ],
                'created' => 'Role created successfully.',
                'updated' => 'Permissions updated for :role.',
                'no_roles' => 'No roles have been created yet.',
                'no_permissions' => 'No permissions found.',
            ],
            'users' => [
                'title' => 'User management',
                'subtitle' => 'Create accounts, assign branches and roles, and manage status.',
                'create_title' => 'Create a new account',
                'list_title' => 'Current users',
                'fields' => [
                    'name' => 'Full name',
                    'email' => 'Email address',
                    'phone' => 'Phone number',
                    'password' => 'Temporary password',
                    'password_optional' => 'New password (optional)',
                    'branch' => 'Branch',
                    'center' => 'Center',
                    'role' => 'Role',
                    'status' => 'Status',
                    'branch_placeholder' => 'Select branch',
                    'center_placeholder' => 'Select center',
                ],
                'status' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
                'table' => [
                    'name' => 'Name',
                    'email' => 'Email',
                    'branch' => 'Branch',
                    'center' => 'Center',
                    'role' => 'Role',
                    'status' => 'Status',
                    'actions' => 'Actions',
                    'unassigned' => 'Unassigned',
                    'empty' => 'No users available.',
                ],
                'actions' => [
                    'create' => 'Create account',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                    'save' => 'Save changes',
                ],
                'created' => 'User created successfully.',
                'updated' => 'User updated: :user.',
                'deleted' => 'User removed: :user.',
            ],
            'approvals' => [
                'title' => 'Operations approvals',
                'subtitle' => 'Track approvals for every operational plan step.',
                'steps' => [
                    'agenda' => [
                        'title' => 'Annual agenda approvals',
                        'items' => [
                            ['label' => 'Event proposal submitted', 'owner' => 'Relations officer'],
                            ['label' => 'Manager review & feedback', 'owner' => 'Relations manager'],
                            ['label' => 'Program alignment review', 'owner' => 'Programs manager'],
                            ['label' => 'Final approval decision', 'owner' => 'Super admin'],
                        ],
                    ],
                    'monthly_plan' => [
                        'title' => 'Monthly plan approvals',
                        'items' => [
                            ['label' => 'Monthly plan drafted', 'owner' => 'Programs officer'],
                            ['label' => 'Programs manager validation', 'owner' => 'Programs manager'],
                            ['label' => 'Budget confirmation', 'owner' => 'Finance officer'],
                            ['label' => 'Executive sign-off', 'owner' => 'Super admin'],
                        ],
                    ],
                    'maintenance' => [
                        'title' => 'Maintenance approvals',
                        'items' => [
                            ['label' => 'Request logged', 'owner' => 'Branch staff'],
                            ['label' => 'Maintenance inspection', 'owner' => 'Maintenance officer'],
                            ['label' => 'Budget approval', 'owner' => 'Finance officer'],
                            ['label' => 'Closure confirmation', 'owner' => 'Maintenance officer'],
                        ],
                    ],
                    'transport' => [
                        'title' => 'Transport approvals',
                        'items' => [
                            ['label' => 'Trip request created', 'owner' => 'Staff'],
                            ['label' => 'Vehicle & driver assignment', 'owner' => 'Transport officer'],
                            ['label' => 'Schedule confirmation', 'owner' => 'Programs manager'],
                            ['label' => 'Trip completion review', 'owner' => 'Transport officer'],
                        ],
                    ],
                    'bookings' => [
                        'title' => 'Bookings approvals',
                        'items' => [
                            ['label' => 'Booking request received', 'owner' => 'Front desk'],
                            ['label' => 'Payment verification', 'owner' => 'Finance officer'],
                            ['label' => 'Booking confirmation', 'owner' => 'Programs officer'],
                            ['label' => 'Final reporting', 'owner' => 'Super admin'],
                        ],
                    ],
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
