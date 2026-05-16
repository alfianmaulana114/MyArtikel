<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\SsrfProtectionService;
use App\Services\FileUploadSecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\HtmlSanitizationMiddleware::class);
    }

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

    public function test_rate_limiting_blocks_excessive_login_attempts()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            $this->assertContains($response->status(), [302, 429]);
        }
    }

    public function test_security_headers_are_present()
    {
        $response = $this->get('/');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_file_upload_security_blocks_dangerous_files()
    {
        Storage::fake('public');
        $dangerousFile = UploadedFile::fake()->create('dangerous.php', 100, 'text/x-php');

        $fileSecurityService = new FileUploadSecurityService();
        $result = $fileSecurityService->validateUpload($dangerousFile);

        $this->assertFalse($result['valid'], 'Should block PHP files');
    }

    public function test_file_upload_security_allows_safe_images()
    {
        Storage::fake('public');
        $safeFile = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $fileSecurityService = new FileUploadSecurityService();
        $result = $fileSecurityService->validateUpload($safeFile);

        $this->assertTrue($result['valid'], 'Should allow safe image files');
    }

    public function test_file_upload_size_limits_are_enforced()
    {
        Storage::fake('public');
        $largeFile = UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg');

        $fileSecurityService = new FileUploadSecurityService();
        $result = $fileSecurityService->validateUpload($largeFile);

        $this->assertFalse($result['valid'], 'Should block files larger than size limit');
    }

    public function test_dangerous_file_extensions_are_blocked()
    {
        Storage::fake('public');

        $fileSecurityService = new FileUploadSecurityService();

        $dangerousFiles = [
            UploadedFile::fake()->create('malicious.exe', 100, 'application/x-msdownload'),
            UploadedFile::fake()->create('script.sh', 100, 'text/x-shellscript'),
            UploadedFile::fake()->create('config.bat', 100, 'application/x-bat'),
        ];

        foreach ($dangerousFiles as $file) {
            $result = $fileSecurityService->validateUpload($file);
            $this->assertFalse($result['valid'], 'Should block dangerous file extensions');
        }
    }

    public function test_authentication_required_for_protected_endpoints()
    {
        $protectedEndpoints = ['/dashboard', '/articles', '/notes', '/bookmarks', '/tags', '/projects'];

        foreach ($protectedEndpoints as $endpoint) {
            $response = $this->get($endpoint);
            $this->assertContains($response->status(), [302, 401, 403]);
        }
    }

    public function test_articles_data_endpoint_requires_auth()
    {
        $response = $this->get('/articles/data');
        $this->assertContains($response->status(), [302, 401]);
    }
}
