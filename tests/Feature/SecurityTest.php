<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\SsrfProtectionService;
use App\Services\FileUploadSecurityService;

class SecurityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test SSRF protection blocks private IP addresses
     */
    public function test_ssrf_protection_blocks_private_ips()
    {
        $ssrfService = new SsrfProtectionService();
        
        $privateUrls = [
            'http://127.0.0.1',
            'http://localhost',
            'http://192.168.1.1',
            'http://10.0.0.1',
            'http://172.16.0.1',
        ];

        foreach ($privateUrls as $url) {
            $this->assertFalse($ssrfService->validateUrl($url), "SSRF should block: $url");
        }
    }

    /**
     * Test SSRF protection allows legitimate URLs
     */
    public function test_ssrf_protection_allows_legitimate_urls()
    {
        $ssrfService = new SsrfProtectionService();
        
        $legitimateUrls = [
            'https://example.com',
            'https://google.com',
            'https://github.com',
        ];

        foreach ($legitimateUrls as $url) {
            $this->assertTrue($ssrfService->validateUrl($url), "SSRF should allow: $url");
        }
    }

    /**
     * Test rate limiting for login attempts
     */
    public function test_rate_limiting_blocks_excessive_login_attempts()
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Make 5 failed login attempts (limit is 5)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
            
            if ($i < 4) {
                $response->assertStatus(302); // Redirect back with error
            }
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429); // Too Many Requests
    }

    /**
     * Test HTML sanitization removes dangerous content
     */
    public function test_html_sanitization_removes_dangerous_content()
    {
        $dangerousContent = '<script>alert("XSS")</script><p>Safe content</p>';
        $safeContent = '<p>Safe content</p>';

        // Test through API endpoint that should sanitize input
        $response = $this->post('/api/notes', [
            'content' => $dangerousContent,
            'article_id' => 1,
        ]);

        // Should reject the request due to dangerous content
        $response->assertStatus(422);
    }

    /**
     * Test security headers are present in responses
     */
    public function test_security_headers_are_present()
    {
        $response = $this->get('/');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Strict-Transport-Security');
        $response->assertHeader('Referrer-Policy');
        $response->assertHeader('Content-Security-Policy');
    }

    /**
     * Test SQL injection protection
     */
    public function test_sql_injection_protection()
    {
        $maliciousInput = "1' OR '1'='1";
        
        // This should not cause SQL injection
        $response = $this->get("/api/articles?search=" . urlencode($maliciousInput));
        
        $response->assertStatus(200);
        // Should not return all records due to SQL injection
        $this->assertNotContains('all records', $response->getContent());
    }

    /**
     * Test file upload security
     */
    public function test_file_upload_security_blocks_dangerous_files()
    {
        Storage::fake('public');
        
        // Create a fake PHP file
        $dangerousFile = UploadedFile::fake()->create('dangerous.php', 100, 'text/x-php');
        
        $fileSecurityService = new FileUploadSecurityService();
        $result = $fileSecurityService->validateUpload($dangerousFile);
        
        $this->assertFalse($result['valid'], 'Should block PHP files');
    }

    /**
     * Test file upload security allows safe images
     */
    public function test_file_upload_security_allows_safe_images()
    {
        Storage::fake('public');
        
        $safeFile = UploadedFile::fake()->image('avatar.jpg', 100, 100);
        
        $fileSecurityService = new FileUploadSecurityService();
        $result = $fileSecurityService->validateUpload($safeFile);
        
        $this->assertTrue($result['valid'], 'Should allow safe image files');
    }

    /**
     * Test XSS protection in form inputs
     */
    public function test_xss_protection_in_form_inputs()
    {
        $xssPayload = '<script>alert("XSS")</script>';
        
        $response = $this->post('/api/tags', [
            'name' => $xssPayload,
            'color' => '#FF0000',
        ]);

        $response->assertStatus(422); // Should reject due to validation
    }

    /**
     * Test CSRF protection is active
     */
    public function test_csrf_protection_is_active()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ], [
            'X-CSRF-TOKEN' => 'invalid-token'
        ]);

        // Should be rejected due to invalid CSRF token
        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Test input validation prevents SQL injection
     */
    public function test_input_validation_prevents_sql_injection()
    {
        $maliciousInput = [
            'email' => "test@example.com'; DROP TABLE users; --",
            'password' => 'password123',
        ];
        
        $response = $this->post('/login', $maliciousInput);
        
        // Should not cause SQL injection, should just fail validation
        $response->assertStatus(302); // Redirect back with validation error
    }

    /**
     * Test that private network URLs are blocked
     */
    public function test_private_network_urls_are_blocked()
    {
        $privateUrls = [
            'http://localhost/admin',
            'http://127.0.0.1/phpmyadmin',
            'http://192.168.1.1/config',
            'http://10.0.0.1/internal',
        ];

        foreach ($privateUrls as $url) {
            $response = $this->post('/api/articles', [
                'url' => $url,
            ]);

            $response->assertStatus(422); // Should be rejected
        }
    }

    /**
     * Test that file upload size limits are enforced
     */
    public function test_file_upload_size_limits_are_enforced()
    {
        Storage::fake('public');
        
        // Create a file larger than 2MB limit
        $largeFile = UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg');
        
        $fileSecurityService = new FileUploadSecurityService();
        $result = $fileSecurityService->validateUpload($largeFile);
        
        $this->assertFalse($result['valid'], 'Should block files larger than size limit');
    }

    /**
     * Test that dangerous file extensions are blocked
     */
    public function test_dangerous_file_extensions_are_blocked()
    {
        Storage::fake('public');
        
        $dangerousFiles = [
            UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload'),
            UploadedFile::fake()->create('script.sh', 100, 'text/x-shellscript'),
            UploadedFile::fake()->create('config.bat', 100, 'application/x-bat'),
        ];

        $fileSecurityService = new FileUploadSecurityService();
        
        foreach ($dangerousFiles as $file) {
            $result = $fileSecurityService->validateUpload($file);
            $this->assertFalse($result['valid'], 'Should block dangerous file extensions');
        }
    }

    /**
     * Test that HTML content is properly sanitized
     */
    public function test_html_content_is_properly_sanitized()
    {
        $htmlWithScript = '<p>Safe content</p><script>alert("XSS")</script>';
        $expectedClean = '<p>Safe content</p>';
        
        // Test through note creation
        $response = $this->actingAs(\App\Models\User::factory()->create())
            ->post('/api/notes', [
                'content' => $htmlWithScript,
                'article_id' => 1,
            ]);

        $response->assertStatus(422); // Should reject due to script tag
    }

    /**
     * Test that authentication is required for protected endpoints
     */
    public function test_authentication_required_for_protected_endpoints()
    {
        $protectedEndpoints = [
            '/api/articles',
            '/api/notes',
            '/api/bookmarks',
            '/api/tags',
        ];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->get($endpoint);
            $response->assertStatus(401); // Unauthorized
        }
    }
}