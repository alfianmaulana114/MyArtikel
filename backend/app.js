const express = require("express");
const cors = require("cors");
const helmet = require("helmet");
const compression = require("compression");
const rateLimit = require("express-rate-limit");
const { sequelize } = require("./config/database");
const { initializeModels } = require("./models");

// Import routes
const deviceRoutes = require("./routes/devices");
const syncRoutes = require("./routes/sync");
const syncStatusRoutes = require("./routes/sync-status");

// Import services
const SyncOrchestrator = require("./services/SyncOrchestrator");

// Import middleware
const { authenticateToken } = require("./middleware/auth");
const { errorHandler } = require("./middleware/errorHandler");

const app = express();
const PORT = process.env.PORT || 3000;

// Initialize sync orchestrator
const syncOrchestrator = new SyncOrchestrator();

// Middleware
app.use(helmet());
app.use(compression());
app.use(
    cors({
        origin: process.env.FRONTEND_URL || "http://localhost:8080",
        credentials: true,
    }),
);

// Rate limiting
const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
    message: "Too many requests from this IP, please try again later.",
});

const syncLimiter = rateLimit({
    windowMs: 60 * 1000, // 1 minute
    max: 10, // limit sync requests to 10 per minute
    message: "Too many sync requests, please try again later.",
});

app.use("/api/", limiter);
app.use("/api/sync", syncLimiter);

app.use(express.json({ limit: "10mb" }));
app.use(express.urlencoded({ extended: true }));

// Routes
app.use("/api/devices", deviceRoutes);
app.use("/api/sync", syncRoutes);
app.use("/api/sync-status", syncStatusRoutes);

// Health check endpoint
app.get("/health", (req, res) => {
    res.json({
        status: "healthy",
        timestamp: new Date().toISOString(),
        uptime: process.uptime(),
        sync: syncOrchestrator.getHealthStatus(),
    });
});

// Protected sync endpoint with orchestration
app.post("/api/sync/orchestrated", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const { deviceId, syncData } = req.body;

        // Add sync job to queue
        const jobId = await syncOrchestrator.addSyncJob(
            userId,
            deviceId,
            syncData,
            "high",
        );

        res.json({
            success: true,
            data: {
                jobId,
                status: "queued",
            },
        });
    } catch (error) {
        console.error("Orchestrated sync error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to queue sync job",
        });
    }
});

// Get sync job status
app.get("/api/sync/jobs/:jobId", authenticateToken, async (req, res) => {
    try {
        const { jobId } = req.params;
        const jobStatus = syncOrchestrator.getSyncJobStatus(jobId);

        if (!jobStatus) {
            return res.status(404).json({
                success: false,
                error: "Sync job not found",
            });
        }

        res.json({
            success: true,
            data: jobStatus,
        });
    } catch (error) {
        console.error("Get sync job status error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get sync job status",
        });
    }
});

// Get active sync jobs
app.get("/api/sync/jobs/active", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const activeJobs = syncOrchestrator.getActiveSyncJobs(userId);

        res.json({
            success: true,
            data: {
                jobs: activeJobs,
            },
        });
    } catch (error) {
        console.error("Get active sync jobs error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get active sync jobs",
        });
    }
});

// Cancel sync job
app.post(
    "/api/sync/jobs/:jobId/cancel",
    authenticateToken,
    async (req, res) => {
        try {
            const { jobId } = req.params;
            const cancelled = await syncOrchestrator.cancelSyncJob(jobId);

            if (!cancelled) {
                return res.status(400).json({
                    success: false,
                    error: "Cannot cancel sync job",
                });
            }

            res.json({
                success: true,
                message: "Sync job cancelled successfully",
            });
        } catch (error) {
            console.error("Cancel sync job error:", error);
            res.status(500).json({
                success: false,
                error: "Failed to cancel sync job",
            });
        }
    },
);

// Get sync statistics
app.get("/api/sync/statistics", authenticateToken, async (req, res) => {
    try {
        const userId = req.user.id;
        const { range = "7d" } = req.query;

        const stats = await syncOrchestrator.getSyncStatistics(userId, range);

        res.json({
            success: true,
            data: {
                statistics: stats,
                range: range,
            },
        });
    } catch (error) {
        console.error("Get sync statistics error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get sync statistics",
        });
    }
});

// Connection statistics
app.get("/api/sync/connections", authenticateToken, async (req, res) => {
    try {
        const stats = syncOrchestrator.getConnectionStatistics();

        res.json({
            success: true,
            data: stats,
        });
    } catch (error) {
        console.error("Get connection statistics error:", error);
        res.status(500).json({
            success: false,
            error: "Failed to get connection statistics",
        });
    }
});

// Error handling middleware
app.use(errorHandler);

// 404 handler
app.use("*", (req, res) => {
    res.status(404).json({
        success: false,
        error: "Route not found",
    });
});

// Start server
const server = app.listen(PORT, async () => {
    console.log(`Server running on port ${PORT}`);

    try {
        // Initialize database
        await sequelize.authenticate();
        console.log("Database connection established");

        // Initialize models
        await initializeModels();

        // Initialize sync orchestrator with WebSocket support
        syncOrchestrator.initialize(server);

        console.log("Sync orchestrator initialized with WebSocket support");
    } catch (error) {
        console.error("Failed to initialize:", error);
        process.exit(1);
    }
});

// Graceful shutdown
process.on("SIGTERM", async () => {
    console.log("SIGTERM received, shutting down gracefully");

    server.close(() => {
        console.log("Server closed");
        process.exit(0);
    });
});

process.on("SIGINT", async () => {
    console.log("SIGINT received, shutting down gracefully");

    server.close(() => {
        console.log("Server closed");
        process.exit(0);
    });
});

module.exports = app;
