# Advanced Search System API Documentation

## Overview
The Advanced Search System provides comprehensive search functionality for the MyArtikel library, supporting full-text search across articles, notes, and tags with intelligent ranking, filtering, and analytics.

## Base URL
```
https://your-domain.com/search
```

## Authentication
All search endpoints require authentication via Laravel's standard authentication system.

## Search Endpoints

### 1. Basic Search
**POST** `/search`

Perform a basic search across all content types.

#### Request Body
```json
{
  "query": "machine learning",
  "filters": {
    "status": "published",
    "tags": [1, 2, 3],
    "date_from": "2024-01-01",
    "date_to": "2024-12-31",
    "domain": "medium.com"
  },
  "per_page": 20,
  "page": 1,
  "type": "all"
}
```

#### Response
```json
{
  "results": [
    {
      "id": 1,
      "title": "Introduction to Machine Learning",
      "content": "Machine learning is a subset of artificial intelligence...",
      "excerpt": "...Machine learning is a subset of artificial intelligence...",
      "status": "published",
      "created_at": "2024-03-15T10:30:00Z",
      "search_score": 95.5,
      "result_type": "article",
      "tags": [
        {
          "id": 1,
          "name": "machine-learning",
          "color": "#3b82f6"
        }
      ],
      "view_count": 150,
      "notes_count": 5,
      "bookmarks_count": 12
    }
  ],
  "total_count": 45,
  "article_count": 30,
  "note_count": 15,
  "current_page": 1,
  "per_page": 20,
  "total_pages": 3,
  "query": "machine learning",
  "keywords": ["machine", "learning"],
  "filters": {
    "status": "published"
  },
  "search_metadata": {
    "execution_time": 125,
    "cache_hit": false,
    "query_type": "phrase",
    "suggestions_available": true
  }
}
```

### 2. Quick Search
**GET** `/search/quick`

Get instant search results with minimal processing.

#### Query Parameters
- `query` (required): Search query string
- `limit` (optional): Maximum results (default: 5, max: 10)

#### Response
```json
{
  "query": "machine learning",
  "results": [
    {
      "id": 1,
      "title": "Introduction to Machine Learning",
      "excerpt": "Machine learning is a subset of artificial intelligence...",
      "search_score": 95.5,
      "result_type": "article"
    }
  ],
  "total_count": 45,
  "quick_search": true
}
```

### 3. Advanced Search
**POST** `/search/advanced`

Perform advanced search with multiple criteria.

#### Request Body
```json
{
  "query": "machine learning",
  "title": "Introduction",
  "content": "neural networks",
  "tags": [1, 2],
  "status": "published",
  "date_from": "2024-01-01",
  "date_to": "2024-12-31",
  "domain": "medium.com",
  "has_notes": true,
  "has_bookmarks": false,
  "sort_by": "relevance",
  "sort_order": "desc",
  "per_page": 20,
  "page": 1
}
```

#### Response
```json
{
  "advanced_search": true,
  "filters_applied": {
    "status": "published",
    "has_notes": true
  },
  "results": {
    "results": [...],
    "total_count": 25,
    "current_page": 1,
    "per_page": 20,
    "total_pages": 2
  }
}
```

### 4. Search Suggestions
**GET** `/search/suggestions`

Get search suggestions based on partial query.

#### Query Parameters
- `query` (required): Partial search query
- `limit` (optional): Maximum suggestions (default: 10, max: 20)

#### Response
```json
{
  "query": "mach",
  "suggestions": [
    {
      "suggestion": "machine learning",
      "type": "query",
      "popularity": 125,
      "metadata": null
    },
    {
      "suggestion": "machine learning algorithms",
      "type": "query",
      "popularity": 89,
      "metadata": null
    },
    {
      "suggestion": "machine-learning",
      "type": "tag",
      "popularity": 45,
      "metadata": {
        "color": "#3b82f6",
        "id": 1
      }
    }
  ],
  "count": 3
}
```

### 5. Search History
**GET** `/search/history`

Get user's search history.

#### Query Parameters
- `limit` (optional): Maximum history items (default: 20)

