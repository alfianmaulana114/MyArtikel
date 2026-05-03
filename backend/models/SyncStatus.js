const { DataTypes } = require("sequelize");
const { sequelize } = require("../config/database");

const SyncStatus = sequelize.define(
    "SyncStatus",
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
        syncType: {
            type: DataTypes.ENUM("full", "incremental", "manual", "background"),
            allowNull: false,
            field: "sync_type",
        },
        status: {
            type: DataTypes.ENUM(
                "pending",
                "in_progress",
                "completed",
                "failed",
                "cancelled",
            ),
            defaultValue: "pending",
            field: "status",
        },
        startedAt: {
            type: DataTypes.DATE,
            allowNull: false,
            field: "started_at",
        },
        completedAt: {
            type: DataTypes.DATE,
            allowNull: true,
            field: "completed_at",
        },
        itemsProcessed: {
            type: DataTypes.INTEGER,
            defaultValue: 0,
            field: "items_processed",
        },
        itemsTotal: {
            type: DataTypes.INTEGER,
            defaultValue: 0,
            field: "items_total",
        },
        conflictsDetected: {
            type: DataTypes.INTEGER,
            defaultValue: 0,
            field: "conflicts_detected",
        },
        conflictsResolved: {
            type: DataTypes.INTEGER,
            defaultValue: 0,
            field: "conflicts_resolved",
        },
        errors: {
            type: DataTypes.JSONB,
            allowNull: true,
            field: "errors",
        },
        performanceMetrics: {
            type: DataTypes.JSONB,
            allowNull: true,
            field: "performance_metrics",
        },
        networkStats: {
            type: DataTypes.JSONB,
            allowNull: true,
            field: "network_stats",
        },
    },
    {
        tableName: "sync_status",
        timestamps: true,
        indexes: [
            {
                fields: ["user_id", "device_id", "started_at"],
            },
            {
                fields: ["status", "started_at"],
            },
            {
                fields: ["sync_type", "status"],
            },
        ],
    },
);

module.exports = SyncStatus;
