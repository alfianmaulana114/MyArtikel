# Notes System Documentation

## Overview

Sistem Notes yang komprehensif untuk aplikasi MyArtikel dengan fitur CRUD lengkap, real-time sync antar device, rich text editor, note anchoring ke paragraf, categorization, tagging, dan search functionality.

## Features

### ✅ Core Features

1. **Backend API CRUD Lengkap**
   - Create, Read, Update, Delete notes per artikel
   - Validasi input yang aman dengan NoteRequest
   - Response format JSON yang konsisten
   - Error handling yang robust

2. **Frontend Components**
   - NoteManager UI yang intuitif
   - NoteService untuk komunikasi dengan API
   - Integrasi dengan Clean Reader
   - Responsive design dengan Tailwind CSS

3. **Real-time Sync Antar Device**
   - Auto-sync setiap 30 detik
   - Offline queue untuk operasi saat offline
   - Conflict resolution untuk data yang bertentangan
   - Device identification system

4. **Rich Text Editor**
   - Quill.js integration
   - Support untuk formatting lengkap
   - Konversi otomatis ke plain text untuk search
   - Validasi konten yang aman

5. **Note Categorization & Tagging**
   - Kategori custom per user
   - Multiple tags per note (max 10)
   - Filter berdasarkan kategori dan tags
   - Auto-suggestion untuk kategori dan tags

6. **Search Functionality**
   - Full-text search di title, content, dan search_vector
   - Filter advanced dengan multiple criteria
   - Search performance optimization dengan indexing
   - Search suggestions dan autocomplete

7. **Note Anchoring ke Paragraf (Opsional MVP)**
   - Anchoring ke paragraph_index dan paragraph_id
   - Start/end offset untuk text selection
   - Visual indicators untuk anchored notes
   - Text selection tooltip untuk quick note creation

## Database Schema

### Notes Table Structure

```sql
CREATE TABLE notes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    content TEXT,
    content_json JSON,
    is_rich_text BOOLEAN DEFAULT FALSE,
    user_id BIGINT NOT NULL,
    article_id BIGINT,
    type ENUM('personal', 'research', 'draft') DEFAULT 'personal',
    is_private BOOLEAN DEFAULT FALSE,
    paragraph_index INT,
    paragraph_id VARCHAR(255),
    start_offset INT,
    end_offset INT,
    category VARCHAR(100),
    tags JSON,
    device_id VARCHAR(255),
    last_synced_at TIMESTAMP,
    sync_status ENUM('synced', 'pending', 'conflict', 'error') DEFAULT 'synced',
    search_vector TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    
    INDEX idx_user_id (user_id),
    INDEX idx_article_id (article_id),
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_device_id (device_id),
    INDEX idx_sync_status (sync_status),
    INDEX idx_paragraph_index (paragraph_index),
    INDEX idx_paragraph_id (paragraph_id),
    FULLTEXT idx_search (title, content, search_vector)
);
```

## API Endpoints

### Notes CRUD

```
GET    /notes                    - Get all notes (with filtering)
POST   /notes                    - Create new note
GET    /notes/{id}               - Get specific note
PUT    /notes/{id}               - Update note
DELETE /notes/{id}               - Delete note
```

### Sync & Search

```
POST   /notes/sync               - Sync notes across devices
GET    /notes/pending-sync       - Get pending sync notes
POST   /notes/search             - Search notes with filters
```

### Request Parameters

#### Filtering Parameters (GET /notes)
- `search`: Search query string
- `category`: Filter by category
- `type`: Filter by type (personal/research/draft)
- `tags`: Filter by tags (array)
- `article_id`: Filter by article
- `is_private`: Filter by privacy (boolean)
- `anchored`: Filter anchored notes only (boolean)
- `sort_by`: Sort field (created_at/updated_at/title/category/type)
- `sort_order`: Sort direction (asc/desc)
- `per_page`: Items per page (default: 20)

#### Search Parameters (POST /notes/search)
```json
{
  "query": "search term",
  "filters": {
    "category": "category name",
    "type": "personal",
    "tags": ["tag1", "tag2"],
    "article_id": 1
  }
}
```

#### Sync Parameters (POST /notes/sync)
```json
{
  "notes": [
    {
      "id": "note-id",
      "title": "Note Title",
      "content": "Note content",
      "content_json": { /* rich content */ },
      "article_id": 1,
      "last_modified": "2024-01-01T00:00:00Z"
    }
  ],
  "device_id": "device-identifier"
}
```

## Frontend Usage

### Basic Integration

```javascript
// Initialize NoteService
const noteService = new NoteService();

// Initialize NoteManager
const noteManager = new NoteManager({
    container: document.getElementById('note-container'),
    noteService: noteService
});

// Set article context
noteManager.filters.articleId = currentArticleId;
```

### Creating Notes

