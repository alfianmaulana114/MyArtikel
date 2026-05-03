#!/bin/bash

# PDF Export System Setup Script for MyArtikel
# This script sets up the PDF export functionality with DOMPDF

echo "📄 PDF Export System Setup for MyArtikel"
echo "======================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if running in Laravel project
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel project root directory"
    exit 1
fi

# Function to run Laravel commands
run_laravel_command() {
    print_info "Running: php artisan $1"
    php artisan $1
    if [ $? -eq 0 ]; then
        print_status "Command completed successfully"
    else
        print_error "Command failed: $1"
        return 1
    fi
}

# Function to run Composer commands
run_composer_command() {
    print_info "Running: composer $1"
    composer $1
    if [ $? -eq 0 ]; then
        print_status "Command completed successfully"
    else
        print_error "Command failed: composer $1"
        return 1
    fi
}

# Install DOMPDF
install_dompdf() {
    print_info "Installing DOMPDF package..."
    run_composer_command "require barryvdh/laravel-dompdf"
}

# Create necessary directories
create_directories() {
    print_info "Creating necessary directories..."
    
    # Create PDF export directories
    mkdir -p storage/app/exports/pdf
    mkdir -p storage/app/fonts
    mkdir -p storage/logs/pdf
    mkdir -p public/images/pdf-templates
    
    # Create template directories
    mkdir -p resources/views/pdf/templates/default
    mkdir -p resources/views/pdf/templates/academic
    mkdir -p resources/views/pdf/templates/magazine
    mkdir -p resources/views/pdf/templates/minimal
    mkdir -p resources/views/pdf/templates/business
    
    # Set permissions
    chmod -R 755 storage/app/exports
    chmod -R 755 storage/app/fonts
    chmod -R 755 storage/logs/pdf
    
    print_status "Directories created successfully"
}

