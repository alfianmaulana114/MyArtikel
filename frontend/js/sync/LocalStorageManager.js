class LocalStorageManager {
    constructor() {
        this.DB_NAME = "MyArtikelSyncDB";
        this.DB_VERSION = 1;
        this.db = null;
        this.initDB();
    }

    async initDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.DB_NAME, this.DB_VERSION);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create object stores
                if (!db.objectStoreNames.contains("articles")) {
                    const articlesStore = db.createObjectStore("articles", {
                        keyPath: "id",
                    });
                    articlesStore.createIndex("lastModified", "lastModified");
                    articlesStore.createIndex("isDeleted", "isDeleted");
                    articlesStore.createIndex("syncStatus", "syncStatus");
                }

                if (!db.objectStoreNames.contains("notes")) {
                    const notesStore = db.createObjectStore("notes", {
                        keyPath: "id",
                    });
                    notesStore.createIndex("lastModified", "lastModified");
                    notesStore.createIndex("isDeleted", "isDeleted");
                    notesStore.createIndex("syncStatus", "syncStatus");
                }

                if (!db.objectStoreNames.contains("tags")) {
                    const tagsStore = db.createObjectStore("tags", {
                        keyPath: "id",
                    });
                    tagsStore.createIndex("lastModified", "lastModified");
                    tagsStore.createIndex("isDeleted", "isDeleted");
                    tagsStore.createIndex("syncStatus", "syncStatus");
                }

                if (!db.objectStoreNames.contains("bookmarks")) {
                    const bookmarksStore = db.createObjectStore("bookmarks", {
                        keyPath: "id",
                    });
                    bookmarksStore.createIndex("lastModified", "lastModified");
                    bookmarksStore.createIndex("isDeleted", "isDeleted");
                    bookmarksStore.createIndex("syncStatus", "syncStatus");
                }

                if (!db.objectStoreNames.contains("sync_queue")) {
                    const syncQueueStore = db.createObjectStore("sync_queue", {
                        keyPath: "id",
                        autoIncrement: true,
                    });
                    syncQueueStore.createIndex("entityType", "entityType");
                    syncQueueStore.createIndex("timestamp", "timestamp");
                    syncQueueStore.createIndex("status", "status");
                }

                if (!db.objectStoreNames.contains("metadata")) {
                    db.createObjectStore("metadata", { keyPath: "key" });
                }
            };
        });
    }

    // Generic CRUD operations
    async saveItem(storeName, item) {
        const transaction = this.db.transaction([storeName], "readwrite");
        const store = transaction.objectStore(storeName);

        // Add sync metadata
        item.lastModified = item.lastModified || new Date().toISOString();
        item.syncStatus = item.syncStatus || "pending";

        return new Promise((resolve, reject) => {
            const request = store.put(item);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getItem(storeName, id) {
        const transaction = this.db.transaction([storeName], "readonly");
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            const request = store.get(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getAllItems(storeName, options = {}) {
        const transaction = this.db.transaction([storeName], "readonly");
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            let request;

            if (options.index && options.range) {
                const index = store.index(options.index);
                request = index.getAll(options.range);
            } else {
                request = store.getAll();
            }

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async deleteItem(storeName, id) {
        const transaction = this.db.transaction([storeName], "readwrite");
        const store = transaction.objectStore(storeName);

        return new Promise((resolve, reject) => {
            const request = store.delete(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Soft delete (mark as deleted)
    async softDelete(storeName, id) {
        const item = await this.getItem(storeName, id);
        if (item) {
            item.isDeleted = true;
            item.deletedAt = new Date().toISOString();
            item.syncStatus = "pending";
            await this.saveItem(storeName, item);
        }
    }

    // Sync queue operations
    async addToSyncQueue(entityType, entityId, action, data) {
        const queueItem = {
            entityType,
            entityId,
            action,
            data,
            timestamp: new Date().toISOString(),
            status: "pending",
            retryCount: 0,
        };

        const transaction = this.db.transaction(["sync_queue"], "readwrite");
        const store = transaction.objectStore("sync_queue");

        return new Promise((resolve, reject) => {
            const request = store.add(queueItem);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getSyncQueueItems(status = "pending", limit = 100) {
        const transaction = this.db.transaction(["sync_queue"], "readonly");
        const store = transaction.objectStore("sync_queue");
        const index = store.index("status");

        return new Promise((resolve, reject) => {
            const request = index.getAll(status, limit);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async updateSyncQueueItem(id, updates) {
        const transaction = this.db.transaction(["sync_queue"], "readwrite");
        const store = transaction.objectStore("sync_queue");

        const item = await new Promise((resolve, reject) => {
            const request = store.get(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });

        if (item) {
            Object.assign(item, updates);

            return new Promise((resolve, reject) => {
                const request = store.put(item);
                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }
    }

    async removeFromSyncQueue(id) {
        const transaction = this.db.transaction(["sync_queue"], "readwrite");
        const store = transaction.objectStore("sync_queue");

        return new Promise((resolve, reject) => {
            const request = store.delete(id);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Metadata operations
    async setMetadata(key, value) {
        const transaction = this.db.transaction(["metadata"], "readwrite");
        const store = transaction.objectStore("metadata");

        return new Promise((resolve, reject) => {
            const request = store.put({
                key,
                value,
                timestamp: new Date().toISOString(),
            });
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async getMetadata(key) {
        const transaction = this.db.transaction(["metadata"], "readonly");
        const store = transaction.objectStore("metadata");

        return new Promise((resolve, reject) => {
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result?.value);
            request.onerror = () => reject(request.error);
        });
    }

    // Get items that need sync
    async getItemsNeedingSync(storeName) {
        const transaction = this.db.transaction([storeName], "readonly");
        const store = transaction.objectStore(storeName);
        const index = store.index("syncStatus");

        return new Promise((resolve, reject) => {
            const request = index.getAll("pending");
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Get items modified after a certain date
    async getItemsModifiedAfter(storeName, timestamp) {
        const transaction = this.db.transaction([storeName], "readonly");
        const store = transaction.objectStore(storeName);
        const index = store.index("lastModified");

        const range = IDBKeyRange.lowerBound(timestamp);

        return new Promise((resolve, reject) => {
            const request = index.getAll(range);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Clear all data (for logout)
    async clearAllData() {
        const storeNames = [
            "articles",
            "notes",
            "tags",
            "bookmarks",
            "sync_queue",
            "metadata",
        ];

        for (const storeName of storeNames) {
            const transaction = this.db.transaction([storeName], "readwrite");
            const store = transaction.objectStore(storeName);
            await new Promise((resolve, reject) => {
                const request = store.clear();
                request.onsuccess = () => resolve();
                request.onerror = () => reject(request.error);
            });
        }
    }

    // Get database size
    async getDatabaseSize() {
        const estimate = await navigator.storage.estimate();
        return {
            usage: estimate.usage,
            quota: estimate.quota,
            percentage: (estimate.usage / estimate.quota) * 100,
        };
    }
}

// Export singleton instance
const localStorageManager = new LocalStorageManager();

// Wait for database initialization
localStorageManager
    .initDB()
    .then(() => {
        console.log("Local storage database initialized");
    })
    .catch((error) => {
        console.error("Failed to initialize local storage database:", error);
    });

export default localStorageManager;
