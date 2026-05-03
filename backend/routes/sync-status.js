const express = require("express");
const SyncStatusTracker = require("../services/SyncStatusTracker");
const { authenticateToken } = require("../middleware/auth");

const router = express.Router();

// Get current sync status
router.get("/status", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const { deviceId } = req.query;

        if (!deviceId) {
            return res.status(400).json({
                success: false,
                error: "Device ID is required",
            });
        }

        const syncStatus = await SyncStatusTracker.getDeviceSyncStatus(
            userId,
            deviceId,
        );

        res.json({
            success: true,
            data: syncStatus,
        });
    } catch (error) {
        console.error("Get sync status error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get sync status",
        });
    }
});

// Get sync history
router.get("/history", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const { deviceId, limit = 50 } = req.query;

        if (!deviceId) {
            return res.status(400).json({
                success: false,
                error: "Device ID is required",
            });
        }

        const history = await SyncStatusTracker.getSyncHistory(
            userId,
            deviceId,
            parseInt(limit),
        );

        res.json({
            success: true,
            data: {
                history: history.map((record) =>
                    SyncStatusTracker.formatSyncStatusForUI(record),
                ),
            },
        });
    } catch (error) {
        console.error("Get sync history error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get sync history",
        });
    }
});

// Get sync statistics
router.get("/stats", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const { days = 7 } = req.query;

        const stats = await SyncStatusTracker.getSyncStats(
            userId,
            parseInt(days),
        );

        res.json({
            success: true,
            data: {
                stats,
                period: {
                    days: parseInt(days),
                    startDate: new Date(
                        Date.now() - parseInt(days) * 24 * 60 * 60 * 1000,
                    ),
                },
            },
        });
    } catch (error) {
        console.error("Get sync stats error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get sync statistics",
        });
    }
});

// Cancel current sync
router.post("/cancel", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const { deviceId } = req.body;

        if (!deviceId) {
            return res.status(400).json({
                success: false,
                error: "Device ID is required",
            });
        }

        const currentSync = await SyncStatusTracker.getCurrentSync(
            userId,
            deviceId,
        );

        if (!currentSync) {
            return res.status(404).json({
                success: false,
                error: "No active sync found",
            });
        }

        await SyncStatusTracker.cancelSync(currentSync.id);

        res.json({
            success: true,
            message: "Sync cancelled successfully",
        });
    } catch (error) {
        console.error("Cancel sync error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to cancel sync",
        });
    }
});

module.exports = router;
