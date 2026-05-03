const WebSocket = require("ws");
const jwt = require("jsonwebtoken");
const DeviceManager = require("../services/DeviceManager");
const SyncManager = require("../services/SyncManager");
const SyncStatusTracker = require("../services/SyncStatusTracker");

class WebSocketSyncManager {
    constructor() {
        this.clients = new Map(); // userId -> Set of WebSocket connections
        this.deviceConnections = new Map(); // deviceId -> WebSocket connection
        this.heartbeatInterval = 30000; // 30 seconds
        this.syncInProgress = new Set();
    }

    // Initialize WebSocket server
    initialize(server) {
        this.wss = new WebSocket.Server({
            server,
            path: "/ws/sync",
            verifyClient: this.verifyClient.bind(this),
        });

        this.wss.on("connection", this.handleConnection.bind(this));

        // Start heartbeat
        this.startHeartbeat();

        console.log("WebSocket sync server initialized");
    }

    // Verify WebSocket client
    async verifyClient(info, callback) {
        const token = this.extractToken(info.req);

        if (!token) {
            callback(false, 401, "Unauthorized");
            return;
        }

        try {
            const decoded = jwt.verify(token, process.env.JWT_SECRET);
            const device = await DeviceManager.getDeviceByToken(
                decoded.deviceToken,
            );

            if (!device || !device.isActive) {
                callback(false, 401, "Invalid device");
                return;
            }

            // Attach user and device info to request
            info.req.user = device.user;
            info.req.device = device;

            callback(true);
        } catch (error) {
            console.error("WebSocket verification error:", error);
            callback(false, 401, "Invalid token");
        }
    }

    // Extract token from request
    extractToken(req) {
        const authHeader = req.headers.authorization;
        if (authHeader && authHeader.startsWith("Bearer ")) {
            return authHeader.substring(7);
        }

        // Check query params for token (for WebSocket connections)
        const url = new URL(req.url, `http://${req.headers.host}`);
        return url.searchParams.get("token");
    }

    // Handle new WebSocket connection
    handleConnection(ws, req) {
        const userId = req.user.id;
        const deviceId = req.device.deviceId;

        console.log(
            `WebSocket connection established: ${userId} - ${deviceId}`,
        );

        // Store connection
        if (!this.clients.has(userId)) {
            this.clients.set(userId, new Set());
        }
        this.clients.get(userId).add(ws);
        this.deviceConnections.set(deviceId, ws);

        // Set up connection handlers
        ws.on("message", (data) =>
            this.handleMessage(ws, data, userId, deviceId),
        );
        ws.on("close", () => this.handleDisconnect(ws, userId, deviceId));
        ws.on("pong", () => this.handlePong(ws));

        // Send initial sync status
        this.sendSyncStatus(ws, userId, deviceId);

        // Mark device as online
        DeviceManager.updateDeviceLastSeen(deviceId);
    }

    // Handle incoming messages
    async handleMessage(ws, data, userId, deviceId) {
        try {
            const message = JSON.parse(data);

            switch (message.type) {
                case "sync_request":
                    await this.handleSyncRequest(
                        ws,
                        userId,
                        deviceId,
                        message.data,
                    );
                    break;

                case "sync_acknowledge":
                    await this.handleSyncAcknowledge(
                        userId,
                        deviceId,
                        message.data,
                    );
                    break;

                case "conflict_resolution":
                    await this.handleConflictResolution(
                        userId,
                        deviceId,
                        message.data,
                    );
                    break;

                case "heartbeat":
                    this.handleHeartbeat(ws, userId, deviceId);
                    break;

                default:
                    ws.send(
                        JSON.stringify({
                            type: "error",
                            error: "Unknown message type",
                        }),
                    );
            }
        } catch (error) {
            console.error("Message handling error:", error);
            ws.send(
                JSON.stringify({
                    type: "error",
                    error: "Invalid message format",
                }),
            );
        }
    }

    // Handle sync request from client
    async handleSyncRequest(ws, userId, deviceId, syncData) {
        if (this.syncInProgress.has(deviceId)) {
            ws.send(
                JSON.stringify({
                    type: "sync_status",
                    status: "in_progress",
                    message: "Sync already in progress",
                }),
            );
            return;
        }

        this.syncInProgress.add(deviceId);

        try {
            // Start sync tracking
            const syncStatus = await SyncStatusTracker.startSync(
                userId,
                deviceId,
                "realtime",
            );

            // Notify other devices about sync start
            this.broadcastToUserDevices(userId, deviceId, {
                type: "sync_started",
                deviceId: deviceId,
                syncId: syncStatus.id,
            });

            // Perform sync logic here
            const syncResults = await this.performRealtimeSync(
                userId,
                deviceId,
                syncData,
            );

            // Complete sync tracking
            await SyncStatusTracker.completeSync(syncStatus.id, {
                itemsProcessed: syncResults.itemsProcessed || 0,
                itemsTotal: syncResults.itemsTotal || 0,
                conflictsDetected: syncResults.conflictsDetected || 0,
                conflictsResolved: syncResults.conflictsResolved || 0,
            });

            // Send sync results back to requesting device
            ws.send(
                JSON.stringify({
                    type: "sync_complete",
                    data: syncResults,
                }),
            );

            // Notify other devices about sync completion
            this.broadcastToUserDevices(userId, deviceId, {
                type: "sync_completed",
                deviceId: deviceId,
                syncId: syncStatus.id,
                results: syncResults,
            });
        } catch (error) {
            console.error("Realtime sync error:", error);

            // Fail sync tracking
            if (syncStatus) {
                await SyncStatusTracker.failSync(syncStatus.id, error);
            }

            ws.send(
                JSON.stringify({
                    type: "sync_error",
                    error: error.message,
                }),
            );
        } finally {
            this.syncInProgress.delete(deviceId);
        }
    }

