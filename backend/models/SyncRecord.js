const { DataTypes } = require("sequelize");
const { sequelize } = require("../config/database");

const SyncRecord = sequelize.define(
    "SyncRecord",
    {
        id: {
            type: DataTypes.UUID,
            defaultValue: DataTypes.UUIDV4,
            primaryKey: true,
        },
        userId: {
            type: DataTypes.UUID,
            allowNull: false,
            field: "user_id",
            references: {
                model: "users",
                key: "id",
            },
        },
        deviceId: {
            type: DataTypes.STRING,
            allowNull: false,
            field: "device_id",
        },
        entityType: {
            type: DataTypes.ENUM("article", "note", "tag", "bookmark"),
            allowNull: false,
            field: "entity_type",
        },
        entityId: {
            type: DataTypes.UUID,
            allowNull: false,
            field: "entity_id",
        },
        action: {
            type: DataTypes.ENUM("create", "update", "delete"),
            allowNull: false,
            field: "action",
        },
        timestamp: {
            type: DataTypes.DATE,
            allowNull: false,
            defaultValue: DataTypes.NOW,
            field: "timestamp",
        },
        dataHash: {
            type: DataTypes.STRING,
            allowNull: false,
            field: "data_hash",
        },
        syncStatus: {
            type: DataTypes.ENUM("pending", "synced", "conflict", "error"),
            defaultValue: "pending",
            field: "sync_status",
        },
        conflictResolution: {
            type: DataTypes.JSONB,
            allowNull: true,
            field: "conflict_resolution",
        },
        retryCount: {
            type: DataTypes.INTEGER,
            defaultValue: 0,
            field: "retry_count",
        },
        lastError: {
            type: DataTypes.TEXT,
            allowNull: true,
            field: "last_error",
        },
    },
    {
        tableName: "sync_records",
        timestamps: true,
        indexes: [
            {
                fields: ["user_id", "device_id", "timestamp"],
            },
            {
                fields: ["entity_type", "entity_id", "timestamp"],
            },
            {
                fields: ["sync_status", "retry_count"],
            },
            {
                fields: ["data_hash"],
            },
        ],
    },
);

module.exports = SyncRecord;
