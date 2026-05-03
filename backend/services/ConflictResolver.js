const { sequelize } = require("../config/database");
const SyncRecord = require("../models/SyncRecord");
const crypto = require("crypto");

class ConflictResolver {
    static detectConflict(localData, remoteData) {
        // Check if both versions have been modified
        const localHash = crypto
            .createHash("sha256")
            .update(JSON.stringify(localData))
            .digest("hex");
        const remoteHash = crypto
            .createHash("sha256")
            .update(JSON.stringify(remoteData))
            .digest("hex");

        return localHash !== remoteHash;
    }

    static resolveConflict(
        conflictType,
        localData,
        remoteData,
        resolutionStrategy = "last_write_wins",
    ) {
        switch (resolutionStrategy) {
            case "last_write_wins":
                return this.resolveLastWriteWins(localData, remoteData);

            case "manual_resolution":
                return this.resolveManual(localData, remoteData);

            case "merge":
                return this.resolveMerge(conflictType, localData, remoteData);

            case "server_wins":
                return {
                    resolved: true,
                    data: remoteData,
                    strategy: "server_wins",
                };

            case "client_wins":
                return {
                    resolved: true,
                    data: localData,
                    strategy: "client_wins",
                };

            default:
                return this.resolveLastWriteWins(localData, remoteData);
        }
    }

    static resolveLastWriteWins(localData, remoteData) {
        const localTime = new Date(localData.lastModified).getTime();
        const remoteTime = new Date(remoteData.lastModified).getTime();

        if (localTime > remoteTime) {
            return { resolved: true, data: localData, strategy: "client_wins" };
        } else if (remoteTime > localTime) {
            return {
                resolved: true,
                data: remoteData,
                strategy: "server_wins",
            };
        } else {
            // If timestamps are equal, use server data
            return {
                resolved: true,
                data: remoteData,
                strategy: "server_wins_tie",
            };
        }
    }

    static resolveManual(localData, remoteData) {
        return {
            resolved: false,
            conflict: {
                local: localData,
                remote: remoteData,
                type: "manual_resolution_required",
            },
            strategy: "manual_resolution",
        };
    }

    static resolveMerge(conflictType, localData, remoteData) {
        switch (conflictType) {
            case "article":
                return this.mergeArticles(localData, remoteData);

            case "note":
                return this.mergeNotes(localData, remoteData);

            case "tag":
                return this.mergeTags(localData, remoteData);

            case "bookmark":
                return this.mergeBookmarks(localData, remoteData);

            default:
                return this.resolveLastWriteWins(localData, remoteData);
        }
    }

    static mergeArticles(localData, remoteData) {
        const merged = { ...remoteData }; // Start with server version

        // Merge fields that can be combined
        if (localData.title !== remoteData.title) {
            merged.title = `${remoteData.title} | ${localData.title}`; // Combine titles
        }

        if (localData.content !== remoteData.content) {
            merged.content = `Server: ${remoteData.content}\n\nClient: ${localData.content}`;
        }

        // Merge tags (union of both sets)
        const localTags = new Set(localData.tags || []);
        const remoteTags = new Set(remoteData.tags || []);
        merged.tags = [...new Set([...localTags, ...remoteTags])];

        // Use most recent metadata
        merged.lastModified = new Date().toISOString();
        merged.mergedFrom = {
            local: localData.lastModified,
            remote: remoteData.lastModified,
        };

        return {
            resolved: true,
            data: merged,
            strategy: "merge",
            conflicts: ["title", "content", "tags"],
        };
    }

    static mergeNotes(localData, remoteData) {
        const merged = { ...remoteData };

        // For notes, we can append content with conflict markers
        if (localData.content !== remoteData.content) {
            merged.content = `<<<<<<< Server\n${remoteData.content}\n=======\n${localData.content}\n>>>>>>> Client`;
        }

        // Merge tags
        const localTags = new Set(localData.tags || []);
        const remoteTags = new Set(remoteData.tags || []);
        merged.tags = [...new Set([...localTags, ...remoteTags])];

        merged.lastModified = new Date().toISOString();
        merged.mergedFrom = {
            local: localData.lastModified,
            remote: remoteData.lastModified,
        };

        return {
            resolved: true,
            data: merged,
            strategy: "merge",
            conflicts: ["content", "tags"],
        };
    }

    static mergeTags(localData, remoteData) {
        const merged = { ...remoteData };

        // For tags, merge name and color if different
        if (localData.name !== remoteData.name) {
            merged.name = `${remoteData.name}-${localData.name}`; // Combine names
        }

        if (localData.color !== remoteData.color) {
            merged.color = localData.color; // Prefer client color
        }

        merged.lastModified = new Date().toISOString();
        merged.mergedFrom = {
            local: localData.lastModified,
            remote: remoteData.lastModified,
        };

        return {
            resolved: true,
            data: merged,
            strategy: "merge",
            conflicts: ["name", "color"],
        };
    }

    static mergeBookmarks(localData, remoteData) {
        const merged = { ...remoteData };

        // Merge title and description
        if (localData.title !== remoteData.title) {
            merged.title = `${remoteData.title} | ${localData.title}`;
        }

        if (localData.description !== remoteData.description) {
            merged.description = `${remoteData.description}\n\n${localData.description}`;
        }

        // Merge tags
        const localTags = new Set(localData.tags || []);
        const remoteTags = new Set(remoteData.tags || []);
        merged.tags = [...new Set([...localTags, ...remoteTags])];

        // Merge categories (if different, use both)
        const localCategories = new Set(localData.categories || []);
        const remoteCategories = new Set(remoteData.categories || []);
        merged.categories = [
            ...new Set([...localCategories, ...remoteCategories]),
        ];

        merged.lastModified = new Date().toISOString();
        merged.mergedFrom = {
            local: localData.lastModified,
            remote: remoteData.lastModified,
        };

        return {
            resolved: true,
            data: merged,
            strategy: "merge",
            conflicts: ["title", "description", "tags", "categories"],
        };
    }

    static async recordConflict(
        userId,
        entityType,
        entityId,
        localData,
        remoteData,
        resolution,
    ) {
        const conflictRecord = {
            userId,
            entityType,
            entityId,
            localData,
            remoteData,
            resolution,
            timestamp: new Date(),
            resolved: resolution.resolved,
        };

        // Store conflict record for audit/history
        // This could be stored in a separate conflicts table
        console.log("Conflict recorded:", conflictRecord);

        return conflictRecord;
    }

    static async getUnresolvedConflicts(userId) {
        return await SyncRecord.findAll({
            where: {
                userId,
                syncStatus: "conflict",
            },
            order: [["timestamp", "DESC"]],
        });
    }

    static async resolveConflictById(conflictId, resolutionStrategy, userId) {
        const conflict = await SyncRecord.findOne({
            where: {
                id: conflictId,
                userId,
                syncStatus: "conflict",
            },
        });

        if (!conflict) {
            throw new Error("Conflict not found");
        }

        // Get the actual data from conflict resolution field
        const { localData, remoteData } = conflict.conflictResolution;

        const resolution = this.resolveConflict(
            conflict.entityType,
            localData,
            remoteData,
            resolutionStrategy,
        );

        if (resolution.resolved) {
            await SyncManager.markAsSynced([conflictId]);

            // Record the resolution for audit
            await this.recordConflict(
                userId,
                conflict.entityType,
                conflict.entityId,
                localData,
                remoteData,
                resolution,
            );
        }

        return resolution;
    }
}

module.exports = ConflictResolver;
