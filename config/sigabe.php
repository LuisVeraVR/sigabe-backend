<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SIGABE System Configuration
    |--------------------------------------------------------------------------
    */

    'system' => [
        'name' => env('APP_NAME', 'SIGABE'),
        'version' => '1.0.0',
        'timezone' => env('APP_TIMEZONE', 'America/Bogota'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules Configuration
    |--------------------------------------------------------------------------
    */

    'loans' => [
        'max_active_per_user' => env('SIGABE_MAX_ACTIVE_LOANS', 2),
        'default_duration_days' => env('SIGABE_LOAN_DURATION_DAYS', 7),
        'overdue_notification_days' => 1,
        'allow_renewals' => true,
        'max_renewals' => 2,
    ],

    'reservations' => [
        'max_hours_per_booking' => env('SIGABE_RESERVATION_MAX_HOURS', 4),
        'require_approval' => true,
        'auto_cancel_minutes' => 15,
        'advance_booking_days' => 30,
    ],

    'equipment' => [
        'maintenance' => [
            'preventive_interval_days' => 90,
            'notify_days_before' => 7,
        ],
        'unique_identifier_required' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Configuration
    |--------------------------------------------------------------------------
    */

    'external_api' => [
        'enabled' => env('API_EXTERNAL_ENABLED', true),
        'rate_limit' => env('API_EXTERNAL_RATE_LIMIT', 60), // requests per minute
        'version' => 'v1',
        'exposed_resources' => [
            'equipment' => true,
            'spaces' => true,
            'catalog' => false, // No exponer catálogo por ahora
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'email' => [
            'enabled' => true,
            'queue' => true,
        ],
        'channels' => [
            'loan_confirmation' => ['mail'],
            'loan_reminder' => ['mail'],
            'reservation_approved' => ['mail'],
            'reservation_rejected' => ['mail'],
            'equipment_maintenance' => ['mail'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit & Logging
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'enabled' => true,
        'log_api_requests' => env('LOG_API_REQUESTS', true),
        'retention_days' => 365,
        'sensitive_actions' => [
            'login',
            'logout',
            'failed_login',
            'user_created',
            'user_deleted',
            'role_assigned',
            'permission_changed',
            'password_changed',
        ],
    ],

    'security' => [
        'password' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'check_compromised' => true,
            'expire_days' => null,
        ],

        'require_institutional_email' => env('REQUIRE_INSTITUTIONAL_EMAIL', true),
        'institutional_domain' => 'sena.edu.co',

        'rate_limit' => [
            'login_attempts' => 5,
            'api_requests' => 60,
            'guest_requests' => 30,
            'external_api' => 60,
        ],

        'token' => [
            'expiration_minutes' => 60 * 24 * 7,
            'max_tokens_per_user' => 5,
        ],

        'headers' => [
            'x_content_type_options' => 'nosniff',
            'x_frame_options' => 'DENY',
            'x_xss_protection' => '1; mode=block',
            'referrer_policy' => 'strict-origin-when-cross-origin',
            'permissions_policy' => 'geolocation=(), microphone=(), camera=()',
        ],
    ],
];
