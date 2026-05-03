const { DataTypes } = require("sequelize");
const { sequelize } = require("./database");

// Import all models
const User = require("./models/User");
const Device = require("./models/Device");
const SyncRecord = require("./models/SyncRecord");
const SyncStatus = require("./models/SyncStatus");

// Define associations
User.hasMany(Device, {
    foreignKey: "userId",
    as: "devices",
});

Device.belongsTo(User, {
    foreignKey: "userId",
    as: "user",
});

User.hasMany(SyncRecord, {
    foreignKey: "userId",
    as: "syncRecords",
});

SyncRecord.belongsTo(User, {
    foreignKey: "userId",
    as: "user",
});

User.hasMany(SyncStatus, {
    foreignKey: "userId",
    as: "syncStatuses",
});

SyncStatus.belongsTo(User, {
    foreignKey: "userId",
    as: "user",
});

// Initialize all models
async function initializeModels() {
    try {
        // Sync all models
        await sequelize.sync({ alter: true });
        console.log("All models synchronized successfully");

        // Create indexes for better performance
        await createIndexes();
    } catch (error) {
        console.error("Error initializing models:", error);
        throw error;
    }
}

async function createIndexes() {
    // Create additional indexes for sync performance
    const indexes = [
        // SyncRecord indexes
        "CREATE INDEX IF NOT EXISTS idx_sync_records_user_timestamp ON sync_records(user_id, timestamp DESC)",
        "CREATE INDEX IF NOT EXISTS idx_sync_records_entity ON sync_records(entity_type, entity_id, timestamp DESC)",
        "CREATE INDEX IF NOT EXISTS idx_sync_records_status ON sync_records(sync_status, retry_count)",

        // SyncStatus indexes
        "CREATE INDEX IF NOT EXISTS idx_sync_status_user_device ON sync_status(user_id, device_id, started_at DESC)",
        "CREATE INDEX IF NOT EXISTS idx_sync_status_status ON sync_status(status, started_at DESC)",

        // Device indexes
        "CREATE INDEX IF NOT EXISTS idx_devices_user_active ON devices(user_id, is_active)",
        "CREATE INDEX IF NOT EXISTS idx_devices_last_seen ON devices(last_seen_at DESC)",
    ];

    for (const indexSQL of indexes) {
        try {
            await sequelize.query(indexSQL);
        } catch (error) {
            console.warn("Failed to create index:", indexSQL, error.message);
        }
    }
}

module.exports = {
    User,
    Device,
    SyncRecord,
    SyncStatus,
    initializeModels,
};
