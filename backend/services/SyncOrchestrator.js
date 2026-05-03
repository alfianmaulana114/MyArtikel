const WebSocketSyncManager = require("../services/WebSocketSyncManager");
const SyncStatusTracker = require("../services/SyncStatusTracker");
const ConflictResolver = require("../services/ConflictResolver");
const DeviceManager = require("../services/DeviceManager");

class SyncOrchestrator {
    constructor() {
        this.wsSyncManager = new WebSocketSyncManager();
        this.syncJobs = new Map();
        this.syncQueue = [];
        this.isProcessing = false;
    }

    // Initialize sync orchestrator
    initialize(server) {
        this.wsSyncManager.initialize(server);
        this.startQueueProcessor();
        console.log("Sync orchestrator initialized");
    }

    // Start queue processor
    startQueueProcessor() {
        setInterval(() => {
            this.processSyncQueue();
        }, 1000); // Process queue every second
    }

    // Add sync job to queue
    async addSyncJob(userId, deviceId, syncData, priority = "normal") {
        const job = {
            id: this.generateJobId(),
            userId,
            deviceId,
            syncData,
            priority,
            createdAt: new Date(),
            status: "queued",
        };

        this.syncQueue.push(job);
        this.syncQueue.sort(
            (a, b) =>
                this.getPriorityValue(b.priority) -
                this.getPriorityValue(a.priority),
        );

        return job.id;
    }

    // Process sync queue
    async processSyncQueue() {
        if (this.isProcessing || this.syncQueue.length === 0) {
            return;
        }

        this.isProcessing = true;

        try {
            const job = this.syncQueue.shift();
            await this.executeSyncJob(job);
        } catch (error) {
            console.error("Sync queue processing error:", error);
        } finally {
            this.isProcessing = false;
        }
    }

    // Execute sync job
    async executeSyncJob(job) {
        const { userId, deviceId, syncData } = job;

        try {
            job.status = "running";
            job.startedAt = new Date();

            // Start sync tracking
            const syncStatus = await SyncStatusTracker.startSync(
                userId,
                deviceId,
                "queued",
            );

            // Perform sync
            const result = await this.performSync(userId, deviceId, syncData);

            // Complete sync tracking
            await SyncStatusTracker.completeSync(syncStatus.id, {
                itemsProcessed: result.itemsProcessed,
                itemsTotal: result.itemsTotal,
                conflictsDetected: result.conflictsDetected,
                conflictsResolved: result.conflictsResolved,
            });

            job.status = "completed";
            job.completedAt = new Date();
            job.result = result;

            // Notify WebSocket clients
            this.notifySyncCompletion(userId, deviceId, result);
        } catch (error) {
            job.status = "failed";
            job.error = error.message;
            job.failedAt = new Date();

            console.error(`Sync job ${job.id} failed:`, error);

            // Fail sync tracking
            if (syncStatus) {
                await SyncStatusTracker.failSync(syncStatus.id, error);
            }

            // Retry logic
            if (job.retryCount < 3) {
                job.retryCount = (job.retryCount || 0) + 1;
                job.status = "queued";
                this.syncQueue.push(job);
            }
        }
    }

    // Perform actual sync
    async performSync(userId, deviceId, syncData) {
        const EntitySyncHandler = require("../services/EntitySyncHandler");

        // Validate device ownership
        const isValidDevice = await DeviceManager.validateDeviceOwnership(
            deviceId,
            userId,
        );
        if (!isValidDevice) {
            throw new Error("Invalid device");
        }

        // Perform entity-specific sync
        const results = await EntitySyncHandler.performFullSync(
            userId,
            deviceId,
            syncData,
        );

        // Process conflicts if any
        const conflicts = await this.detectAndResolveConflicts(
            userId,
            deviceId,
            results,
        );

        return {
            ...results,
            conflictsDetected: conflicts.detected,
            conflictsResolved: conflicts.resolved,
            timestamp: new Date().toISOString(),
        };
    }

    // Detect and resolve conflicts
    async detectAndResolveConflicts(userId, deviceId, syncResults) {
        let detected = 0;
        let resolved = 0;

        // Check for conflicts in sync results
        for (const [entityType, result] of Object.entries(syncResults)) {
            if (result.remoteChanges && result.localChanges) {
                for (const localChange of result.localChanges) {
                    for (const remoteChange of result.remoteChanges) {
                        if (localChange.entityId === remoteChange.entityId) {
                            detected++;

                            // Resolve conflict
                            const resolution =
                                await ConflictResolver.resolveConflictById(
                                    localChange.id,
                                    "last_write_wins",
                                    userId,
                                );

                            if (resolution.resolved) {
                                resolved++;
                            }
                        }
                    }
                }
            }
        }

        return { detected, resolved };
    }

    // Notify sync completion
    notifySyncCompletion(userId, deviceId, result) {
        // Notify via WebSocket
        this.wsSyncManager.notifyDataChange(userId, "sync", null, "completed", {
            deviceId,
            result,
            timestamp: new Date().toISOString(),
        });
    }

    // Get sync job status
    getSyncJobStatus(jobId) {
        return this.syncJobs.get(jobId);
    }

    // Get active sync jobs for user
    getActiveSyncJobs(userId) {
        return Array.from(this.syncJobs.values()).filter(
            (job) => job.userId === userId && job.status === "running",
        );
    }

    // Cancel sync job
    async cancelSyncJob(jobId) {
        const job = this.syncJobs.get(jobId);
        if (job && job.status === "running") {
            job.status = "cancelled";

            // Cancel sync tracking
            if (job.syncStatusId) {
                await SyncStatusTracker.cancelSync(job.syncStatusId);
            }

            return true;
        }
        return false;
    }

    // Get sync statistics
    async getSyncStatistics(userId, timeRange = "7d") {
        const days = this.parseTimeRange(timeRange);
        return await SyncStatusTracker.getSyncStats(userId, days);
    }

    // Get connection statistics
    getConnectionStatistics() {
        return this.wsSyncManager.getConnectionStats();
    }

    // Priority helper
    getPriorityValue(priority) {
        const priorities = {
            high: 3,
            normal: 2,
            low: 1,
        };
        return priorities[priority] || 2;
    }

    // Time range parser
    parseTimeRange(timeRange) {
        const match = timeRange.match(/^(\d+)([dwm])$/);
        if (!match) return 7;

        const value = parseInt(match[1]);
        const unit = match[2];

        switch (unit) {
            case "d":
                return value;
            case "w":
                return value * 7;
            case "m":
                return value * 30;
            default:
                return 7;
        }
    }

    // Generate job ID
    generateJobId() {
        return `sync_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    // Health check
    getHealthStatus() {
        return {
            status: "healthy",
            queueSize: this.syncQueue.length,
            activeJobs: this.syncJobs.size,
            wsConnections: this.wsSyncManager.getConnectionStats(),
            timestamp: new Date().toISOString(),
        };
    }
}

module.exports = SyncOrchestrator;
