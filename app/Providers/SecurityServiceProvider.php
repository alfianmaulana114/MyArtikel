<?php

namespace App\Providers;

use App\Services\FileUploadSecurityService;
use App\Services\SsrfProtectionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class SecurityServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SsrfProtectionService::class, function ($app) {
            return new SsrfProtectionService;
        });

        $this->app->singleton(FileUploadSecurityService::class, function ($app) {
            return new FileUploadSecurityService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerCustomValidators();
        $this->setupSqlInjectionProtection();
        $this->setupSecurityLogging();
    }

    /**
     * Register custom validation rules
     */
    private function registerCustomValidators(): void
    {
        // SSRF Protection Validator
        Validator::extend('ssrf_safe', function ($attribute, $value, $parameters, $validator) {
            $ssrfService = app(SsrfProtectionService::class);

            return $ssrfService->validateUrl($value);
        });

        Validator::replacer('ssrf_safe', function ($message, $attribute, $rule, $parameters) {
            return 'The '.$attribute.' field contains an unsafe URL.';
        });

        // Secure File Upload Validator
        Validator::extend('secure_file', function ($attribute, $value, $parameters, $validator) {
            if (! $value instanceof UploadedFile) {
                return false;
            }

            $fileSecurityService = app(FileUploadSecurityService::class);
            $result = $fileSecurityService->validateUpload($value);

            return $result['valid'];
        });

        Validator::replacer('secure_file', function ($message, $attribute, $rule, $parameters) {
            return 'The '.$attribute.' field contains an insecure file.';
        });

        // No Script Validator
        Validator::extend('no_script', function ($attribute, $value, $parameters, $validator) {
            if (! is_string($value)) {
                return true;
            }

            $dangerousPatterns = [
                '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is',
                '/javascript:/i',
                '/vbscript:/i',
                '/on\w+\s*=/i',
                '/data:text\/html/i',
            ];

            foreach ($dangerousPatterns as $pattern) {
                if (preg_match($pattern, $value)) {
                    return false;
                }
            }

            return true;
        });

        Validator::replacer('no_script', function ($message, $attribute, $rule, $parameters) {
            return 'The '.$attribute.' field contains potentially dangerous content.';
        });
    }

    /**
     * Setup SQL injection protection
     */
    private function setupSqlInjectionProtection(): void
    {
        if (! config('security.sql_injection_protection.enabled')) {
            return;
        }

        // Listen for database queries to detect suspicious patterns
        DB::listen(function ($query) {
            if ($this->isSuspiciousQuery($query->sql)) {
                Log::warning('Suspicious SQL query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);

                if (config('security.sql_injection_protection.block_suspicious_queries')) {
                    throw new \Exception('Suspicious database query detected');
                }
            }
        });
    }

    /**
     * Check if query is suspicious
     */
    private function isSuspiciousQuery(string $sql): bool
    {
        $suspiciousPatterns = [
            '/UNION\s+SELECT/i',
            '/;\s*(DROP|DELETE|TRUNCATE|ALTER|CREATE|EXEC)/i',
            '/\b(SELECT\s+\*|DROP\s+TABLE|DELETE\s+FROM)\b/i',
            '/\/\*.*?\*\//s',
            '/--.*$/m',
            '/[\x00\x1a]/',
            '/\b(OR|AND)\s+1\s*=\s*1\b/i',
            '/\b(SLEEP|BENCHMARK|WAITFOR\s+DELAY)\b/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Setup security logging
     */
    private function setupSecurityLogging(): void
    {
        if (! config('security.logging.log_security_events')) {
            return;
        }

        // Log authentication attempts
        $this->app['events']->listen('Illuminate\Auth\Events\Attempting', function ($event) {
            Log::info('Authentication attempt', [
                'credentials' => array_keys($event->credentials),
                'guard' => $event->guard,
            ]);
        });

        // Log successful logins
        $this->app['events']->listen('Illuminate\Auth\Events\Login', function ($event) {
            Log::info('Successful login', [
                'user_id' => $event->user->id,
                'guard' => $event->guard,
            ]);
        });

        // Log failed logins
        $this->app['events']->listen('Illuminate\Auth\Events\Failed', function ($event) {
            Log::warning('Failed login attempt', [
                'credentials' => array_keys($event->credentials),
                'guard' => $event->guard,
            ]);
        });

        // Log logout events
        $this->app['events']->listen('Illuminate\Auth\Events\Logout', function ($event) {
            Log::info('User logout', [
                'user_id' => $event->user->id,
                'guard' => $event->guard,
            ]);
        });
    }
}
