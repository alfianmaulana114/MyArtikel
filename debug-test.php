<?php
// Simple test untuk cek apakah landing page bisa diakses
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test direct file access
$landingPath = __DIR__.'/landing.html';
if (file_exists($landingPath)) {
    echo "✅ File landing.html ditemukan\n";
    echo "📄 Ukuran file: " . filesize($landingPath) . " bytes\n";
    
    // Check if Tailwind CSS is working
    $content = file_get_contents($landingPath);
    if (strpos($content, 'earth-cream') !== false) {
        echo "✅ Warna earth-tone ditemukan di HTML\n";
    }
    if (strpos($content, 'cdn.tailwindcss.com') !== false) {
        echo "✅ Tailwind CDN ditemukan\n";
    }
} else {
    echo "❌ File landing.html tidak ditemukan\n";
}

// Check CSS files
$cssFiles = [
    '/public/css/notes.css',
    '/public/css/theme/frieren-dark-mode.css',
    '/public/build/assets/app-JtheiTRo.css' // Updated filename
];

echo "\n📁 Cek file CSS:\n";
foreach ($cssFiles as $cssFile) {
    $fullPath = __DIR__ . $cssFile;
    if (file_exists($fullPath)) {
        echo "✅ $cssFile ditemukan (" . filesize($fullPath) . " bytes)\n";
    } else {
        echo "❌ $cssFile tidak ditemukan\n";
    }
}

echo "\n🌐 Server seharusnya berjalan di: http://127.0.0.1:8000\n";
echo "🔗 Coba akses: http://127.0.0.1:8000/test-css.html untuk test CSS\n";
echo "🔗 Landing page: http://127.0.0.1:8000/\n";
echo "🔗 File langsung: http://127.0.0.1:8000/landing.html\n";

// Check Laravel routes
$routes = [
    '/' => 'Landing Page',
    '/login' => 'Login Page',
    '/register' => 'Register Page',
    '/test-css.html' => 'CSS Test Page',
    '/landing.html' => 'Landing HTML Page'
];

echo "\n🛣️  Routes yang tersedia:\n";
foreach ($routes as $path => $description) {
    echo "   $path - $description\n";
}

echo "\n💡 Tips: Jika CSS tidak muncul, coba:\n";
echo "   1. Buka http://127.0.0.1:8000/test-css.html\n";
echo "   2. Buka http://127.0.0.1:8000/landing.html (file langsung)\n";
echo "   3. Buka http://127.0.0.1:8000/ (route Laravel)\n";
echo "   4. Cek browser console untuk error\n";
echo "   5. Pastikan Tailwind CDN ter-load (cek Network tab)\n";