#### Response
```json
{
  "history": [
    {
      "id": 1,
      "query": "machine learning",
      "filters": {
        "status": "published"
      },
      "results_count": 45,
      "clicked_result": true,
      "created_at": "2024-03-15T10:30:00Z"
    }
  ],
  "count": 15
}
```

### 6. Search Analytics
**GET** `/search/analytics`

Get search analytics data.

#### Query Parameters
- `days` (optional): Analytics period in days (default: 30, max: 90)

#### Response
```json
{
  "analytics": {
    "total_searches": 1250,
    "average_results": 28.5,
    "click_through_rate": 45.2,
    "top_queries": [
      {
        "query": "machine learning",
        "count": 89
      },
      {
        "query": "artificial intelligence",
        "count": 67
      }
    ],
    "search_trends": [
      {
        "date": "2024-03-01",
        "count": 45
      },
      {
        "date": "2024-03-02",
        "count": 52
      }
    ]
  },
  "period": "last 30 days"
}
```

### 7. Record Click
**POST** `/search/click`

Record a search result click for analytics.

#### Request Body
```json
{
  "query": "machine learning",
  "result_id": 1,
  "result_type": "article",
  "position": 2
}
```

#### Response
```json
{
  "message": "Click recorded successfully"
}
```

## Search Filters

### Available Filters

| Filter | Type | Description | Example |
|--------|------|-------------|---------|
| `status` | string | Article status: `draft`, `published`, `archived` | `"published"` |
| `tags` | array | Array of tag IDs | `[1, 2, 3]` |
| `date_from` | string | Start date (YYYY-MM-DD) | `"2024-01-01"` |
| `date_to` | string | End date (YYYY-MM-DD) | `"2024-12-31"` |
| `domain` | string | Source domain | `"medium.com"` |
| `article_id` | integer | Specific article ID | `123` |
| `has_notes` | boolean | Articles with notes | `true` |
| `has_bookmarks` | boolean | Bookmarked articles | `true` |
| `sort_by` | string | Sort field: `relevance`, `date`, `title`, `popularity` | `"relevance"` |
| `sort_order` | string | Sort order: `asc`, `desc` | `"desc"` |

### Advanced Search Criteria

| Field | Type | Description |
|-------|------|-------------|
| `query` | string | General search query |
| `title` | string | Search in article titles |
| `content` | string | Search in article content |
| `tags` | array | Filter by specific tags |
| `status` | string | Filter by article status |
| `date_from` | string | Filter by creation date |
| `date_to` | string | Filter by creation date |
| `domain` | string | Filter by source domain |
| `has_notes` | boolean | Articles with notes |
| `has_bookmarks` | boolean | Bookmarked articles |

## Search Result Types

### Article Results
```json
{
  "id": 1,
  "title": "Article Title",
  "content": "Article content...",
  "excerpt": "Content excerpt...",
  "status": "published",
  "created_at": "2024-03-15T10:30:00Z",
  "search_score": 95.5,
  "result_type": "article",
  "tags": [...],
  "view_count": 150,
  "notes_count": 5,
  "bookmarks_count": 12
}
```

### Note Results
```json
{
  "id": 1,
  "content": "Note content...",
  "created_at": "2024-03-15T10:30:00Z",
  "search_score": 45.2,
  "result_type": "note",
  "article": {
    "id": 1,
    "title": "Related Article Title"
  }
}
```

## Search Scoring

The search system uses a sophisticated scoring algorithm that considers:

1. **Title Matches** (highest weight)
   - Exact title match: +15 points
   - Partial title match: +10 points
   - Keyword in title: +2 points per keyword

2. **Content Matches**
   - Query in content: +3 points
   - Keyword frequency: +0.5 points per occurrence

3. **Additional Factors**
   - Tag matches: +1.5 points per tag
   - Recency bonus: +1 point (recent), +0.5 points (within 30 days)
   - Popularity bonus: +0.01 per view, +0.1 per note, +0.2 per bookmark

## Performance Features

### Caching
- Search results are cached for 5 minutes
- Suggestions are cached for 30 seconds
- Quick search results are cached for 1 minute

### Indexing
- Full-text search indexes on articles and notes
- Optimized database queries with proper indexing
- Search vector generation for PostgreSQL

