# MyArtikel Data Sync System

A comprehensive bidirectional data synchronization system for MyArtikel with offline support, real-time sync, and conflict resolution.

## Features

### 1. Device Registration & Management
- Multi-device support with device-specific tokens
- Device limit management (max 5 devices per user)
- Device heartbeat tracking
- Automatic device cleanup for inactive devices

### 2. Bidirectional Sync
- Synchronizes articles, notes, tags, and bookmarks
- Incremental sync to minimize data transfer
- Batch processing for large datasets
- Sync queue management with retry logic

### 3. Conflict Resolution
- Multiple resolution strategies:
  - Last Write Wins (default)
  - Manual Resolution
  - Merge (intelligent merging based on data type)
  - Server/Client Wins
- Conflict detection using data hashing
- Conflict history tracking

### 4. Sync Status Tracking
- Real-time sync progress monitoring
- Sync history with performance metrics
- Detailed sync statistics
- Sync job orchestration

### 5. Offline Support
- IndexedDB for local data storage
- Offline-first architecture
- Automatic sync when connection restored
- Local search functionality
- Data export/import for backup

### 6. Real-time Sync
- WebSocket-based real-time synchronization
- Automatic fallback to polling
- Connection status monitoring
- Multi-device real-time updates

## Architecture

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend API     │    │   Database      │
│   (Client)      │◄──►│   (Node.js)       │◄──►│   (PostgreSQL) │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│Offline Manager  │    │Sync Orchestrator  │    │   Sync Records  │
│IndexedDB        │    │WebSocket Manager  │    │   Device Data   │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## Installation

### Backend Setup

1. **Install dependencies:**
```bash
cd backend
npm install
```

2. **Setup PostgreSQL:**
```bash
# Create database
createdb myartikel

# Copy environment file
cp .env.example .env

# Edit .env with your database credentials
```

3. **Initialize database:**
```bash
npm run db:migrate
```

4. **Start the server:**
```bash
# Development
npm run dev

# Production
npm start
```

### Frontend Integration

1. **Include the sync scripts in your HTML:**
```html
<script type="module" src="./js/sync/LocalStorageManager.js"></script>
<script type="module" src="./js/sync/OfflineSyncManager.js"></script>
<script type="module" src="./js/sync/RealtimeSyncManager.js"></script>
```

2. **Initialize the sync system:**
```javascript
import offlineSyncManager from './js/sync/OfflineSyncManager.js';
import realtimeSyncManager from './js/sync/RealtimeSyncManager.js';

// Initialize sync managers
realtimeSyncManager.connect();

// Listen for sync events
offlineSyncManager.onSyncStatusChange((status) => {
    console.log('Sync status:', status);
});
```

## API Endpoints

### Device Management
- `POST /api/devices/register` - Register new device
- `GET /api/devices` - Get user's devices
- `DELETE /api/devices/:deviceId` - Deactivate device
- `POST /api/devices/:deviceId/heartbeat` - Update device heartbeat

### Sync Operations
- `POST /api/sync` - Perform sync operation
- `GET /api/sync/status` - Get sync status
- `GET /api/sync/pending` - Get pending changes
- `POST /api/sync/conflicts/:conflictId/resolve` - Resolve conflict

### Sync Status
- `GET /api/sync-status/status` - Get current sync status
- `GET /api/sync-status/history` - Get sync history
- `GET /api/sync-status/stats` - Get sync statistics
- `POST /api/sync-status/cancel` - Cancel current sync

### Orchestrated Sync
- `POST /api/sync/orchestrated` - Queue sync job
- `GET /api/sync/jobs/:jobId` - Get job status
- `GET /api/sync/jobs/active` - Get active jobs
- `POST /api/sync/jobs/:jobId/cancel` - Cancel job

## Usage Examples

### Saving Data Offline
```javascript
const article = {
    id: 'article_123',
    title: 'My Article',
    content: 'Article content...',
    tags: ['tech', 'tutorial'],
    createdAt: new Date().toISOString(),
    lastModified: new Date().toISOString()
};

// Save offline and queue for sync
await offlineSyncManager.saveDataOffline('articles', article);
```

