# Sistem Summarization dengan Gemini API + Local Fallback

Sistem ini menyediakan fitur summarization otomatis untuk artikel dengan integrasi Gemini AI API dan local fallback algorithm.

## Fitur Utama

### 1. Gemini API Integration
- **Abstractive Summarization**: Menggunakan Gemini Pro API untuk menghasilkan ringkasan yang inovatif dan natural
- **Structured Output**: Menghasilkan paragraf ringkasan + bullet points key points
- **Error Handling**: Retry mechanism dan fallback yang robust
- **Caching**: Cache hasil Gemini selama 24 jam untuk efisiensi

### 2. Local Extractive Summarization Fallback
- **Algorithm**: TF-IDF based sentence ranking dengan multi-factor scoring
- **Key Point Extraction**: Otomatis ekstrak 3-5 poin penting
- **Performance**: Cepat dan tidak bergantung pada external API
- **Caching**: Cache lokal untuk performa optimal

### 3. Async Processing dengan Queue System
- **Background Processing**: Gunakan Laravel Queue untuk processing yang lama
- **Status Tracking**: Real-time status monitoring (pending → processing → completed/failed)
- **Retry Logic**: Otomatis retry untuk failed jobs

### 4. Caching System
- **Multi-level Caching**: Cache berdasarkan content hash, parameter, dan user
- **TTL Management**: Cache expiry yang configurable
- **Cache Invalidation**: Otomatis invalidasi untuk content yang berubah

### 5. On-demand Generation
- **User-triggered**: User klik tombol "Generate Summary"
- **Parameter Options**: Customizable word count, language, AI preference
- **Real-time Feedback**: Loading states dan progress indication

### 6. Structured Output Format
```json
{
  "summary": "Ringkasan dalam bentuk paragraf yang padat...",
  "key_points": [
    "Poin penting 1",
    "Poin penting 2", 
    "Poin penting 3"
  ],
  "word_count": 150,
  "source": "gemini|local",
  "processing_time": "2.5s"
}
```

### 7. Quota Management System
- **Daily Limits**: Gemini (100 requests), Local (1000 requests)
- **Token Tracking**: Track token usage untuk Gemini API
- **User-based Quotas**: Per-user quota tracking
- **Graceful Degradation**: Otomatis fallback ke local saat quota habis

## Cara Penggunaan

### 1. Setup Environment
```bash
# Tambahkan ke .env
GEMINI_API_KEY=your-gemini-api-key-here
GEMINI_DAILY_LIMIT=100
LOCAL_DAILY_LIMIT=1000
```

### 2. Install Dependencies
```bash
composer install
php artisan migrate
```

### 3. Configure Queue (Optional)
```bash
# Untuk async processing
php artisan queue:work
```

### 4. Gunakan di Frontend
```blade
@include('components.summary-generator', ['article' => $article])
```

### 5. API Usage
```javascript
// Generate summary
fetch('/summaries', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        article_id: 123,
        max_words: 150,
        prefer_ai: true,
        async: false
    })
});

// Check quota
fetch('/summaries/quota')
    .then(response => response.json())
    .then(data => console.log(data.quota_status));
```

## Architecture Overview

```
User Request → SummaryController → SummarizationService
                                      ↓
                    ┌─────────────────┼─────────────────┐
                    ↓                 ↓                 ↓
            QuotaCheck        CacheCheck        ServiceSelection
                    ↓                 ↓                 ↓
            QuotaService      CacheService      Gemini/LocalService
                    ↓                 ↓                 ↓
            Database          Redis/Memory      API/Algorithm
```

## Performance Characteristics

- **Local Summarization**: < 1 second untuk 1000 kata
- **Gemini API**: 2-5 seconds tergantung panjang content
- **Cache Hit**: < 100ms
- **Async Processing**: Non-blocking untuk user experience

## Error Handling

- **API Failures**: Otomatis fallback ke local summarization
- **Quota Exceeded**: Graceful error messages dengan saran alternatif
- **Network Issues**: Retry mechanism dengan exponential backoff
- **Invalid Content**: Validation dan sanitization

## Security Features

- **Rate Limiting**: Per-user quota enforcement
- **Content Validation**: XSS prevention dan sanitization
- **API Key Protection**: Environment-based configuration
- **User Authorization**: Auth middleware untuk semua endpoints

## Monitoring & Analytics

- **Usage Tracking**: Track quota consumption per user
- **Performance Metrics**: Processing time dan success rates
- **Error Logging**: Comprehensive error tracking
- **Cache Statistics**: Hit/miss ratios untuk optimization

## Testing

Run test suite:
```bash
php artisan test --filter=SummarizationTest
```

Test coverage:
- Local summarization algorithm
- Gemini API integration
- Quota management
- Async processing
- Caching behavior
- Error handling

## Troubleshooting

### Gemini API Not Working
1. Check `GEMINI_API_KEY` di .env
2. Verify API key validity di Google AI Studio
3. Check network connectivity
4. Review error logs di `storage/logs/laravel.log`

### Quota Issues
1. Check current quota di `/summaries/quota`
2. Reset quota jika needed (admin function)
3. Monitor usage patterns

### Performance Issues
1. Enable caching: `php artisan config:cache`
2. Check queue workers untuk async processing
3. Monitor database performance
4. Consider Redis untuk caching

## Future Enhancements

- Multi-language support yang lebih baik
- Custom summarization models
- Collaborative summarization
- Export options (PDF, DOCX)
- Advanced analytics dashboard
- Mobile app integration