# Laravel Queue System Documentation

## Overview

Sistem queue yang komprehensif untuk MyArtikel dengan support untuk:
- **Multi-queue processing** dengan prioritas
- **Advanced retry mechanisms** dengan exponential backoff
- **Real-time job monitoring** dan status tracking
- **Background processing** untuk operasi berat
- **Automatic failure recovery** dan health monitoring

## Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   User Request  │    │  Job Dispatch    │    │  Queue Worker   │
└─────────┬───────┘    └─────────┬───────┘    └─────────┬───────┘
          │                      │                      │
          ▼                      ▼                      ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│ Background Proc │───▶│  Job Processing │───▶│  Job Completion │
│   Service       │    │    Handler      │    │   Notification  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
          │                      │                      │
          ▼                      ▼                      ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Job Monitoring │◀───│  Retry Service  │◀───│  Failure Log    │
│   Dashboard     │    │  (Backoff)      │    │   & Tracking    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

## Queue Configuration

### Queue Types

1. **high-priority**: User-facing operations, summaries
2. **default**: Standard operations
3. **summarization**: AI summarization jobs
4. **exports**: PDF/CSV export operations
5. **low-priority**: Article ingestion, cleanup tasks

### Configuration

```bash
# .env configuration
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=90

# Queue-specific timeouts
HIGH_PRIORITY_TIMEOUT=60
DEFAULT_TIMEOUT=90
SUMMARIZATION_TIMEOUT=600
EXPORT_TIMEOUT=300
LOW_PRIORITY_TIMEOUT=300
```

## Job Classes

### 1. ProcessArticleIngestion
- **Purpose**: Extract and process articles from URLs
- **Queue**: low-priority
- **Timeout**: 5 minutes
- **Retry**: 5 attempts with exponential backoff

```php
ProcessArticleIngestion::dispatch($userId, $url, $options)
    ->onQueue('low-priority');
```

### 2. ProcessSummaryGeneration
- **Purpose**: Generate article summaries using AI/Local algorithms
- **Queue**: summarization
- **Timeout**: 10 minutes
- **Retry**: 3 attempts with exponential backoff

```php
ProcessSummaryGeneration::dispatch($articleId, $userId, $summaryId, $options)
    ->onQueue('summarization');
```

### 3. ProcessPdfExport
- **Purpose**: Export articles to PDF format
- **Queue**: exports
- **Timeout**: 5 minutes
- **Retry**: 2 attempts with fixed backoff

```php
ProcessPdfExport::dispatch($userId, $articleIds, $exportOptions)
    ->onQueue('exports');
```

## Usage Examples

### Basic Job Dispatch

```php
use App\Services\BackgroundProcessingService;

$service = app(BackgroundProcessingService::class);

// Process article ingestion
$result = $service->processArticleIngestion(
    $userId,
    'https://example.com/article',
    ['priority' => 'high']
);

// Generate summary
$result = $service->processSummaryGeneration(
    $articleId,
    $userId,
    ['prefer_ai' => true, 'max_words' => 150]
);

// Export to PDF
$result = $service->processPdfExport(
    $userId,
    [1, 2, 3], // Article IDs
    ['format' => 'A4', 'include_images' => true]
);
```

### Batch Processing

```php
// Process multiple summaries
$jobs = [
    ['article_id' => 1, 'user_id' => 1, 'summary_id' => 101],
    ['article_id' => 2, 'user_id' => 1, 'summary_id' => 102],
    ['article_id' => 3, 'user_id' => 1, 'summary_id' => 103],
];

$result = $service->processBatchOperation('summarize', $jobs);
```

## Monitoring & Management

### Dashboard Access

Access the job monitoring dashboard at:
```
http://your-domain/jobs/monitoring
```

### API Endpoints

```bash
# Get queue statistics
GET /jobs/stats

# Get job type statistics
GET /jobs/job-types

# Check system health
GET /jobs/health

# Get user-specific statistics
GET /jobs/user-stats

# View failed jobs
GET /jobs/failed

# Retry failed jobs
POST /jobs/retry

# Delete failed jobs
DELETE /jobs/failed

# Restart queue workers
POST /jobs/restart-workers
```

### Command Line Management

```bash
# Show queue status
php artisan queue:manage status

# Show failed jobs
php artisan queue:manage failed

# Retry all failed jobs
php artisan queue:manage retry --all

# Retry specific job
php artisan queue:manage retry --job-id=123

# Flush all failed jobs
php artisan queue:manage flush

# Monitor queue in real-time
php artisan queue:manage monitor

# Advanced worker management
php artisan queue:worker-manager start --queue=summarization --workers=2
php artisan queue:worker-manager restart --queue=exports
php artisan queue:worker-manager status
```

## Retry Mechanisms

### Exponential Backoff
```
Attempt 1: 1 minute delay
Attempt 2: 2 minutes delay
Attempt 3: 4 minutes delay
Attempt 4: 8 minutes delay
Attempt 5: 16 minutes delay (max 1 hour)
```

### Linear Backoff
```
Attempt 1: 1 minute delay
Attempt 2: 2 minutes delay
Attempt 3: 3 minutes delay
Attempt 4: 4 minutes delay
Attempt 5: 5 minutes delay (max 1 hour)
```