### Manual Sync Trigger
```javascript
// Trigger sync when online
await offlineSyncManager.triggerSync();

// Force sync with server
await realtimeSyncManager.requestSync({
    articles: localArticles,
    notes: localNotes,
    lastSyncTimestamp: lastSyncTime
});
```

### Conflict Resolution
```javascript
// Get unresolved conflicts
const conflicts = await fetch('/api/sync/pending').then(r => r.json());

// Resolve conflict
await fetch('/api/sync/conflicts/123/resolve', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        resolution: 'merge' // or 'local', 'remote'
    })
});
```

### Real-time Updates
```javascript
// Listen for real-time data changes
realtimeSyncManager.onSyncEvent((event) => {
    switch (event.type) {
        case 'data_changed':
            updateLocalData(event.data);
            break;
        case 'conflict_resolved':
            handleConflictResolution(event.resolution);
            break;
        case 'sync_completed':
            refreshUI();
            break;
    }
});
```

## Configuration

### Environment Variables
```bash
# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=myartikel
DB_USER=postgres
DB_PASSWORD=password

# JWT
JWT_SECRET=your-secret-key
JWT_EXPIRES_IN=7d

# Sync Settings
SYNC_BATCH_SIZE=100
SYNC_MAX_RETRY_ATTEMPTS=3
SYNC_CONFLICT_RESOLUTION=last_write_wins

# WebSocket
WS_HEARTBEAT_INTERVAL=30000
WS_MAX_CONNECTIONS_PER_USER=5

# Rate Limiting
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100
```

### Conflict Resolution Strategies
- `last_write_wins` - Uses timestamp to determine winner
- `manual_resolution` - Requires manual intervention
- `merge` - Intelligently merges conflicting data
- `server_wins` - Always uses server version
- `client_wins` - Always uses client version

## Performance Considerations

### Database Optimization
- Indexed columns for fast queries
- Batch operations for bulk sync
- Connection pooling for high concurrency
- Regular cleanup of old sync records

### Network Optimization
- Compression for large payloads
- Incremental sync to minimize data transfer
- WebSocket for real-time efficiency
- Automatic retry with exponential backoff

### Client-Side Optimization
- IndexedDB for fast local access
- Background sync processing
- Efficient conflict detection
- Memory management for large datasets

## Security Features

### Authentication
- JWT-based authentication
- Device-specific tokens
- Token expiration and refresh
- Rate limiting per user/device

### Data Protection
- HTTPS enforcement
- Input validation and sanitization
- SQL injection prevention
- XSS protection

### Privacy
- User data isolation
- Device authorization
- Audit logging
- GDPR compliance features

## Monitoring & Debugging

### Health Checks
```bash
# System health
curl http://localhost:3000/health

# Connection statistics
curl http://localhost:3000/api/sync/connections

# Sync statistics
curl http://localhost:3000/api/sync/statistics
```

### Logging
- Structured logging with Winston
- Sync operation tracking
- Error reporting
- Performance metrics

### Debugging Tools
- Sync demo interface at `/sync-demo.html`
- Real-time connection monitoring
- Sync history visualization
- Conflict resolution interface

## Troubleshooting

### Common Issues

1. **Sync not working offline**
   - Check IndexedDB support
   - Verify local storage quota
   - Clear browser cache

2. **WebSocket connection failing**
   - Check firewall settings
   - Verify WebSocket support
   - Fallback to polling mode

3. **Conflicts not resolving**
   - Check conflict resolution strategy
   - Verify user permissions
   - Manual conflict resolution

4. **Performance issues**
   - Optimize sync batch size
   - Check database indexes
   - Monitor connection pool usage

### Debug Mode
Enable debug logging:
```bash
DEBUG=myartikel:* npm run dev
```

## Contributing

1. Fork the repository
2. Create feature branch
3. Add tests for new features
4. Submit pull request

## License

MIT License - see LICENSE file for details