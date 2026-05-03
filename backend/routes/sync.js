const express = require("express");
const EntitySyncHandler = require("../services/EntitySyncHandler");
const SyncManager = require("../services/SyncManager");
const DeviceManager = require("../services/DeviceManager");
const { authenticateToken } = require("../middleware/auth");

const router = express.Router();

// Main sync endpoint
router.post("/sync", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const {
            deviceId,
            lastSyncTimestamp,
            articles,
            notes,
            tags,
            bookmarks,
        } = req.body;

        // Validate device ownership
        const isValidDevice = await DeviceManager.validateDeviceOwnership(
            deviceId,
            userId,
        );
        if (!isValidDevice) {
            return res.status(403).json({
                success: false,
                error: "Invalid device",
            });
        }

        const syncData = {
            lastSyncTimestamp: lastSyncTimestamp || new Date(0),
            articles,
            notes,
            tags,
            bookmarks,
        };

        const syncResults = await EntitySyncHandler.performFullSync(
            userId,
            deviceId,
            syncData,
        );

        // Mark local changes as synced
        const allLocalChanges = [
            ...(syncResults.articles?.localChanges || []),
            ...(syncResults.notes?.localChanges || []),
            ...(syncResults.tags?.localChanges || []),
            ...(syncResults.bookmarks?.localChanges || []),
        ];

        if (allLocalChanges.length > 0) {
            await SyncManager.markAsSynced(allLocalChanges.map((c) => c.id));
        }

        res.json({
            success: true,
            data: {
                syncResults,
                timestamp: new Date(),
                syncStatus: await SyncManager.getSyncStatus(userId),
            },
        });
    } catch (error) {
        console.error("Sync error:", error);
        res.status(500).json({
            success: false,
            error: "Sync failed",
        });
    }
});

// Get sync status
router.get("/sync/status", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const syncStatus = await SyncManager.getSyncStatus(userId);

        res.json({
            success: true,
            data: {
                syncStatus,
            },
        });
    } catch (error) {
        console.error("Sync status error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get sync status",
        });
    }
});

// Get pending changes
router.get("/sync/pending", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const { limit = 100 } = req.query;

        const pendingChanges = await SyncManager.getPendingChanges(
            userId,
            parseInt(limit),
        );

        res.json({
            success: true,
            data: {
                pendingChanges: pendingChanges.map((change) => ({
                    id: change.id,
                    entityType: change.entityType,
                    entityId: change.entityId,
                    action: change.action,
                    timestamp: change.timestamp,
                    dataHash: change.dataHash,
                    syncStatus: change.syncStatus,
                })),
            },
        });
    } catch (error) {
        console.error("Pending changes error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get pending changes",
        });
    }
});

// Resolve conflict
router.post(
    "/sync/conflicts/:conflictId/resolve",
    authenticateToken,
    async (req, res) => {
        try {
            const { conflictId } = req.params;
            const { resolution } = req.body; // 'local', 'remote', 'merge'
            const userId = req.user.id;

            // Update conflict resolution
            await SyncManager.markAsConflict(conflictId, {
                resolution,
                resolvedAt: new Date(),
                resolvedBy: userId,
            });

            res.json({
                success: true,
                message: "Conflict resolved successfully",
            });
        } catch (error) {
            console.error("Conflict resolution error:", error);
            res.status(500).json({
                success: false,
                error: "Failed to resolve conflict",
            });
        }
    },
);

module.exports = router;
