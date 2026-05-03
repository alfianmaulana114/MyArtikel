# Article Ingestion System

Sistem robust untuk mengambil, memproses, dan menyimpan artikel dari web dengan perlindungan SSRF, ekstraksi konten, dan sanitasi HTML.

## Fitur Utama

### 🔒 Security & Protection
- **SSRF Protection**: Validasi DNS, blocking private IPs dan localhost
- **URL Validation**: Normalisasi URL, removal tracking parameters
- **HTML Sanitization**: Remove dangerous elements dan XSS protection
- **Content Size Limits**: Maksimal content size protection

### 📄 Content Extraction
- **Smart Content Detection**: Algoritma readability-style untuk ekstraksi konten utama
- **Metadata Extraction**: Title, author, published date, tags, language
- **Content Analysis**: Word count, reading time calculation
- **Multi-format Support**: HTML parsing dengan fallback mechanisms

### 🔄 Status Tracking & Retry
- **State Machine**: Status tracking lengkap (queued → fetching → extracting → ready/failed)
- **Retry Mechanism**: Exponential backoff untuk failed attempts
- **Error Classification**: Proper error types dengan retryable/non-retryable classification
- **Statistics**: Comprehensive metrics dan performance tracking

### 🚀 Performance & Scalability
- **Batch Processing**: Process multiple URLs secara efisien
- **Rate Limiting**: Built-in delays untuk menghindari rate limiting
- **Memory Efficient**: Streaming processing untuk large content
- **Configurable**: Extensive configuration options

## Instalasi

```bash
npm install
npm run build
```

## Penggunaan Dasar

```typescript
import { ArticleIngestionService } from './src/services/ArticleIngestionService';

// Initialize service
const service = new ArticleIngestionService({
  maxContentSize: 5 * 1024 * 1024, // 5MB
  maxRetries: 3,
  sanitizeHtml: true
});

// Ingest single URL
const result = await service.ingest('https://example.com/article');

if (result.status === 'ready') {
  console.log('Title:', result.content?.title);
  console.log('Content:', result.content?.content);
  console.log('Word count:', result.content?.wordCount);
}
```

## Configuration Options

```typescript
const config = {
  // Security settings
  maxContentSize: 5 * 1024 * 1024,        // Max content size in bytes
  maxRedirects: 5,                         // Max redirects to follow
  requestTimeout: 30000,                   // Request timeout in ms
  
  // Retry settings
  maxRetries: 3,                           // Max retry attempts
  retryDelay: 1000,                        // Base retry delay in ms
  
  // Network settings
  userAgent: 'ArticleIngestionBot/1.0',     // User agent string
  allowedSchemes: ['http:', 'https:'],     // Allowed URL schemes
  
  // Security lists
  blockedHosts: ['malicious.com'],         // Blocked hostnames
  privateIpRanges: [                       // Private IP patterns
    '^127\\.',
    '^10\\.',
    '^172\\.(1[6-9]|2[0-9]|3[01])\\.',
    '^192\\.168\\.'
  ],
  
  // Processing options
  sanitizeHtml: true,                      // Enable HTML sanitization
  extractMetadata: true                    // Enable metadata extraction
};
```

## Status States

```
QUEUED → FETCHING → EXTRACTING → READY
                    ↓
                  FAILED
```

- **QUEUED**: Job sedang menunggu untuk diproses
- **FETCHING**: Sedang mengambil content dari URL
- **EXTRACTING**: Sedang mengekstrak konten dan metadata
- **READY**: Konten berhasil diekstrak dan siap digunakan
- **FAILED**: Proses gagal dengan error details

## Error Types

### Retryable Errors
- `NETWORK_ERROR`: Network connectivity issues
- `TIMEOUT`: Request timeout
- `DNS_ERROR`: DNS resolution failures

### Non-Retryable Errors
- `INVALID_URL`: Invalid URL format
- `SSRF_BLOCKED`: SSRF protection triggered
- `CONTENT_TOO_LARGE`: Content exceeds size limit
- `EXTRACTION_FAILED`: Content extraction failed
- `SANITIZATION_FAILED`: HTML sanitization failed

## Batch Processing

```typescript
const urls = [
  'https://example.com/article1',
  'https://example.com/article2',
  'https://example.com/article3'
];

const results = await service.ingestBatch(urls);

results.forEach(result => {
  if (result.status === 'ready') {
    console.log(`${result.url}: Success`);
  } else {
    console.log(`${result.url}: Failed - ${result.error?.message}`);
  }
});
```

## Status Monitoring

```typescript
// Get job status
const job = service.getJobStatus(jobId);

// Get all jobs
const allJobs = service.getAllJobs();

// Get jobs by status
const failedJobs = service.getJobsByStatus('failed');

// Get system statistics
const stats = service.getStats();
console.log(`Success rate: ${stats.successRate}%`);
console.log(`Average processing time: ${stats.averageProcessingTime}s`);
```

## Retry Management

```typescript
// Process retry queue
await service.processRetries();

// Get retryable jobs
const retryableJobs = service.getRetryableJobs();

// Manual retry
const failedJob = service.getJobsByStatus('failed')[0];
if (failedJob.error?.retryable) {
  await service.ingest(failedJob.url);
}
```

## Security Features

### SSRF Protection
- DNS validation untuk semua URLs
- IP address validation (block private ranges)
- Hostname allowlist/blocklist
- Protocol restrictions (HTTP/HTTPS only)

### Content Security
- HTML sanitization dengan DOMPurify
- XSS protection
- Content-type validation
- Size limits enforcement

### Network Security
- User-agent spoofing protection
- Redirect following dengan limits
- Timeout controls
- Rate limiting

## Testing

```bash
# Run all tests
npm test

# Run dengan coverage
npm run test:coverage

# Run specific test file
npm test -- UrlValidator.test.ts
```

## Performance Considerations

- **Memory Usage**: Content di-streaming untuk large files
- **Rate Limiting**: Built-in delays untuk menghindari rate limiting
- **Timeout Controls**: Configurable timeouts untuk prevent hanging
- **Retry Backoff**: Exponential backoff untuk failed requests

## Deployment

```bash
# Build untuk production
npm run build

# Start service
npm start
```

## Monitoring & Logging

Sistem ini menggunakan Pino logger dengan structured logging:

```typescript
{
  "level": 30,
  "time": 1640995200000,
  "msg": "Article ingestion completed successfully",
  "url": "https://example.com/article",
  "jobId": "job_1234567890",
  "title": "Article Title",
  "wordCount": 1500
}
```

## Contributing

1. Fork repository
2. Create feature branch
3. Add tests untuk new features
4. Ensure semua tests pass
5. Submit pull request

## License

MIT License - see LICENSE file untuk details.