    // Perform realtime sync
    async performRealtimeSync(userId, deviceId, syncData) {
        // This would integrate with your existing sync logic
        // For now, return mock results
        return {
            itemsProcessed: 10,
            itemsTotal: 10,
            conflictsDetected: 0,
            conflictsResolved: 0,
            timestamp: new Date().toISOString(),
        };
    }

    // Handle sync acknowledgment
    async handleSyncAcknowledge(userId, deviceId, data) {
        // Update sync status based on acknowledgment
        console.log(`Sync acknowledged by ${deviceId}:`, data);

        // Notify other devices about acknowledgment
        this.broadcastToUserDevices(userId, deviceId, {
            type: "sync_acknowledged",
            deviceId: deviceId,
            data: data,
        });
    }

    // Handle conflict resolution
    async handleConflictResolution(userId, deviceId, resolutionData) {
        // Process conflict resolution
        console.log(`Conflict resolution from ${deviceId}:`, resolutionData);

        // Broadcast resolution to all user devices
        this.broadcastToUserDevices(userId, null, {
            type: "conflict_resolved",
            deviceId: deviceId,
            resolution: resolutionData,
        });
    }

    // Handle heartbeat
    handleHeartbeat(ws, userId, deviceId) {
        ws.isAlive = true;
        DeviceManager.updateDeviceLastSeen(deviceId);

        ws.send(
            JSON.stringify({
                type: "heartbeat_ack",
                timestamp: new Date().toISOString(),
            }),
        );
    }

    // Handle disconnection
    handleDisconnect(ws, userId, deviceId) {
        console.log(`WebSocket disconnected: ${userId} - ${deviceId}`);

        // Remove from client tracking
        if (this.clients.has(userId)) {
            this.clients.get(userId).delete(ws);
            if (this.clients.get(userId).size === 0) {
                this.clients.delete(userId);
            }
        }

        // Remove from device tracking
        this.deviceConnections.delete(deviceId);

        // Remove from sync in progress
        this.syncInProgress.delete(deviceId);
    }

    // Handle pong (heartbeat response)
    handlePong(ws) {
        ws.isAlive = true;
    }

    // Send sync status to device
    async sendSyncStatus(ws, userId, deviceId) {
        try {
            const syncStatus = await SyncManager.getSyncStatus(userId);
            const deviceStatus = await DeviceManager.getDeviceById(deviceId);

            ws.send(
                JSON.stringify({
                    type: "sync_status",
                    status: "ready",
                    syncStatus: syncStatus,
                    deviceStatus: {
                        lastSyncAt: deviceStatus.lastSyncAt,
                        isActive: deviceStatus.isActive,
                    },
                }),
            );
        } catch (error) {
            console.error("Error sending sync status:", error);
            ws.send(
                JSON.stringify({
                    type: "sync_status",
                    status: "error",
                    error: error.message,
                }),
            );
        }
    }

    // Broadcast to all devices of a user (except sender)
    broadcastToUserDevices(userId, excludeDeviceId, message) {
        if (!this.clients.has(userId)) {
            return;
        }

        const messageStr = JSON.stringify(message);

        this.clients.get(userId).forEach((ws) => {
            if (ws.readyState === WebSocket.OPEN) {
                // Skip if this is the excluded device
                if (excludeDeviceId && ws.deviceId === excludeDeviceId) {
                    return;
                }

                ws.send(messageStr);
            }
        });
    }

    // Start heartbeat mechanism
    startHeartbeat() {
        setInterval(() => {
            this.wss.clients.forEach((ws) => {
                if (ws.isAlive === false) {
                    return ws.terminate();
                }

                ws.isAlive = false;
                ws.ping();
            });
        }, this.heartbeatInterval);
    }

    // Notify devices about data changes
    async notifyDataChange(userId, entityType, entityId, action, data) {
        const message = {
            type: "data_changed",
            entityType: entityType,
            entityId: entityId,
            action: action,
            data: data,
            timestamp: new Date().toISOString(),
        };

        this.broadcastToUserDevices(userId, null, message);
    }

    // Get connected devices for a user
    getConnectedDevices(userId) {
        if (!this.clients.has(userId)) {
            return [];
        }

        return Array.from(this.clients.get(userId)).map((ws) => ({
            deviceId: ws.deviceId,
            readyState: ws.readyState,
        }));
    }

    // Get connection statistics
    getConnectionStats() {
        const stats = {
            totalConnections: this.wss.clients.size,
            uniqueUsers: this.clients.size,
            uniqueDevices: this.deviceConnections.size,
            connectionsPerUser: {},
        };

        this.clients.forEach((connections, userId) => {
            stats.connectionsPerUser[userId] = connections.size;
        });

        return stats;
    }
}

module.exports = WebSocketSyncManager;