```javascript
// Create note from text selection
const noteData = {
    title: "My Note Title",
    content: "Note content here",
    type: "personal",
    category: "Research",
    tags: ["important", "review"],
    article_id: 1,
    is_rich_text: false
};

const note = await noteService.createNote(noteData);
```

### Searching Notes

```javascript
// Basic search
const results = await noteService.searchNotes("search query");

// Advanced search with filters
const results = await noteService.searchNotes("search query", {
    category: "Research",
    type: "personal",
    tags: ["important"]
});
```

### Syncing Notes

```javascript
// Manual sync
const syncResult = await noteService.syncNotes();

// Auto-sync is enabled by default every 30 seconds
// Can be disabled: noteService.setSyncEnabled(false);
```

## Security Features

### Input Validation
- XSS protection dengan DOMPurify
- SQL injection prevention dengan parameterized queries
- Content sanitization untuk rich text
- File upload validation (untuk gambar di rich editor)

### Access Control
- User-based note ownership
- Private note functionality
- CSRF token protection
- Rate limiting untuk API endpoints

### Data Protection
- Input sanitization untuk semua user input
- HTML content cleaning
- Script injection prevention
- Safe file handling untuk rich media

## Performance Optimization

### Database Indexing
- Index pada user_id, article_id untuk query performance
- Full-text index untuk search functionality
- Composite indexes untuk complex queries

### Caching Strategy
- Client-side caching untuk notes
- Search result caching
- Filter data caching
- Offline queue management

### Query Optimization
- Eager loading untuk relationships
- Pagination untuk large datasets
- Search result limiting (max 50 results)
- Optimized search dengan search_vector

## Configuration

### Environment Variables
```env
# Sync configuration
SYNC_INTERVAL=30
SYNC_ENABLED=true

# Cache configuration
CACHE_TTL=3600
CACHE_PREFIX=notes_

# Search configuration
SEARCH_LIMIT=50
SEARCH_MIN_LENGTH=2
```

### Model Constants
```php
// Note types
Note::TYPE_PERSONAL = 'personal'
Note::TYPE_RESEARCH = 'research'
Note::TYPE_DRAFT = 'draft'

// Sync statuses
Note::SYNC_SYNCED = 'synced'
Note::SYNC_PENDING = 'pending'
Note::SYNC_CONFLICT = 'conflict'
Note::SYNC_ERROR = 'error'
```

## Testing

### Unit Tests
```bash
php artisan test --filter=NoteTest
```

### API Testing
```bash
# Test create note
curl -X POST http://localhost:8000/notes \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{"title":"Test Note","content":"Test content","article_id":1}'

# Test search
curl -X POST http://localhost:8000/notes/search \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -d '{"query":"test","filters":{"type":"personal"}}'
```

### Frontend Testing
```javascript
// Test NoteService
const noteService = new NoteService();
const notes = await noteService.fetchNotes({ type: 'personal' });
console.log('Personal notes:', notes);

// Test sync
const syncStatus = await noteService.syncNotes();
console.log('Sync status:', syncStatus);
```

## Troubleshooting

### Common Issues

1. **Sync not working**
   - Check network connectivity
   - Verify device_id is set correctly
   - Check browser console for errors

2. **Search not returning results**
   - Verify search index is created
   - Check search_vector field is populated
   - Ensure search query meets minimum length

3. **Rich editor not loading**
   - Verify Quill.js is loaded correctly
   - Check for JavaScript errors
   - Ensure CSS is loaded

4. **Notes not anchoring to paragraphs**
   - Verify paragraph_index and paragraph_id are set
   - Check paragraph elements have proper IDs
   - Ensure text selection is working

### Debug Mode
```javascript
// Enable debug logging
noteService.debug = true;

// Check sync status
console.log('Sync status:', noteService.getSyncStatus());

// Check offline queue
console.log('Offline queue:', noteService.offlineQueue);
```

## Future Enhancements

### Planned Features
- [ ] Collaborative notes (sharing between users)
- [ ] Note templates dan snippets
- [ ] Advanced rich editor dengan tables, math
- [ ] Note versioning dan history
- [ ] Export notes ke PDF/Word
- [ ] Integration dengan external services (Google Drive, Dropbox)
- [ ] AI-powered note suggestions
- [ ] Voice-to-text untuk note creation

### Performance Improvements
- [ ] Redis caching untuk high-traffic scenarios
- [ ] Elasticsearch integration untuk advanced search
- [ ] CDN integration untuk static assets
- [ ] Database sharding untuk large datasets

## Contributing

### Development Setup
```bash
# Clone repository
git clone https://github.com/your-repo/myartikel.git
cd myartikel

# Install dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Start development server
npm run dev
```

### Code Standards
- Follow PSR-12 untuk PHP code style
- Use Laravel best practices
- Write comprehensive tests
- Document all public methods
- Use meaningful variable names

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue di GitHub repository
- Email: support@myartikel.com
- Documentation: https://docs.myartikel.com

---

**Last Updated:** April 2026  
**Version:** 1.0.0