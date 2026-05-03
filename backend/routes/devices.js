const express = require("express");
const DeviceManager = require("../services/DeviceManager");
const { authenticateToken } = require("../middleware/auth");
const { validateDeviceRegistration } = require("../middleware/validation");

const router = express.Router();

// Register new device
router.post(
    "/register",
    authenticateToken,
    validateDeviceRegistration,
    async (req, res) => {
        try {
            const {
                deviceId,
                deviceName,
                deviceType,
                platform,
                appVersion,
                pushToken,
                metadata,
            } = req.body;
            const userId = req.user.id;

            const device = await DeviceManager.registerDevice(userId, {
                deviceId,
                deviceName,
                deviceType,
                platform,
                appVersion,
                pushToken,
                metadata,
            });

            res.status(201).json({
                success: true,
                data: {
                    device: {
                        id: device.id,
                        deviceId: device.deviceId,
                        deviceName: device.deviceName,
                        deviceType: device.deviceType,
                        syncToken: device.syncToken,
                        lastSyncAt: device.lastSyncAt,
                    },
                },
            });
        } catch (error) {
            console.error("Device registration error:", error);
            res.status(500).json({
                success: false,
                error: "Failed to register device",
            });
        }
    },
);

// Get user's devices
router.get("/devices", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const devices = await DeviceManager.getUserDevices(userId);

        res.json({
            success: true,
            data: {
                devices: devices.map((device) => ({
                    id: device.id,
                    deviceId: device.deviceId,
                    deviceName: device.deviceName,
                    deviceType: device.deviceType,
                    platform: device.platform,
                    lastSyncAt: device.lastSyncAt,
                    lastSeenAt: device.lastSeenAt,
                    isActive: device.isActive,
                })),
            },
        });
    } catch (error) {
        console.error("Get devices error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get devices",
        });
    }
});

// Deactivate device
router.delete("/devices/:deviceId", authenticateToken, async (req, res) => {
    try {
        const { deviceId } = req.params;
        const userId = req.user.id;

        const result = await DeviceManager.deactivateDevice(deviceId, userId);

        if (result[0] === 0) {
            return res.status(404).json({
                success: false,
                error: "Device not found",
            });
        }

        res.json({
            success: true,
            message: "Device deactivated successfully",
        });
    } catch (error) {
        console.error("Deactivate device error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to deactivate device",
        });
    }
});

// Update device last seen
router.post(
    "/devices/:deviceId/heartbeat",
    authenticateToken,
    async (req, res) => {
        try {
            const { deviceId } = req.params;
            const userId = req.user.id;

            // Validate device ownership
            const isValid = await DeviceManager.validateDeviceOwnership(
                deviceId,
                userId,
            );
            if (!isValid) {
                return res.status(403).json({
                    success: false,
                    error: "Unauthorized device access",
                });
            }

            await DeviceManager.updateDeviceLastSeen(deviceId);

            res.json({
                success: true,
                message: "Device heartbeat updated",
            });
        } catch (error) {
            console.error("Device heartbeat error:", error);
            res.status(500).json({
                success: false,
                error: "Failed to update device heartbeat",
            });
        }
    },
);

module.exports = router;