### Fixed Backoff
```
All attempts: 5 minutes delay
```

## Worker Configuration

### Supervisor Configuration

```ini
[program:myartikel-queue-summarization]
command=php /path/to/project/artisan queue:work --queue=summarization --sleep=2 --tries=3 --timeout=600 --memory=256
process_name=%(program_name)s_%(process_num)02d
numprocs=2
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/queue-summarization.log
```

### Systemd Service

```ini
[Unit]
Description=MyArtikel Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php artisan queue:work --queue=high-priority,default --sleep=3 --tries=3 --timeout=90
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

## Performance Optimization

### 1. Queue Prioritization
```bash
# Start workers with different priorities
php artisan queue:work --queue=high-priority,default,low-priority
```

### 2. Worker Scaling
```bash
# Multiple workers per queue
php artisan queue:work --queue=summarization &
php artisan queue:work --queue=summarization &
```

### 3. Memory Management
```php
// In job classes
public $timeout = 300;
public $memory = 256; // MB
```

### 4. Batch Processing
```php
Bus::batch($jobs)
    ->then(function (Batch $batch) {
        // All jobs completed successfully
    })
    ->catch(function (Batch $batch, Throwable $e) {
        // First batch job failure detected
    })
    ->dispatch();
```

## Error Handling & Monitoring

### Job Failure Tracking

```php
// In BaseJob class
public function failed(Throwable $exception)
{
    Log::error('Job failed', [
        'job' => get_class($this),
        'attempts' => $this->attempts(),
        'error' => $exception->getMessage(),
        'metadata' => $this->jobMetadata,
    ]);
}
```

### Health Monitoring

```php
// Health check endpoint
public function checkJobHealth(): array
{
    return [
        'healthy' => $this->isSystemHealthy(),
        'issues' => $this->detectIssues(),
        'recommendations' => $this->getRecommendations()
    ];
}
```

## Troubleshooting

### Common Issues

1. **Workers Not Processing Jobs**
   ```bash
   # Check worker status
   php artisan queue:worker-manager status
   
   # Restart workers
   php artisan queue:restart
   ```

2. **High Failed Job Rate**
   ```bash
   # View failed jobs
   php artisan queue:manage failed
   
   # Check error patterns
   tail -f storage/logs/laravel.log | grep "Job failed"
   ```

3. **Queue Backlog**
   ```bash
   # Scale up workers
   php artisan queue:worker-manager start --queue=default --workers=3
   
   # Monitor processing rate
   php artisan queue:manage monitor
   ```

### Performance Issues

1. **Slow Job Processing**
   - Check job timeout settings
   - Optimize job logic
   - Scale worker processes
   - Use faster queue drivers (Redis)

2. **Memory Leaks**
   ```php
   // Add memory monitoring
   public function handle()
   {
       $startMemory = memory_get_usage();
       // Job logic
       $endMemory = memory_get_usage();
       
       if (($endMemory - $startMemory) > 50 * 1024 * 1024) {
           Log::warning('High memory usage detected', [
               'usage' => ($endMemory - $startMemory) / 1024 / 1024 . 'MB'
           ]);
       }
   }
   ```

## Security Considerations

1. **Queue Isolation**
   - Separate queues by sensitivity
   - Use different workers for different job types
   - Implement rate limiting

2. **Job Validation**
   ```php
   // Validate job parameters
   public function __construct(int $userId, string $url)
   {
       if ($userId <= 0) {
           throw new InvalidArgumentException('Invalid user ID');
       }
       
       if (!filter_var($url, FILTER_VALIDATE_URL)) {
           throw new InvalidArgumentException('Invalid URL');
       }
   }
   ```

3. **Access Control**
   ```php
   // Check user permissions
   public function handle()
   {
       $user = User::find($this->userId);
       if (!$user->can('process-articles')) {
           throw new UnauthorizedException();
       }
   }
   ```

## Maintenance

### Regular Tasks

```bash
# Daily cleanup
0 2 * * * php artisan queue:flush

# Weekly restart
0 3 * * 0 php artisan queue:restart

# Monthly log rotation
0 4 1 * * find storage/logs -name "*.log" -mtime +30 -delete
```

### Monitoring Setup

```bash
# Install monitoring tools
composer require laravel/horizon

# Configure Horizon
php artisan horizon:install
php artisan horizon:publish
```

## API Integration

### Webhook Notifications

```php
// Job completion webhook
public function handle()
{
    // Process job
    $result = $this->processJob();
    
    // Send webhook
    Http::post($this->webhookUrl, [
        'job_id' => $this->jobId,
        'status' => 'completed',
        'result' => $result
    ]);
}
```

### Progress Tracking

```php
// Track job progress
public function handle()
{
    $totalSteps = 100;
    
    for ($i = 0; $i < $totalSteps; $i++) {
        // Process step
        $this->processStep($i);
        
        // Update progress
        $this->updateProgress(($i + 1) / $totalSteps * 100);
    }
}
```

This comprehensive queue system provides robust background processing capabilities with excellent monitoring, retry mechanisms, and scalability options for the MyArtikel application.