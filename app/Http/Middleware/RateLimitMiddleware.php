<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    private array $limits = [
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
    ];

    public function handle(Request $request, Closure $next, string $type = 'api'): Response
    {
        if (! isset($this->limits[$type])) {
            $type = 'api';
        }

        $key = $this->resolveRequestSignature($request, $type);
        $limit = $this->limits[$type]['attempts'];
        $decayMinutes = $this->limits[$type]['decay_minutes'];

        $attempts = Cache::get($key, 0);

        if ($attempts >= $limit) {
            $retryAfter = $this->getRetryAfter($key, $decayMinutes);

            Log::warning('Rate limit exceeded', [
                'type' => $type,
                'key' => $key,
                'attempts' => $attempts,
                'limit' => $limit,
                'retry_after' => $retryAfter,
            ]);

            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->headers->set('Retry-After', $retryAfter);
        }

        // Increment attempts
        Cache::put($key, $attempts + 1, now()->addMinutes($decayMinutes));

        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit - $attempts - 1));
        $response->headers->set('X-RateLimit-Reset', time() + ($decayMinutes * 60));

        return $response;
    }

    private function resolveRequestSignature(Request $request, string $type): string
    {
        $signature = '';

        switch ($type) {
            case 'login':
                $signature = 'login|'.($request->input('email') ?? $request->ip());
                break;
            case 'url_submission':
                $signature = 'url|'.$request->ip();
                break;
            case 'api':
            default:
                $signature = 'api|'.$request->ip();
                if ($user = $request->user()) {
                    $signature = 'api|'.$user->id;
                }
                break;
        }

        return 'rate_limit:'.md5($signature);
    }

    private function getRetryAfter(string $key, int $decayMinutes): int
    {
        $expiresAt = Cache::get($key.':timer', now());

        return max(0, $expiresAt->diffInSeconds(now()));
    }

    public function tooManyAttempts(string $key, int $limit): bool
    {
        return Cache::get($key, 0) >= $limit;
    }

    public function hit(string $key, int $decayMinutes = 1): void
    {
        Cache::put($key, Cache::get($key, 0) + 1, now()->addMinutes($decayMinutes));
    }

    public function attempts(string $key): int
    {
        return Cache::get($key, 0);
    }

    public function resetAttempts(string $key): void
    {
        Cache::forget($key);
    }
}
