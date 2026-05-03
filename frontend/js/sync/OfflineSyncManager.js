import localStorageManager from "./LocalStorageManager.js";

class OfflineSyncManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.syncInProgress = false;
        this.syncQueue = [];
        this.syncCallbacks = new Map();

        this.setupEventListeners();
        this.processSyncQueue();
    }

    setupEventListeners() {
        window.addEventListener("online", () => {
            this.isOnline = true;
            this.triggerSync();
        });

        window.addEventListener("offline", () => {
            this.isOnline = false;
        });

        // Visibility change - sync when app becomes visible
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden && this.isOnline) {
                this.triggerSync();
            }
        });

        // Periodic sync every 5 minutes when online
        setInterval(
            () => {
                if (this.isOnline && !this.syncInProgress) {
                    this.triggerSync();
                }
            },
            5 * 60 * 1000,
        );
    }

    // Save data locally and queue for sync
    async saveDataOffline(storeName, item) {
        try {
            // Save to local storage
            await localStorageManager.saveItem(storeName, item);

            // Add to sync queue
            await localStorageManager.addToSyncQueue(
                storeName,
                item.id,
                "update",
                item,
            );

            // Notify listeners
            this.notifySyncStatus("pending", storeName, item.id);

            return { success: true, synced: false };
        } catch (error) {
            console.error("Failed to save data offline:", error);
            return { success: false, error: error.message };
        }
    }

    // Delete data locally and queue for sync
    async deleteDataOffline(storeName, id) {
        try {
            // Soft delete locally
            await localStorageManager.softDelete(storeName, id);

            // Add to sync queue
            await localStorageManager.addToSyncQueue(storeName, id, "delete", {
                id,
            });

            // Notify listeners
            this.notifySyncStatus("pending", storeName, id);

            return { success: true, synced: false };
        } catch (error) {
            console.error("Failed to delete data offline:", error);
            return { success: false, error: error.message };
        }
    }

    // Trigger sync when online
    async triggerSync() {
        if (!this.isOnline || this.syncInProgress) {
            return;
        }

        this.syncInProgress = true;
        this.notifySyncStatus("syncing");

        try {
            await this.performSync();
            this.notifySyncStatus("completed");
        } catch (error) {
            console.error("Sync failed:", error);
            this.notifySyncStatus("failed", null, null, error);
        } finally {
            this.syncInProgress = false;
        }
    }

    // Perform actual sync
    async performSync() {
        const syncQueue =
            await localStorageManager.getSyncQueueItems("pending");

        if (syncQueue.length === 0) {
            return;
        }

        // Group by entity type for batch processing
        const groupedQueue = this.groupByEntityType(syncQueue);

        for (const [entityType, items] of Object.entries(groupedQueue)) {
            await this.syncEntityType(entityType, items);
        }
    }

    groupByEntityType(items) {
        return items.reduce((groups, item) => {
            if (!groups[item.entityType]) {
                groups[item.entityType] = [];
            }
            groups[item.entityType].push(item);
            return groups;
        }, {});
    }

    async syncEntityType(entityType, items) {
        try {
            // Prepare sync data
            const syncData = {
                lastSyncTimestamp: await this.getLastSyncTimestamp(),
                [entityType]: items.map((item) => item.data),
            };

            // Make API call
            const response = await fetch("/api/sync", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${this.getAuthToken()}`,
                },
                body: JSON.stringify({
                    deviceId: this.getDeviceId(),
                    ...syncData,
                }),
            });

            if (!response.ok) {
                throw new Error(`Sync failed: ${response.statusText}`);
            }

            const result = await response.json();

            // Process sync results
            await this.processSyncResults(result.data.syncResults);

            // Remove successfully synced items from queue
            for (const item of items) {
                await localStorageManager.removeFromSyncQueue(item.id);
                await localStorageManager.updateSyncQueueItem(item.id, {
                    status: "completed",
                    completedAt: new Date().toISOString(),
                });
            }

            // Update last sync timestamp
            await this.updateLastSyncTimestamp();
        } catch (error) {
            console.error(`Failed to sync ${entityType}:`, error);

            // Update retry count and mark as failed if max retries reached
            for (const item of items) {
                const retryCount = (item.retryCount || 0) + 1;

                if (retryCount >= 3) {
                    await localStorageManager.updateSyncQueueItem(item.id, {
                        status: "failed",
                        lastError: error.message,
                        retryCount,
                    });
                } else {
                    await localStorageManager.updateSyncQueueItem(item.id, {
                        retryCount,
                        lastError: error.message,
                    });
                }
            }

            throw error;
        }
    }

    async processSyncResults(syncResults) {
        // Process remote changes
        for (const [entityType, result] of Object.entries(syncResults)) {
            if (result.remoteChanges && result.remoteChanges.length > 0) {
                await this.applyRemoteChanges(entityType, result.remoteChanges);
            }
        }
    }

    async applyRemoteChanges(entityType, changes) {
        for (const change of changes) {
            switch (change.action) {
                case "create":
                case "update":
                    await localStorageManager.saveItem(entityType, change.data);
                    break;
                case "delete":
                    await localStorageManager.deleteItem(
                        entityType,
                        change.entityId,
                    );
                    break;
            }
        }
    }

    // Get data for offline use
    async getOfflineData(storeName, options = {}) {
        try {
            const items = await localStorageManager.getAllItems(
                storeName,
                options,
            );
            return items.filter((item) => !item.isDeleted);
        } catch (error) {
            console.error(`Failed to get offline ${storeName}:`, error);
            return [];
        }
    }

    // Search offline data
    async searchOfflineData(query, storeName = null) {
        const results = {};
        const stores = storeName
            ? [storeName]
            : ["articles", "notes", "bookmarks"];

        for (const store of stores) {
            const items = await this.getOfflineData(store);
            results[store] = items.filter((item) => {
                const searchableText = JSON.stringify(item).toLowerCase();
                return searchableText.includes(query.toLowerCase());
            });
        }

        return results;
    }

    // Sync status management
    getSyncStatus() {
        return {
            isOnline: this.isOnline,
            isSyncing: this.syncInProgress,
            lastSync: localStorage.getItem("lastSyncTimestamp"),
            pendingItems: this.getPendingItemsCount(),
        };
    }

    async getPendingItemsCount() {
        const pendingItems =
            await localStorageManager.getSyncQueueItems("pending");
        return pendingItems.length;
    }

    async getLastSyncTimestamp() {
        return (
            localStorage.getItem("lastSyncTimestamp") ||
            new Date(0).toISOString()
        );
    }

    async updateLastSyncTimestamp() {
        localStorage.setItem("lastSyncTimestamp", new Date().toISOString());
    }

    // Event handling
    onSyncStatusChange(callback) {
        const id = Date.now().toString();
        this.syncCallbacks.set(id, callback);
        return () => this.syncCallbacks.delete(id);
    }

    notifySyncStatus(status, entityType = null, entityId = null, error = null) {
        const syncStatus = this.getSyncStatus();
        const event = {
            status,
            entityType,
            entityId,
            error,
            timestamp: new Date().toISOString(),
            ...syncStatus,
        };

        this.syncCallbacks.forEach((callback) => {
            try {
                callback(event);
            } catch (error) {
                console.error("Sync callback error:", error);
            }
        });
    }

    // Utility methods
    getAuthToken() {
        return localStorage.getItem("authToken") || "";
    }

    getDeviceId() {
        let deviceId = localStorage.getItem("deviceId");
        if (!deviceId) {
            deviceId = "web-" + Date.now().toString();
            localStorage.setItem("deviceId", deviceId);
        }
        return deviceId;
    }

    // Process sync queue periodically
    async processSyncQueue() {
        if (this.isOnline && !this.syncInProgress) {
            await this.triggerSync();
        }

        // Schedule next check
        setTimeout(() => this.processSyncQueue(), 30000); // Check every 30 seconds
    }

    // Export/import for backup
    async exportOfflineData() {
        const data = {};
        const stores = ["articles", "notes", "tags", "bookmarks"];

        for (const store of stores) {
            data[store] = await localStorageManager.getAllItems(store);
        }

        data.metadata = {
            lastSync: await this.getLastSyncTimestamp(),
            exportDate: new Date().toISOString(),
            deviceId: this.getDeviceId(),
        };

        return data;
    }

    async importOfflineData(data) {
        const stores = ["articles", "notes", "tags", "bookmarks"];

        for (const store of stores) {
            if (data[store]) {
                for (const item of data[store]) {
                    await localStorageManager.saveItem(store, item);
                }
            }
        }

        if (data.metadata?.lastSync) {
            await this.updateLastSyncTimestamp(data.metadata.lastSync);
        }
    }
}

// Export singleton instance
const offlineSyncManager = new OfflineSyncManager();

export default offlineSyncManager;
