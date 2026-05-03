const crypto = require("crypto");
const Device = require("../models/Device");
const { sequelize } = require("../config/database");

class DeviceManager {
    static async registerDevice(userId, deviceInfo) {
        const transaction = await sequelize.transaction();

        try {
            const {
                deviceId,
                deviceName,
                deviceType,
                platform,
                appVersion,
                pushToken,
                metadata,
            } = deviceInfo;

            // Generate unique sync token
            const syncToken = crypto.randomBytes(32).toString("hex");

            // Check if device already exists
            let device = await Device.findOne({
                where: { deviceId },
                transaction,
            });

            if (device) {
                // Update existing device
                await device.update(
                    {
                        userId,
                        deviceName,
                        deviceType,
                        platform,
                        appVersion,
                        pushToken,
                        metadata,
                        isActive: true,
                        lastSeenAt: new Date(),
                    },
                    { transaction },
                );
            } else {
                // Create new device
                device = await Device.create(
                    {
                        deviceId,
                        userId,
                        deviceName,
                        deviceType,
                        platform,
                        appVersion,
                        syncToken,
                        pushToken,
                        metadata,
                        isActive: true,
                    },
                    { transaction },
                );
            }

            // Deactivate other devices if limit exceeded (e.g., max 5 devices per user)
            const deviceCount = await Device.count({
                where: { userId, isActive: true },
                transaction,
            });

            if (deviceCount > 5) {
                const oldestDevices = await Device.findAll({
                    where: { userId, isActive: true },
                    order: [["lastSeenAt", "ASC"]],
                    limit: deviceCount - 5,
                    transaction,
                });

                for (const oldDevice of oldestDevices) {
                    await oldDevice.update(
                        { isActive: false },
                        { transaction },
                    );
                }
            }

            await transaction.commit();
            return device;
        } catch (error) {
            await transaction.rollback();
            throw error;
        }
    }

    static async getDeviceByToken(syncToken) {
        return await Device.findOne({
            where: { syncToken, isActive: true },
            include: ["user"],
        });
    }

    static async getUserDevices(userId) {
        return await Device.findAll({
            where: { userId, isActive: true },
            order: [["lastSeenAt", "DESC"]],
        });
    }

    static async updateDeviceLastSeen(deviceId) {
        return await Device.update(
            { lastSeenAt: new Date() },
            { where: { deviceId } },
        );
    }

    static async updateDeviceSyncTime(deviceId, syncTime = new Date()) {
        return await Device.update(
            { lastSyncAt: syncTime },
            { where: { deviceId } },
        );
    }

    static async deactivateDevice(deviceId, userId) {
        return await Device.update(
            { isActive: false },
            { where: { deviceId, userId } },
        );
    }

    static async validateDeviceOwnership(deviceId, userId) {
        const device = await Device.findOne({
            where: { deviceId, userId, isActive: true },
        });
        return !!device;
    }

    static async cleanupInactiveDevices(daysInactive = 30) {
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - daysInactive);

        return await Device.update(
            { isActive: false },
            {
                where: {
                    lastSeenAt: {
                        [sequelize.Sequelize.Op.lt]: cutoffDate,
                    },
                    isActive: true,
                },
            },
        );
    }
}

module.exports = DeviceManager;