### Optimization
- Debounced search input (300ms delay)
- Pagination support (max 100 results per page)
- Efficient query building and execution

## Error Handling

### Common Error Responses

#### 400 Bad Request
```json
{
  "error": "Invalid search parameters",
  "message": "The 'query' field must be at least 2 characters long"
}
```

#### 401 Unauthorized
```json
{
  "error": "Authentication required",
  "message": "Please log in to perform searches"
}
```

#### 500 Internal Server Error
```json
{
  "error": "Search failed",
  "message": "An unexpected error occurred. Please try again."
}
```

## Rate Limiting

- Standard search: 60 requests per minute
- Quick search: 120 requests per minute
- Suggestions: 180 requests per minute

## Usage Examples

### JavaScript/Axios
```javascript
// Basic search
const response = await axios.post('/search', {
  query: 'machine learning',
  filters: { status: 'published' },
  per_page: 20
});

// Get suggestions
const suggestions = await axios.get('/search/suggestions', {
  params: { query: 'mach', limit: 10 }
});
```

### jQuery
```javascript
// Advanced search
$.ajax({
  url: '/search/advanced',
  method: 'POST',
  data: JSON.stringify({
    query: 'AI',
    tags: [1, 2],
    sort_by: 'popularity'
  }),
  contentType: 'application/json',
  success: function(data) {
    console.log('Search results:', data);
  }
});
```

### Fetch API
```javascript
// Record click
fetch('/search/click', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    query: 'machine learning',
    result_id: 123,
    result_type: 'article',
    position: 2
  })
});
```

## Integration Guide

### 1. Include Required Files
```html
<!-- CSS for search components -->
<link rel="stylesheet" href="/css/search.css">

<!-- JavaScript libraries -->
<script src="/js/search/SearchService.js"></script>
<script src="/js/search/AdvancedSearch.js"></script>
```

### 2. Initialize Search Component
```javascript
const search = new AdvancedSearch({
  container: '#search-container',
  resultsContainer: '#search-results',
  enableAutocomplete: true,
  enableHistory: true,
  enableFilters: true,
  onSearch: function(results, query) {
    console.log(`Found ${results.total_count} results for: ${query}`);
  },
  onResultClick: function(result, query) {
    // Handle result navigation
    window.location.href = `/articles/${result.id}`;
  }
});
```

### 3. Handle Search Results
```javascript
// Custom result rendering
search.onSearch = function(results, query) {
  const resultsContainer = document.getElementById('search-results');
  resultsContainer.innerHTML = '';
  
  results.results.forEach(result => {
    const element = createResultElement(result);
    resultsContainer.appendChild(element);
  });
};
```

## Performance Optimization

### Database Indexes
- Full-text search indexes on `articles` and `notes` tables
- Composite indexes on frequently queried columns
- Search vector generation for PostgreSQL

### Caching Strategy
- Redis-based result caching
- Browser-side caching for suggestions
- CDN caching for static assets

### Query Optimization
- Efficient SQL queries with proper joins
- Pagination to limit result sets
- Selective field loading

## Monitoring and Analytics

### Key Metrics
- Search volume and frequency
- Click-through rates
- Average response time
- Cache hit rates
- Error rates

### Monitoring Endpoints
- `/search/analytics` - Search analytics data
- `/search/history` - User search history
- Application logs for error tracking

## Troubleshooting

### Common Issues

1. **Slow Search Performance**
   - Check database indexes
   - Verify cache configuration
   - Monitor query execution plans

2. **No Search Results**
   - Verify search index is populated
   - Check content status (draft vs published)
   - Review filter parameters

3. **Autocomplete Not Working**
   - Check suggestion API endpoint
   - Verify minimum character requirements
   - Review network connectivity

### Debug Information
Enable debug mode to see detailed search information:
```javascript
const search = new AdvancedSearch({
  debug: true,
  onSearch: function(results, query) {
    console.log('Search metadata:', results.search_metadata);
    console.log('Execution time:', results.search_metadata.execution_time);
    console.log('Cache hit:', results.search_metadata.cache_hit);
  }
});
```