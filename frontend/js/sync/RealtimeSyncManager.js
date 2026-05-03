import offlineSyncManager from "./OfflineSyncManager.js";

class RealtimeSyncManager {
    constructor() {
        this.ws = null;
        this.reconnectInterval = 5000; // 5 seconds
        this.heartbeatInterval = 30000; // 30 seconds
        this.maxReconnectAttempts = 5;
        this.reconnectAttempts = 0;
        this.isConnected = false;
        this.messageHandlers = new Map();
        this.connectionCallbacks = [];

        this.setupMessageHandlers();
    }

    // Initialize WebSocket connection
    connect() {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            return;
        }

        const token = this.getAuthToken();
        if (!token) {
            console.warn("No auth token available for WebSocket connection");
            return;
        }

        const wsUrl = `ws://${window.location.host}/ws/sync?token=${token}`;

        try {
            this.ws = new WebSocket(wsUrl);
            this.setupWebSocketHandlers();
        } catch (error) {
            console.error("Failed to create WebSocket connection:", error);
            this.scheduleReconnect();
        }
    }

    // Setup WebSocket event handlers
    setupWebSocketHandlers() {
        this.ws.onopen = () => {
            console.log("WebSocket connected");
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.startHeartbeat();
            this.notifyConnectionStatus("connected");
        };

        this.ws.onmessage = (event) => {
            try {
                const message = JSON.parse(event.data);
                this.handleMessage(message);
            } catch (error) {
                console.error("Failed to parse WebSocket message:", error);
            }
        };

        this.ws.onclose = (event) => {
            console.log("WebSocket disconnected:", event.code, event.reason);
            this.isConnected = false;
            this.stopHeartbeat();
            this.notifyConnectionStatus("disconnected");

            // Don't reconnect if closed normally (code 1000)
            if (event.code !== 1000) {
                this.scheduleReconnect();
            }
        };

        this.ws.onerror = (error) => {
            console.error("WebSocket error:", error);
            this.notifyConnectionStatus("error", error);
        };
    }

    // Setup message handlers
    setupMessageHandlers() {
        this.registerMessageHandler(
            "sync_status",
            this.handleSyncStatus.bind(this),
        );
        this.registerMessageHandler(
            "sync_started",
            this.handleSyncStarted.bind(this),
        );
        this.registerMessageHandler(
            "sync_completed",
            this.handleSyncCompleted.bind(this),
        );
        this.registerMessageHandler(
            "sync_error",
            this.handleSyncError.bind(this),
        );
        this.registerMessageHandler(
            "data_changed",
            this.handleDataChanged.bind(this),
        );
        this.registerMessageHandler(
            "conflict_resolved",
            this.handleConflictResolved.bind(this),
        );
        this.registerMessageHandler(
            "heartbeat_ack",
            this.handleHeartbeatAck.bind(this),
        );
    }

    // Register message handler
    registerMessageHandler(messageType, handler) {
        this.messageHandlers.set(messageType, handler);
    }

    // Handle incoming messages
    handleMessage(message) {
        const handler = this.messageHandlers.get(message.type);
        if (handler) {
            try {
                handler(message);
            } catch (error) {
                console.error(
                    `Error handling message type ${message.type}:`,
                    error,
                );
            }
        } else {
            console.warn("Unknown message type:", message.type);
        }
    }

    // Message handlers
    handleSyncStatus(message) {
        console.log("Sync status received:", message);

        // Update local sync status
        if (message.syncStatus) {
            this.updateLocalSyncStatus(message.syncStatus);
        }

        // Trigger sync if needed
        if (message.status === "ready" && this.shouldAutoSync()) {
            this.requestSync();
        }
    }

    handleSyncStarted(message) {
        console.log("Sync started on device:", message.deviceId);

        // Update UI to show sync in progress
        this.notifySyncEvent("started", {
            deviceId: message.deviceId,
            syncId: message.syncId,
        });
    }

    handleSyncCompleted(message) {
        console.log("Sync completed on device:", message.deviceId);

        // Refresh local data if sync was from another device
        if (message.deviceId !== this.getDeviceId()) {
            this.refreshLocalData(message.results);
        }

        this.notifySyncEvent("completed", {
            deviceId: message.deviceId,
            results: message.results,
        });
    }

    handleSyncError(message) {
        console.error("Sync error received:", message.error);

        this.notifySyncEvent("error", {
            error: message.error,
        });
    }

    handleDataChanged(message) {
        console.log("Data changed notification:", message);

        // Apply changes to local data
        this.applyRemoteChange(message);

        this.notifySyncEvent("data_changed", message);
    }

    handleConflictResolved(message) {
        console.log("Conflict resolved:", message);

        // Update local data with resolved conflict
        this.applyConflictResolution(message.resolution);

        this.notifySyncEvent("conflict_resolved", message);
    }

    handleHeartbeatAck(message) {
        // Update last heartbeat timestamp
        this.lastHeartbeat = new Date();
    }

    // Send message to server
    sendMessage(message) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(message));
        } else {
            console.warn("WebSocket not connected, message not sent:", message);
        }
    }

    // Request sync
    requestSync(syncData = {}) {
        this.sendMessage({
            type: "sync_request",
            data: {
                deviceId: this.getDeviceId(),
                lastSyncTimestamp: offlineSyncManager.getLastSyncTimestamp(),
                ...syncData,
            },
        });
    }

    // Send heartbeat
    sendHeartbeat() {
        this.sendMessage({
            type: "heartbeat",
            timestamp: new Date().toISOString(),
        });
    }

    // Start heartbeat
    startHeartbeat() {
        this.heartbeatTimer = setInterval(() => {
            this.sendHeartbeat();
        }, this.heartbeatInterval);
    }

    // Stop heartbeat
    stopHeartbeat() {
        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }
    }

    // Schedule reconnection
    scheduleReconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.warn(
                "Max reconnection attempts reached, falling back to polling",
            );
            this.startPolling();
            return;
        }

        this.reconnectAttempts++;

        setTimeout(() => {
            console.log(
                `Attempting reconnection ${this.reconnectAttempts}/${this.maxReconnectAttempts}`,
            );
            this.connect();
        }, this.reconnectInterval * this.reconnectAttempts);
    }

    // Disconnect WebSocket
    disconnect() {
        if (this.ws) {
            this.ws.close(1000, "Client disconnecting");
            this.ws = null;
        }
        this.stopHeartbeat();
        this.stopPolling();
    }

    // Polling fallback
    startPolling() {
        if (this.pollingInterval) {
            return; // Already polling
        }

        console.log("Starting polling mode");

        this.pollingInterval = setInterval(async () => {
            if (this.isOnline()) {
                await this.performPollingSync();
            }
        }, 30000); // Poll every 30 seconds
    }

    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }

    async performPollingSync() {
        try {
            const response = await fetch("/api/sync/pending", {
                headers: {
                    Authorization: `Bearer ${this.getAuthToken()}`,
                },
            });

            if (response.ok) {
                const data = await response.json();

                if (data.data.pendingChanges.length > 0) {
                    // Process pending changes
                    await this.processPendingChanges(data.data.pendingChanges);
                }
            }
        } catch (error) {
            console.error("Polling sync error:", error);
        }
    }

    async processPendingChanges(changes) {
        for (const change of changes) {
            await offlineSyncManager.applyRemoteChange(change);
        }
    }

    // Utility methods
    getAuthToken() {
        return localStorage.getItem("authToken") || "";
    }

    getDeviceId() {
        return offlineSyncManager.getDeviceId();
    }

    isOnline() {
        return navigator.onLine;
    }

    shouldAutoSync() {
        // Auto sync if there are pending changes or it's been more than 5 minutes
        const lastSync = new Date(offlineSyncManager.getLastSyncTimestamp());
        const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);

        return (
            offlineSyncManager.getPendingItemsCount() > 0 ||
            lastSync < fiveMinutesAgo
        );
    }

    // Apply remote changes to local data
    async applyRemoteChange(message) {
        const { entityType, entityId, action, data } = message;

        try {
            switch (action) {
                case "create":
                case "update":
                    await offlineSyncManager.saveDataOffline(entityType, data);
                    break;
                case "delete":
                    await offlineSyncManager.deleteDataOffline(
                        entityType,
                        entityId,
                    );
                    break;
            }
        } catch (error) {
            console.error("Failed to apply remote change:", error);
        }
    }

    // Apply conflict resolution
    async applyConflictResolution(resolution) {
        // Implementation depends on conflict resolution format
        console.log("Applying conflict resolution:", resolution);
    }

    // Refresh local data based on sync results
    async refreshLocalData(syncResults) {
        // Refresh data from local storage or trigger a local sync
        console.log(
            "Refreshing local data based on sync results:",
            syncResults,
        );
    }

    // Update local sync status
    updateLocalSyncStatus(syncStatus) {
        // Update UI or internal state based on sync status
        console.log("Updating local sync status:", syncStatus);
    }

    // Connection status management
    onConnectionStatusChange(callback) {
        this.connectionCallbacks.push(callback);
    }

    notifyConnectionStatus(status, error = null) {
        this.connectionCallbacks.forEach((callback) => {
            try {
                callback({
                    status,
                    error,
                    timestamp: new Date().toISOString(),
                });
            } catch (error) {
                console.error("Connection callback error:", error);
            }
        });
    }

    // Sync event management
    onSyncEvent(callback) {
        offlineSyncManager.onSyncStatusChange(callback);
    }

    notifySyncEvent(eventType, data) {
        // Additional event handling can be added here
        console.log(`Sync event: ${eventType}`, data);
    }

    // Get connection status
    getConnectionStatus() {
        return {
            isConnected: this.isConnected,
            isOnline: this.isOnline(),
            lastHeartbeat: this.lastHeartbeat,
            reconnectAttempts: this.reconnectAttempts,
            connectionType: this.isConnected ? "websocket" : "polling",
        };
    }
}

// Export singleton instance
const realtimeSyncManager = new RealtimeSyncManager();

export default realtimeSyncManager;
