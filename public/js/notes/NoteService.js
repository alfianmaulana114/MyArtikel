/**
 * NoteService - Service untuk mengelola notes dengan fitur CRUD, sync, dan search
 */
class NoteService {
    constructor() {
        this.baseUrl = '/notes';
        this.deviceId = this.generateDeviceId();
        this.syncInterval = null;
        this.syncEnabled = true;
        this.offlineQueue = [];
        this.cache = new Map();
        
        this.initializeService();
    }
    
    /**
     * Generate unique device ID
     */
    generateDeviceId() {
        const stored = localStorage.getItem('device_id');
        if (stored) return stored;
        
        const deviceId = 'device_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('device_id', deviceId);
        return deviceId;
    }
    
    /**
     * Initialize service dengan setup sync dan event listeners
     */
    initializeService() {
        this.setupNetworkListeners();
        this.startAutoSync();
        this.loadOfflineQueue();
    }
    
    /**
     * Setup network event listeners
     */
    setupNetworkListeners() {
        window.addEventListener('online', () => {
            console.log('Network online - processing offline queue');
            this.processOfflineQueue();
        });
        
        window.addEventListener('offline', () => {
            console.log('Network offline - queueing operations');
        });
        
        // Visibility change handler
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.syncPendingNotes();
            }
        });
    }
    
    /**
     * Start auto-sync interval
     */
    startAutoSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
        
        this.syncInterval = setInterval(() => {
            if (this.syncEnabled && navigator.onLine) {
                this.syncPendingNotes();
            }
        }, 30000); // Sync every 30 seconds
    }
    
    /**
     * Stop auto-sync
     */
    stopAutoSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
    }
    
    /**
     * Enable/disable sync
     */
    setSyncEnabled(enabled) {
        this.syncEnabled = enabled;
        if (enabled) {
            this.startAutoSync();
        } else {
            this.stopAutoSync();
        }
    }
    
    /**
     * Get headers dengan device ID
     */
    getHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-Device-ID': this.deviceId,
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        };
    }
    
    /**
     * Fetch all notes dengan filtering
     */
    async fetchNotes(filters = {}) {
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const url = `${this.baseUrl}${queryParams ? '?' + queryParams : ''}`;
            
            const response = await fetch(url, {
                headers: this.getHeaders(),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Cache the results
                data.data.forEach(note => {
                    this.cache.set(note.id, note);
                });
                
                return {
                    notes: data.data,
                    pagination: data.pagination,
                    filters: data.filters,
                };
            } else {
                throw new Error(data.message || 'Failed to fetch notes');
            }
        } catch (error) {
            console.error('Error fetching notes:', error);
            
            // Return cached data if available
            const cachedNotes = Array.from(this.cache.values());
            if (cachedNotes.length > 0) {
                return {
                    notes: cachedNotes,
                    pagination: { total: cachedNotes.length },
                    filters: {},
                    fromCache: true,
                };
            }
            
            throw error;
        }
    }
    
    /**
     * Fetch single note
     */
    async fetchNote(id) {
        try {
            // Check cache first
            if (this.cache.has(id)) {
                return this.cache.get(id);
            }
            
            const response = await fetch(`${this.baseUrl}/${id}`, {
                headers: this.getHeaders(),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.cache.set(id, data.data);
                return data.data;
            } else {
                throw new Error(data.message || 'Failed to fetch note');
            }
        } catch (error) {
            console.error('Error fetching note:', error);
            throw error;
        }
    }
    
    /**
     * Create new note
     */
    async createNote(noteData) {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(noteData),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.cache.set(data.data.id, data.data);
                return data.data;
            } else {
                throw new Error(data.message || 'Failed to create note');
            }
        } catch (error) {
            console.error('Error creating note:', error);
            
            // Queue for offline processing
            if (!navigator.onLine) {
                this.queueOfflineOperation('create', noteData);
            }
            
            throw error;
        }
    }
    
    /**
     * Update existing note
     */
    async updateNote(id, noteData) {
        try {
            const response = await fetch(`${this.baseUrl}/${id}`, {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify(noteData),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.cache.set(id, data.data);
                return data.data;
            } else {
                throw new Error(data.message || 'Failed to update note');
            }
        } catch (error) {
            console.error('Error updating note:', error);
            
            // Queue for offline processing
            if (!navigator.onLine) {
                this.queueOfflineOperation('update', { id, ...noteData });
            }
            
            throw error;
        }
    }
    
    /**
     * Delete note
     */
    async deleteNote(id) {
        try {
            const response = await fetch(`${this.baseUrl}/${id}`, {
                method: 'DELETE',
                headers: this.getHeaders(),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.cache.delete(id);
                return true;
            } else {
                throw new Error(data.message || 'Failed to delete note');
            }
        } catch (error) {
            console.error('Error deleting note:', error);
            
            // Queue for offline processing
            if (!navigator.onLine) {
                this.queueOfflineOperation('delete', { id });
            }
            
            throw error;
        }
    }
    
    /**
     * Search notes
     */
    async searchNotes(query, filters = {}) {
        try {
            const searchData = {
                query,
                filters,
            };
            
            const response = await fetch(`${this.baseUrl}/search`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify(searchData),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Cache search results
                data.data.forEach(note => {
                    this.cache.set(note.id, note);
                });
                
                return {
                    notes: data.data,
                    searchInfo: data.search_info,
                };
            } else {
                throw new Error(data.message || 'Search failed');
            }
        } catch (error) {
            console.error('Error searching notes:', error);
            throw error;
        }
    }
    
    /**
     * Sync notes across devices
     */
    async syncNotes(notes = null) {
        if (!navigator.onLine) {
            console.log('Cannot sync: offline');
            return { success: false, message: 'Cannot sync while offline' };
        }
        
        try {
            // Get notes to sync
            const notesToSync = notes || this.getLocalNotesForSync();
            
            if (notesToSync.length === 0) {
                return { success: true, message: 'No notes to sync' };
            }
            
            const response = await fetch(`${this.baseUrl}/sync`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    notes: notesToSync,
                    device_id: this.deviceId,
                }),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Update cache with synced notes
                data.data.synced.forEach(note => {
                    this.cache.set(note.id, note);
                });
                
                // Handle conflicts
                if (data.data.conflicts && data.data.conflicts.length > 0) {
                    this.handleSyncConflicts(data.data.conflicts);
                }
                
                // Update local storage
                this.updateLocalNotes(data.data);
                
                return {
                    success: true,
                    message: 'Sync completed',
                    data: data.data,
                };
            } else {
                throw new Error(data.message || 'Sync failed');
            }
        } catch (error) {
            console.error('Error syncing notes:', error);
            throw error;
        }
    }
    
    /**
     * Get pending sync notes
     */
    async getPendingSyncNotes() {
        try {
            const response = await fetch(`${this.baseUrl}/pending-sync`, {
                headers: this.getHeaders(),
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                return data.data;
            } else {
                throw new Error(data.message || 'Failed to get pending sync notes');
            }
        } catch (error) {
            console.error('Error getting pending sync notes:', error);
            throw error;
        }
    }
    
    /**
     * Sync pending notes
     */
    async syncPendingNotes() {
        try {
            const pendingNotes = await this.getPendingSyncNotes();
            
            if (pendingNotes.length > 0) {
                await this.syncNotes(pendingNotes);
            }
            
            // Process offline queue
            await this.processOfflineQueue();
            
        } catch (error) {
            console.error('Error syncing pending notes:', error);
        }
    }
    
    /**
     * Handle sync conflicts
     */
    handleSyncConflicts(conflicts) {
        console.log('Sync conflicts detected:', conflicts);
        
        // Emit custom event for UI to handle
        window.dispatchEvent(new CustomEvent('notes-sync-conflicts', {
            detail: { conflicts }
        }));
    }
    
    /**
     * Queue offline operation
     */
    queueOfflineOperation(type, data) {
        const operation = {
            id: Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            type,
            data,
            timestamp: new Date().toISOString(),
        };
        
        this.offlineQueue.push(operation);
        this.saveOfflineQueue();
        
        console.log('Queued offline operation:', operation);
    }
    
    /**
     * Process offline queue
     */
    async processOfflineQueue() {
        if (!navigator.onLine || this.offlineQueue.length === 0) {
            return;
        }
        
        console.log('Processing offline queue:', this.offlineQueue.length, 'operations');
        
        const failedOperations = [];
        
        for (const operation of this.offlineQueue) {
            try {
                switch (operation.type) {
                    case 'create':
                        await this.createNote(operation.data);
                        break;
                    case 'update':
                        await this.updateNote(operation.data.id, operation.data);
                        break;
                    case 'delete':
                        await this.deleteNote(operation.data.id);
                        break;
                }
            } catch (error) {
                console.error('Failed to process offline operation:', operation, error);
                failedOperations.push(operation);
            }
        }
        
        this.offlineQueue = failedOperations;
        this.saveOfflineQueue();
        
        console.log('Offline queue processed. Failed operations:', failedOperations.length);
    }
    
    /**
     * Save offline queue to local storage
     */
    saveOfflineQueue() {
        localStorage.setItem('notes_offline_queue', JSON.stringify(this.offlineQueue));
    }
    
    /**
     * Load offline queue from local storage
     */
    loadOfflineQueue() {
        try {
            const stored = localStorage.getItem('notes_offline_queue');
            this.offlineQueue = stored ? JSON.parse(stored) : [];
        } catch (error) {
            console.error('Error loading offline queue:', error);
            this.offlineQueue = [];
        }
    }
    
    /**
     * Get local notes for sync
     */
    getLocalNotesForSync() {
        // This would typically come from your local storage or state management
        // For now, return empty array - implement based on your app architecture
        return [];
    }
    
    /**
     * Update local notes after sync
     */
    updateLocalNotes(syncData) {
        // Update your local storage or state management
        // This depends on your app architecture
        console.log('Local notes updated with sync data:', syncData);
    }
    
    /**
     * Get cached notes
     */
    getCachedNotes() {
        return Array.from(this.cache.values());
    }
    
    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
    }
    
    /**
     * Get sync status
     */
    getSyncStatus() {
        return {
            enabled: this.syncEnabled,
            interval: this.syncInterval ? 'active' : 'inactive',
            offlineQueueLength: this.offlineQueue.length,
            cacheSize: this.cache.size,
            deviceId: this.deviceId,
            online: navigator.onLine,
        };
    }
    
    /**
     * Destroy service
     */
    destroy() {
        this.stopAutoSync();
        this.clearCache();
        this.saveOfflineQueue();
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NoteService;
} else {
    window.NoteService = NoteService;
}