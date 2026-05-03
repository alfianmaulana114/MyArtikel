const SyncStatus = require("../models/SyncStatus");
const { sequelize } = require("../config/database");

class SyncStatusTracker {
    static async startSync(userId, deviceId, syncType = "incremental") {
        return await SyncStatus.create({
            userId,
            deviceId,
            syncType,
            status: "in_progress",
            startedAt: new Date(),
        });
    }

    static async updateSyncProgress(syncId, progressData) {
        const updateData = {};

        if (progressData.itemsProcessed !== undefined) {
            updateData.itemsProcessed = progressData.itemsProcessed;
        }

        if (progressData.itemsTotal !== undefined) {
            updateData.itemsTotal = progressData.itemsTotal;
        }

        if (progressData.conflictsDetected !== undefined) {
            updateData.conflictsDetected = progressData.conflictsDetected;
        }

        if (progressData.conflictsResolved !== undefined) {
            updateData.conflictsResolved = progressData.conflictsResolved;
        }

        if (progressData.errors) {
            updateData.errors = progressData.errors;
        }

        if (progressData.performanceMetrics) {
            updateData.performanceMetrics = progressData.performanceMetrics;
        }

        if (progressData.networkStats) {
            updateData.networkStats = progressData.networkStats;
        }

        return await SyncStatus.update(updateData, {
            where: { id: syncId },
        });
    }

    static async completeSync(syncId, completionData = {}) {
        const updateData = {
            status: "completed",
            completedAt: new Date(),
        };

        if (completionData.itemsProcessed !== undefined) {
            updateData.itemsProcessed = completionData.itemsProcessed;
        }

        if (completionData.itemsTotal !== undefined) {
            updateData.itemsTotal = completionData.itemsTotal;
        }

        if (completionData.conflictsDetected !== undefined) {
            updateData.conflictsDetected = completionData.conflictsDetected;
        }

        if (completionData.conflictsResolved !== undefined) {
            updateData.conflictsResolved = completionData.conflictsResolved;
        }

        if (completionData.performanceMetrics) {
            updateData.performanceMetrics = completionData.performanceMetrics;
        }

        return await SyncStatus.update(updateData, {
            where: { id: syncId },
        });
    }

    static async failSync(syncId, error) {
        return await SyncStatus.update(
            {
                status: "failed",
                completedAt: new Date(),
                errors: [
                    {
                        message: error.message,
                        stack: error.stack,
                        timestamp: new Date(),
                    },
                ],
            },
            {
                where: { id: syncId },
            },
        );
    }

    static async cancelSync(syncId) {
        return await SyncStatus.update(
            {
                status: "cancelled",
                completedAt: new Date(),
            },
            {
                where: { id: syncId },
            },
        );
    }

    static async getCurrentSync(userId, deviceId) {
        return await SyncStatus.findOne({
            where: {
                userId,
                deviceId,
                status: "in_progress",
            },
            order: [["startedAt", "DESC"]],
        });
    }

    static async getSyncHistory(userId, deviceId, limit = 50) {
        return await SyncStatus.findAll({
            where: { userId, deviceId },
            order: [["startedAt", "DESC"]],
            limit,
        });
    }

    static async getSyncStats(userId, days = 7) {
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - days);

        const stats = await SyncStatus.findAll({
            where: {
                userId,
                startedAt: {
                    [sequelize.Sequelize.Op.gte]: startDate,
                },
            },
            attributes: [
                "status",
                "syncType",
                [sequelize.fn("COUNT", sequelize.col("id")), "count"],
                [
                    sequelize.fn("AVG", sequelize.col("items_processed")),
                    "avg_items",
                ],
                [
                    sequelize.fn(
                        "AVG",
                        sequelize.literal(
                            "EXTRACT(EPOCH FROM (completed_at - started_at))",
                        ),
                    ),
                    "avg_duration",
                ],
            ],
            group: ["status", "syncType"],
        });

        return stats.map((stat) => ({
            status: stat.status,
            syncType: stat.syncType,
            count: parseInt(stat.get("count")),
            avgItems: parseFloat(stat.get("avg_items") || 0),
            avgDuration: parseFloat(stat.get("avg_duration") || 0),
        }));
    }

    static async getDeviceSyncStatus(userId, deviceId) {
        const latestSync = await SyncStatus.findOne({
            where: { userId, deviceId },
            order: [["startedAt", "DESC"]],
        });

        if (!latestSync) {
            return {
                lastSync: null,
                status: "never_synced",
                isSyncing: false,
            };
        }

        const currentSync = await this.getCurrentSync(userId, deviceId);

        return {
            lastSync: latestSync.completedAt || latestSync.startedAt,
            status: latestSync.status,
            isSyncing: !!currentSync,
            currentSync: currentSync
                ? {
                      id: currentSync.id,
                      type: currentSync.syncType,
                      startedAt: currentSync.startedAt,
                      progress:
                          currentSync.itemsTotal > 0
                              ? (currentSync.itemsProcessed /
                                    currentSync.itemsTotal) *
                                100
                              : 0,
                      conflicts: {
                          detected: currentSync.conflictsDetected,
                          resolved: currentSync.conflictsResolved,
                      },
                  }
                : null,
        };
    }

    static async cleanupOldSyncRecords(daysToKeep = 30) {
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - daysToKeep);

        return await SyncStatus.destroy({
            where: {
                startedAt: {
                    [sequelize.Sequelize.Op.lt]: cutoffDate,
                },
                status: ["completed", "failed", "cancelled"],
            },
        });
    }

    static calculateSyncPerformance(startTime, endTime, itemsProcessed) {
        const duration = (endTime - startTime) / 1000; // seconds
        const itemsPerSecond = itemsProcessed / duration;

        return {
            duration,
            itemsProcessed,
            itemsPerSecond,
            performance:
                itemsPerSecond > 10
                    ? "good"
                    : itemsPerSecond > 5
                      ? "average"
                      : "slow",
        };
    }

    static formatSyncStatusForUI(syncStatus) {
        if (!syncStatus) return null;

        const progress =
            syncStatus.itemsTotal > 0
                ? Math.round(
                      (syncStatus.itemsProcessed / syncStatus.itemsTotal) * 100,
                  )
                : 0;

        return {
            id: syncStatus.id,
            type: syncStatus.syncType,
            status: syncStatus.status,
            progress,
            startedAt: syncStatus.startedAt,
            completedAt: syncStatus.completedAt,
            duration: syncStatus.completedAt
                ? Math.round(
                      (syncStatus.completedAt - syncStatus.startedAt) / 1000,
                  )
                : null,
            conflicts: {
                detected: syncStatus.conflictsDetected,
                resolved: syncStatus.conflictsResolved,
            },
            errors: syncStatus.errors,
            performance: syncStatus.performanceMetrics,
        };
    }
}

module.exports = SyncStatusTracker;