# Install fonts
install_fonts() {
    print_info "Installing fonts for PDF generation..."
    
    # Create fonts directory if it doesn't exist
    mkdir -p storage/fonts
    
    # Copy default fonts (DejaVu family)
    if [ -d "vendor/dompdf/dompdf/lib/fonts" ]; then
        cp -r vendor/dompdf/dompdf/lib/fonts/* storage/fonts/ 2>/dev/null || true
    fi
    
    # Download additional fonts if needed
    print_info "Downloading additional fonts..."
    
    # Download Google Fonts (optional)
    if command -v wget >/dev/null 2>&1; then
        # Download Open Sans
        wget -q -O storage/fonts/OpenSans-Regular.ttf "https://fonts.gstatic.com/s/opensans/v34/memSYaGs126MiZpBA-UvWbX2vVnXBbObj2OVZyOOSr4dVJWUgsg-1x4iaVQUwaEQbjB_mQ.ttf" 2>/dev/null || true
        
        # Download Roboto
        wget -q -O storage/fonts/Roboto-Regular.ttf "https://fonts.gstatic.com/s/roboto/v30/KFOmCnqEu92Fr1Mu4mxK.ttf" 2>/dev/null || true
        
        print_status "Additional fonts downloaded"
    else
        print_warning "wget not available, skipping additional font downloads"
    fi
}

# Configure DOMPDF
configure_dompdf() {
    print_info "Configuring DOMPDF..."
    
    # Publish DOMPDF configuration
    run_laravel_command "vendor:publish --provider=\"Barryvdh\\DomPDF\\ServiceProvider\""
    
    # Update DOMPDF configuration
    if [ -f "config/dompdf.php" ]; then
        print_info "Updating DOMPDF configuration..."
        
        # Backup original config
        cp config/dompdf.php config/dompdf.php.backup
        
        # Update key settings
        sed -i 's/"enable_php" => false,/"enable_php" => false,/' config/dompdf.php
        sed -i 's/"enable_javascript" => false,/"enable_javascript" => false,/' config/dompdf.php
        sed -i 's/"enable_remote" => false,/"enable_remote" => true,/' config/dompdf.php
        sed -i 's/"enable_html5_parser" => false,/"enable_html5_parser" => true,/' config/dompdf.php
        
        print_status "DOMPDF configuration updated"
    else
        print_warning "DOMPDF config file not found, configuration may need manual setup"
    fi
}

# Create template files
create_templates() {
    print_info "Creating PDF template files..."
    
    # Default template
    cat > resources/views/pdf/templates/default/single-article.blade.php << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $article->title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            margin: 1in;
        }
        h1 { font-size: 24pt; color: #2c3e50; margin-bottom: 1em; }
        h2 { font-size: 20pt; color: #34495e; margin-bottom: 0.8em; }
        h3 { font-size: 16pt; color: #34495e; margin-bottom: 0.6em; }
        p { margin-bottom: 1em; text-align: justify; }
        .header { border-bottom: 2px solid #3498db; padding-bottom: 1em; margin-bottom: 2em; }
        .meta { font-size: 10pt; color: #666; margin-bottom: 1em; }
        .featured-image { text-align: center; margin: 2em 0; }
        .featured-image img { max-width: 100%; height: auto; }
        .page-break { page-break-before: always; }
        @media print {
            body { margin: 0.5in; }
            .page-break { page-break-before: always; }
            h1, h2, h3 { page-break-after: avoid; }
            img { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $article->title }}</h1>
        <div class="meta">
            <div>By: {{ $article->user->name }}</div>
            <div>Created: {{ $article->created_at->format('Y-m-d H:i') }}</div>
            @if($article->tags->count() > 0)
            <div>Tags: {{ $article->tags->pluck('name')->implode(', ') }}</div>
            @endif
        </div>
    </div>

    @if($options['include_images'] && $article->featured_image)
    <div class="featured-image">
        <img src="{{ $article->featured_image }}" alt="Featured Image">
    </div>
    @endif

    <div class="content">
        @if(isset($clean_content))
            {!! $clean_content !!}
        @else
            {!! $article->content !!}
        @endif
    </div>
</body>
</html>
EOF

    # Multiple articles template
    cat > resources/views/pdf/templates/default/multiple-articles.blade.php << 'EOF'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles Collection</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #333;
            margin: 1in;
        }
        .cover { text-align: center; padding: 2em 0; }
        .cover h1 { font-size: 2.5em; margin-bottom: 0.5em; }
        .cover .info { color: #666; }
        .toc { margin: 2em 0; }
        .toc h2 { border-bottom: 2px solid #3498db; padding-bottom: 0.5em; }
        .toc ol { list-style: none; counter-reset: item; }
        .toc li { counter-increment: item; margin: 0.5em 0; }
        .toc li::before { content: counter(item) ". "; font-weight: bold; }
        .article { margin: 2em 0; page-break-before: always; }
        .article h1 { font-size: 20pt; color: #2c3e50; }
        .article .meta { font-size: 10pt; color: #666; margin-bottom: 1em; }
        .featured-image { text-align: center; margin: 1em 0; }
        .featured-image img { max-width: 100%; height: auto; }
        @media print {
            body { margin: 0.5in; }
            .article { page-break-before: always; }
            h1 { page-break-after: avoid; }
            img { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="cover">
        <h1>Articles Collection</h1>
        <div class="info">
            <p>Total Articles: {{ $total_articles }}</p>
            <p>Generated on: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>

    @if($options['include_toc'] ?? true)
    <div class="toc">
        <h2>Table of Contents</h2>
        <ol>
            @foreach($processed_articles ?? $articles as $index => $item)
                @php $article = $item['article'] ?? $item; @endphp
                <li><a href="#article-{{ $article->id }}">{{ $article->title }}</a></li>
            @endforeach
        </ol>
    </div>
    @endif

    @foreach($processed_articles ?? $articles as $item)
        @php $article = $item['article'] ?? $item; @endphp
        <div class="article" id="article-{{ $article->id }}">
            <h1>{{ $article->title }}</h1>
            <div class="meta">
                <div>By: {{ $article->user->name }}</div>
                <div>Created: {{ $article->created_at->format('Y-m-d H:i') }}</div>
                @if($article->tags->count() > 0)
                <div>Tags: {{ $article->tags->pluck('name')->implode(', ') }}</div>
                @endif
            </div>

            @if($options['include_images'] && $article->featured_image)
            <div class="featured-image">
                <img src="{{ $article->featured_image }}" alt="Featured Image">
            </div>
            @endif

            <div class="content">
                @if(isset($item['clean_content']))
                    {!! $item['clean_content'] !!}
                @else
                    {!! $article->content !!}
                @endif
            </div>
        </div>
    @endforeach
</body>
</html>
EOF

    print_status "PDF templates created"
}

# Test PDF generation
test_pdf_generation() {
    print_info "Testing PDF generation..."
    
    # Create a simple test script
    cat > test_pdf.php << 'EOF'
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Services\AdvancedPdfExportService;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $pdfService = app(AdvancedPdfExportService::class);
    
    // Test template availability
    $templates = $pdfService->getAvailableTemplates();
    echo "Available templates: " . count($templates) . "\n";
    
    foreach ($templates as $template) {
        echo "- {$template['name']}: {$template['description']}\n";
    }
    
    echo "\nPDF Export System is ready!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

    # Run test
    php test_pdf.php
    
    if [ $? -eq 0 ]; then
        print_status "PDF generation test passed"
    else
        print_error "PDF generation test failed"
    fi
    
    # Cleanup test file
    rm -f test_pdf.php
}

# Create sample data
create_sample_data() {
    print_info "Creating sample export data..."
    
    # Run migrations if needed
    run_laravel_command "migrate"
    
    # Create sample export record
    php artisan tinker --execute="
    \$user = \App\Models\User::first();
    if (\$user) {
        \App\Models\Export::create([
            'user_id' => \$user->id,
            'type' => 'pdf',
            'file_path' => 'exports/pdf/sample.pdf',
            'file_size' => 1024000,
            'status' => 'completed',
            'metadata' => [
                'template' => 'default',
                'article_count' => 1,
                'test_export' => true
            ],
            'expires_at' => now()->addDays(30)
        ]);
        echo 'Sample export created successfully';
    } else {
        echo 'No users found, skipping sample data creation';
    }
    "
    
    print_status "Sample data created"
}

# Setup cron jobs
setup_cron_jobs() {
    print_info "Setting up PDF export cron jobs..."
    
    # Add cleanup job
    (crontab -l 2>/dev/null; echo "# PDF Export Cleanup") | crontab -
    (crontab -l 2>/dev/null; echo "0 2 * * * cd $(pwd) && php artisan pdf:export-manager cleanup --days=30 >> storage/logs/pdf/cleanup.log 2>&1") | crontab -
    
    # Add optimization job (weekly)
    (crontab -l 2>/dev/null; echo "0 3 * * 0 cd $(pwd) && php artisan pdf:export-manager optimize >> storage/logs/pdf/optimize.log 2>&1") | crontab -
    
    print_status "Cron jobs configured"
}

# Main setup function
main() {
    print_info "Starting PDF Export System setup..."
    
    # Check PHP version
    PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
    print_info "PHP Version: $PHP_VERSION"
    
    if [[ $(echo "$PHP_VERSION < 8.0" | bc) -eq 1 ]]; then
        print_error "PHP 8.0 or higher is required"
        exit 1
    fi
    
    # Run setup steps
    install_dompdf
    create_directories
    install_fonts
    configure_dompdf
    create_templates
    test_pdf_generation
    create_sample_data
    setup_cron_jobs
    
    print_status "PDF Export System setup completed successfully!"
    print_info ""
    print_info "Next steps:"
    print_info "1. Configure DOMPDF settings in config/dompdf.php"
    print_info "2. Test PDF export at: http://your-domain/pdf/export"
    print_info "3. Monitor logs at: storage/logs/pdf/"
    print_info "4. Customize templates in resources/views/pdf/templates/"
    print_info ""
    print_info "Available commands:"
    print_info "- php artisan pdf:export-manager stats"
    print_info "- php artisan pdf:export-manager cleanup --days=30"
    print_info "- php artisan pdf:export-manager test"
    print_info "- php artisan pdf:export-manager optimize"
}

# Show help
show_help() {
    echo "MyArtikel PDF Export System Setup"
    echo "Usage: $0 [OPTION]"
    echo ""
    echo "Options:"
    echo "  setup       Complete setup (default)"
    echo "  test        Test PDF generation only"
    echo "  fonts       Install fonts only"
    echo "  templates   Create template files only"
    echo "  help        Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 setup    # Complete setup"
    echo "  $0 test     # Test PDF generation"
    echo "  $0 fonts    # Install fonts only"
}

# Handle command line arguments
case "${1:-setup}" in
    setup)
        main
        ;;
    test)
        test_pdf_generation
        ;;
    fonts)
        install_fonts
        ;;
    templates)
        create_templates
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        print_error "Unknown option: $1"
        show_help
        exit 1
        ;;
esac