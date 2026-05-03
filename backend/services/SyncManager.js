const crypto = require("crypto");
const { sequelize } = require("../config/database");
const SyncRecord = require("../models/SyncRecord");
const DeviceManager = require("./DeviceManager");

class SyncManager {
    static generateDataHash(data) {
        return crypto
            .createHash("sha256")
            .update(JSON.stringify(data))
            .digest("hex");
    }

    static async recordChange(
        userId,
        deviceId,
        entityType,
        entityId,
        action,
        data,
    ) {
        const dataHash = this.generateDataHash(data);

        return await SyncRecord.create({
            userId,
            deviceId,
            entityType,
            entityId,
            action,
            dataHash,
            timestamp: new Date(),
        });
    }

    static async getChangesSince(
        userId,
        deviceId,
        sinceTimestamp,
        entityTypes = null,
    ) {
        const whereClause = {
            userId,
            timestamp: {
                [sequelize.Sequelize.Op.gt]: sinceTimestamp,
            },
        };

        // Exclude changes from the same device (to avoid echo)
        if (deviceId) {
            whereClause.deviceId = {
                [sequelize.Sequelize.Op.ne]: deviceId,
            };
        }

        if (entityTypes) {
            whereClause.entityType = entityTypes;
        }

        return await SyncRecord.findAll({
            where: whereClause,
            order: [["timestamp", "ASC"]],
            limit: 1000, // Prevent excessive data transfer
        });
    }

    static async getPendingChanges(userId, limit = 100) {
        return await SyncRecord.findAll({
            where: {
                userId,
                syncStatus: "pending",
            },
            order: [["timestamp", "ASC"]],
            limit,
        });
    }

    static async markAsSynced(syncRecordIds) {
        return await SyncRecord.update(
            { syncStatus: "synced" },
            {
                where: {
                    id: syncRecordIds,
                },
            },
        );
    }

    static async markAsConflict(syncRecordId, conflictResolution) {
        return await SyncRecord.update(
            {
                syncStatus: "conflict",
                conflictResolution,
            },
            {
                where: { id: syncRecordId },
            },
        );
    }

    static async markAsError(syncRecordId, error) {
        return await SyncRecord.update(
            {
                syncStatus: "error",
                lastError: error.message,
                retryCount: sequelize.literal("retry_count + 1"),
            },
            {
                where: { id: syncRecordId },
            },
        );
    }

    static async cleanupOldRecords(daysToKeep = 30) {
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - daysToKeep);

        return await SyncRecord.destroy({
            where: {
                timestamp: {
                    [sequelize.Sequelize.Op.lt]: cutoffDate,
                },
                syncStatus: "synced",
            },
        });
    }

    static async getSyncStatus(userId) {
        const stats = await SyncRecord.findAll({
            where: { userId },
            attributes: [
                "syncStatus",
                [sequelize.fn("COUNT", sequelize.col("id")), "count"],
            ],
            group: ["syncStatus"],
        });

        const status = {
            pending: 0,
            synced: 0,
            conflict: 0,
            error: 0,
        };

        stats.forEach((stat) => {
            status[stat.syncStatus] = parseInt(stat.get("count"));
        });

        return status;
    }

    static async getLastSyncTimestamp(userId, deviceId) {
        const lastSync = await SyncRecord.findOne({
            where: { userId, deviceId },
            order: [["timestamp", "DESC"]],
        });

        return lastSync ? lastSync.timestamp : null;
    }
}

module.exports = SyncManager;
