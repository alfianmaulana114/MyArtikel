<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SsrfProtectionService
{
    private array $blockedIps = [
        '127.0.0.1',
        'localhost',
        '0.0.0.0',
        '::1',
        '169.254.0.0/16', // Link-local
        '10.0.0.0/8',     // Private network
        '172.16.0.0/12',  // Private network
        '192.168.0.0/16', // Private network
        'fc00::/7',       // IPv6 private
        'fe80::/10',      // IPv6 link-local
    ];

    private array $allowedPorts = [80, 443, 8080, 8443];

    private array $allowedSchemes = ['http', 'https'];

    public function validateUrl(string $url): bool
    {
        try {
            $parsed = parse_url($url);

            if (! $parsed || ! isset($parsed['host'])) {
                Log::warning('SSRF Protection: Invalid URL format', ['url' => $url]);

                return false;
            }

            // Validate scheme
            if (! isset($parsed['scheme']) || ! in_array(strtolower($parsed['scheme']), $this->allowedSchemes)) {
                Log::warning('SSRF Protection: Invalid scheme', ['url' => $url, 'scheme' => $parsed['scheme'] ?? 'none']);

                return false;
            }

            // Validate port
            if (isset($parsed['port']) && ! in_array($parsed['port'], $this->allowedPorts)) {
                Log::warning('SSRF Protection: Invalid port', ['url' => $url, 'port' => $parsed['port']]);

                return false;
            }

            // Resolve IP address
            $ip = gethostbyname($parsed['host']);
            if ($ip === $parsed['host']) {
                Log::warning('SSRF Protection: Could not resolve hostname', ['url' => $url, 'host' => $parsed['host']]);

                return false;
            }

            // Check if IP is blocked
            if ($this->isIpBlocked($ip)) {
                Log::warning('SSRF Protection: Blocked IP address', ['url' => $url, 'ip' => $ip]);

                return false;
            }

            // Additional validation: DNS rebinding protection
            if ($this->isPotentialDnsRebinding($parsed['host'], $ip)) {
                Log::warning('SSRF Protection: Potential DNS rebinding attack', ['url' => $url, 'host' => $parsed['host'], 'ip' => $ip]);

                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('SSRF Protection: Validation error', ['url' => $url, 'error' => $e->getMessage()]);

            return false;
        }
    }

    private function isIpBlocked(string $ip): bool
    {
        // Check exact matches first
        if (in_array($ip, $this->blockedIps)) {
            return true;
        }

        // Check CIDR ranges
        foreach ($this->blockedIps as $blocked) {
            if (strpos($blocked, '/') !== false) {
                if ($this->ipInCidrRange($ip, $blocked)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function ipInCidrRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $this->ipv4InRange($ip, $subnet, $mask);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $this->ipv6InRange($ip, $subnet, $mask);
        }

        return false;
    }

    private function ipv4InRange(string $ip, string $subnet, int $mask): bool
    {
        return (ip2long($ip) >> (32 - $mask)) === (ip2long($subnet) >> (32 - $mask));
    }

    private function ipv6InRange(string $ip, string $subnet, int $mask): bool
    {
        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if (! $ipBin || ! $subnetBin) {
            return false;
        }

        $maskBytes = intval($mask / 8);
        $maskBits = $mask % 8;

        for ($i = 0; $i < $maskBytes; $i++) {
            if ($ipBin[$i] !== $subnetBin[$i]) {
                return false;
            }
        }

        if ($maskBits > 0 && $maskBytes < 16) {
            $mask = 0xFF << (8 - $maskBits);

            return (ord($ipBin[$maskBytes]) & $mask) === (ord($subnetBin[$maskBytes]) & $mask);
        }

        return true;
    }

    private function isPotentialDnsRebinding(string $host, string $resolvedIp): bool
    {
        // Simple check: if the resolved IP is different from what we'd expect
        // This is a basic protection - in production, implement proper DNS rebinding detection
        $expectedIps = gethostbynamel($host);

        if (! $expectedIps) {
            return true; // Suspicious if we can't resolve
        }

        return ! in_array($resolvedIp, $expectedIps);
    }

    public function getSafeHttpOptions(): array
    {
        return [
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'follow_location' => 1,
                'max_redirects' => 3,
                'user_agent' => 'Laravel-App/1.0',
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ];
    }
}
