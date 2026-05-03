const SyncManager = require("../services/SyncManager");
const DeviceManager = require("../services/DeviceManager");
const { sequelize } = require("../config/database");

class EntitySyncHandler {
    static async syncArticles(userId, deviceId, lastSyncTimestamp, articles) {
        const transaction = await sequelize.transaction();

        try {
            const changes = [];

            // Process local changes
            for (const article of articles) {
                if (article.isDeleted) {
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "article",
                            article.id,
                            "delete",
                            article,
                        ),
                    );
                } else if (article.lastModified > lastSyncTimestamp) {
                    const action =
                        article.createdAt > lastSyncTimestamp
                            ? "create"
                            : "update";
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "article",
                            article.id,
                            action,
                            article,
                        ),
                    );
                }
            }

            // Get remote changes
            const remoteChanges = await SyncManager.getChangesSince(
                userId,
                deviceId,
                lastSyncTimestamp,
                ["article"],
            );

            await transaction.commit();

            return {
                localChanges: changes,
                remoteChanges: remoteChanges,
                timestamp: new Date(),
            };
        } catch (error) {
            await transaction.rollback();
            throw error;
        }
    }

    static async syncNotes(userId, deviceId, lastSyncTimestamp, notes) {
        const transaction = await sequelize.transaction();

        try {
            const changes = [];

            // Process local changes
            for (const note of notes) {
                if (note.isDeleted) {
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "note",
                            note.id,
                            "delete",
                            note,
                        ),
                    );
                } else if (note.lastModified > lastSyncTimestamp) {
                    const action =
                        note.createdAt > lastSyncTimestamp
                            ? "create"
                            : "update";
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "note",
                            note.id,
                            action,
                            note,
                        ),
                    );
                }
            }

            // Get remote changes
            const remoteChanges = await SyncManager.getChangesSince(
                userId,
                deviceId,
                lastSyncTimestamp,
                ["note"],
            );

            await transaction.commit();

            return {
                localChanges: changes,
                remoteChanges: remoteChanges,
                timestamp: new Date(),
            };
        } catch (error) {
            await transaction.rollback();
            throw error;
        }
    }

    static async syncTags(userId, deviceId, lastSyncTimestamp, tags) {
        const transaction = await sequelize.transaction();

        try {
            const changes = [];

            // Process local changes
            for (const tag of tags) {
                if (tag.isDeleted) {
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "tag",
                            tag.id,
                            "delete",
                            tag,
                        ),
                    );
                } else if (tag.lastModified > lastSyncTimestamp) {
                    const action =
                        tag.createdAt > lastSyncTimestamp ? "create" : "update";
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "tag",
                            tag.id,
                            action,
                            tag,
                        ),
                    );
                }
            }

            // Get remote changes
            const remoteChanges = await SyncManager.getChangesSince(
                userId,
                deviceId,
                lastSyncTimestamp,
                ["tag"],
            );

            await transaction.commit();

            return {
                localChanges: changes,
                remoteChanges: remoteChanges,
                timestamp: new Date(),
            };
        } catch (error) {
            await transaction.rollback();
            throw error;
        }
    }

    static async syncBookmarks(userId, deviceId, lastSyncTimestamp, bookmarks) {
        const transaction = await sequelize.transaction();

        try {
            const changes = [];

            // Process local changes
            for (const bookmark of bookmarks) {
                if (bookmark.isDeleted) {
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "bookmark",
                            bookmark.id,
                            "delete",
                            bookmark,
                        ),
                    );
                } else if (bookmark.lastModified > lastSyncTimestamp) {
                    const action =
                        bookmark.createdAt > lastSyncTimestamp
                            ? "create"
                            : "update";
                    changes.push(
                        await SyncManager.recordChange(
                            userId,
                            deviceId,
                            "bookmark",
                            bookmark.id,
                            action,
                            bookmark,
                        ),
                    );
                }
            }

            // Get remote changes
            const remoteChanges = await SyncManager.getChangesSince(
                userId,
                deviceId,
                lastSyncTimestamp,
                ["bookmark"],
            );

            await transaction.commit();

            return {
                localChanges: changes,
                remoteChanges: remoteChanges,
                timestamp: new Date(),
            };
        } catch (error) {
            await transaction.rollback();
            throw error;
        }
    }

    static async performFullSync(userId, deviceId, syncData) {
        const results = {};

        try {
            // Sync each entity type
            if (syncData.articles) {
                results.articles = await this.syncArticles(
                    userId,
                    deviceId,
                    syncData.lastSyncTimestamp,
                    syncData.articles,
                );
            }

            if (syncData.notes) {
                results.notes = await this.syncNotes(
                    userId,
                    deviceId,
                    syncData.lastSyncTimestamp,
                    syncData.notes,
                );
            }

            if (syncData.tags) {
                results.tags = await this.syncTags(
                    userId,
                    deviceId,
                    syncData.lastSyncTimestamp,
                    syncData.tags,
                );
            }

            if (syncData.bookmarks) {
                results.bookmarks = await this.syncBookmarks(
                    userId,
                    deviceId,
                    syncData.lastSyncTimestamp,
                    syncData.bookmarks,
                );
            }

            // Update device last sync time
            await DeviceManager.updateDeviceSyncTime(deviceId);

            return results;
        } catch (error) {
            console.error("Full sync error:", error);
            throw error;
        }
    }
}

module.exports = EntitySyncHandler;
