<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration for the application.
    |
    */

    'ssrf_protection' => [
        'enabled' => true,
        'blocked_ips' => [
            '127.0.0.1',
            'localhost',
            '0.0.0.0',
            '::1',
            '169.254.0.0/16',
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            'fc00::/7',
            'fe80::/10',
        ],
        'allowed_ports' => [80, 443, 8080, 8443],
        'allowed_schemes' => ['http', 'https'],
        'timeout' => 10,
        'max_redirects' => 3,
    ],

    'rate_limiting' => [
        'enabled' => true,
        'login' => [
            'attempts' => 5,
            'decay_minutes' => 15,
        ],
        'url_submission' => [
            'attempts' => 10,
            'decay_minutes' => 60,
        ],
        'api' => [
            'attempts' => 100,
            'decay_minutes' => 60,
        ],
        'file_upload' => [
            'attempts' => 20,
            'decay_minutes' => 60,
        ],
    ],

    'file_upload' => [
        'enabled' => true,
        'max_file_size' => 2 * 1024 * 1024, // 2MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'application/pdf',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'],
        'max_image_width' => 2048,
        'max_image_height' => 2048,
        'virus_scanning' => true,
        'secure_storage' => true,
    ],

    'html_sanitization' => [
        'enabled' => true,
        'allowed_tags' => [
            'p', 'br', 'strong', 'em', 'u', 'i', 'b', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'a', 'img', 'div', 'span',
            'table', 'thead', 'tbody', 'tr', 'td', 'th', 'caption',
        ],
        'allowed_attributes' => [
            'href' => ['a'],
            'title' => ['a'],
            'src' => ['img'],
            'alt' => ['img'],
            'width' => ['img', 'table'],
            'height' => ['img'],
            'class' => ['*'],
            'id' => ['*'],
        ],
    ],

    'security_headers' => [
        'enabled' => true,
        'x_content_type_options' => 'nosniff',
        'x_frame_options' => 'DENY',
        'x_xss_protection' => '1; mode=block',
        'strict_transport_security' => 'max-age=31536000; includeSubDomains; preload',
        'referrer_policy' => 'strict-origin-when-cross-origin',
        'content_security_policy' => [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self' https:",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ],
    ],

    'sql_injection_protection' => [
        'enabled' => false,
        'log_suspicious_queries' => true,
        'block_suspicious_queries' => false,
        'allowed_sql_functions' => ['COUNT', 'SUM', 'AVG', 'MAX', 'MIN', 'DATE', 'TIME', 'YEAR', 'MONTH'],
    ],

    'input_validation' => [
        'strict_mode' => true,
        'max_string_length' => 65535,
        'max_array_items' => 100,
        'max_file_uploads' => 5,
    ],

    'session_security' => [
        'secure_cookie' => true,
        'http_only' => true,
        'same_site' => 'lax',
        'lifetime' => 120, // minutes
        'encrypt' => true,
    ],

    'password_security' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => true,
        'max_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],

    'logging' => [
        'log_security_events' => true,
        'log_failed_logins' => true,
        'log_suspicious_activity' => true,
        'log_file_uploads' => true,
        'retention_days' => 90,
    ],
];
