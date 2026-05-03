const jwt = require("jsonwebtoken");
const { Device } = require("../models");

// Authentication middleware
const authenticateToken = async (req, res, next) => {
    try {
        const authHeader = req.headers["authorization"];
        const token = authHeader && authHeader.split(" ")[1];

        if (!token) {
            return res.status(401).json({
                success: false,
                error: "Access token required",
            });
        }

        const decoded = jwt.verify(token, process.env.JWT_SECRET);

        // Verify device if device token is provided
        if (decoded.deviceToken) {
            const device = await Device.findOne({
                where: {
                    syncToken: decoded.deviceToken,
                    isActive: true,
                },
            });

            if (!device) {
                return res.status(401).json({
                    success: false,
                    error: "Invalid device token",
                });
            }

            req.device = device;
        }

        req.user = decoded;
        next();
    } catch (error) {
        console.error("Authentication error:", error);
        return res.status(403).json({
            success: false,
            error: "Invalid or expired token",
        });
    }
};

// Device validation middleware
const validateDevice = async (req, res, next) => {
    try {
        const { deviceId } = req.body;
        const userId = req.user.id;

        if (!deviceId) {
            return res.status(400).json({
                success: false,
                error: "Device ID is required",
            });
        }

        const isValid = await DeviceManager.validateDeviceOwnership(
            deviceId,
            userId,
        );

        if (!isValid) {
            return res.status(403).json({
                success: false,
                error: "Invalid device or unauthorized access",
            });
        }

        next();
    } catch (error) {
        console.error("Device validation error:", error);
        return res.status(500).json({
            success: false,
            error: "Device validation failed",
        });
    }
};

// Rate limiting for sync operations
const syncRateLimit = (windowMs = 60000, max = 10) => {
    return rateLimit({
        windowMs,
        max,
        message: {
            success: false,
            error: "Too many sync requests, please try again later.",
        },
        standardHeaders: true,
        legacyHeaders: false,
    });
};

// Validation middleware for device registration
const validateDeviceRegistration = (req, res, next) => {
    const { deviceId, deviceName, deviceType } = req.body;

    const errors = [];

    if (!deviceId || deviceId.trim().length === 0) {
        errors.push("Device ID is required");
    }

    if (!deviceName || deviceName.trim().length === 0) {
        errors.push("Device name is required");
    }

    if (
        !deviceType ||
        !["mobile", "desktop", "tablet", "web"].includes(deviceType)
    ) {
        errors.push(
            "Valid device type is required (mobile, desktop, tablet, web)",
        );
    }

    if (errors.length > 0) {
        return res.status(400).json({
            success: false,
            errors: errors,
        });
    }

    next();
};

// Error handling middleware
const errorHandler = (err, req, res, next) => {
    console.error("Error:", err);

    if (err.name === "ValidationError") {
        return res.status(400).json({
            success: false,
            error: "Validation error",
            details: err.message,
        });
    }

    if (err.name === "SequelizeValidationError") {
        return res.status(400).json({
            success: false,
            error: "Database validation error",
            details: err.errors.map((e) => e.message),
        });
    }

    if (err.name === "SequelizeUniqueConstraintError") {
        return res.status(409).json({
            success: false,
            error: "Duplicate entry",
            details: err.errors.map((e) => e.message),
        });
    }

    if (err.name === "JsonWebTokenError") {
        return res.status(401).json({
            success: false,
            error: "Invalid token",
        });
    }

    if (err.name === "TokenExpiredError") {
        return res.status(401).json({
            success: false,
            error: "Token expired",
        });
    }

    // Default error response
    res.status(500).json({
        success: false,
        error: "Internal server error",
        message:
            process.env.NODE_ENV === "development"
                ? err.message
                : "Something went wrong",
    });
};

module.exports = {
    authenticateToken,
    validateDevice,
    validateDeviceRegistration,
    syncRateLimit,
    errorHandler,
};
