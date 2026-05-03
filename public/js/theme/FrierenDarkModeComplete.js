/**
 * FrierenDarkModeComplete - Complete dark mode initialization and integration
 * This script orchestrates all dark mode components and provides a unified API
 */

// Frieren Dark Mode Complete System
window.FrierenDarkMode = (function () {
    "use strict";

    let isInitialized = false;
    let darkModeService = null;
    let themeIntegration = null;
    let toggleComponents = [];

    /**
     * Initialize the complete dark mode system
     */
    function init(options = {}) {
        if (isInitialized) {
            console.warn("Frieren Dark Mode already initialized");
            return;
        }

        console.log("🌙 Initializing Frieren Dark Mode System...");

        try {
            // Wait for DOM to be ready
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", () =>
                    initializeComponents(options),
                );
            } else {
                initializeComponents(options);
            }

            isInitialized = true;
            console.log("✅ Frieren Dark Mode System initialized successfully");
        } catch (error) {
            console.error("❌ Failed to initialize Frieren Dark Mode:", error);
            throw error;
        }
    }

    /**
     * Initialize all components
     */
    function initializeComponents(options) {
        // Initialize Dark Mode Service
        if (window.DarkModeService) {
            darkModeService = window.DarkModeService;
            console.log("✅ Dark Mode Service ready");
        } else {
            console.warn("⚠️ Dark Mode Service not available");
        }

        // Initialize Theme Integration
        if (window.FrierenThemeIntegration) {
            themeIntegration = window.FrierenThemeIntegration;
            console.log("✅ Theme Integration ready");
        } else {
            console.warn("⚠️ Theme Integration not available");
        }

        // Initialize Toggle Components
        initializeToggleComponents(options.toggleOptions);

        // Setup global event handlers
        setupGlobalEventHandlers();

        // Setup keyboard shortcuts
        setupKeyboardShortcuts(options.keyboardShortcuts);

        // Initialize testing if in development
        if (options.enableTesting && window.FrierenDarkModeTest) {
            setTimeout(() => {
                console.log("🧪 Running dark mode tests...");
                new FrierenDarkModeTest().runAllTests();
            }, 1000);
        }
    }

    /**
     * Initialize toggle components
     */
    function initializeToggleComponents(toggleOptions = {}) {
        // Find all toggle containers
        const toggleContainers = document.querySelectorAll(
            "[data-frieren-toggle]",
        );

        toggleContainers.forEach((container) => {
            const position = container.getAttribute("data-frieren-toggle");
            const options = {
                container: container,
                position: position,
                size: container.getAttribute("data-toggle-size") || "medium",
                style: container.getAttribute("data-toggle-style") || "ornate",
                showLabel:
                    container.getAttribute("data-toggle-label") !== "false",
                enableAnimation:
                    container.getAttribute("data-toggle-animation") !== "false",
                enableTooltip:
                    container.getAttribute("data-toggle-tooltip") !== "false",
                ...toggleOptions,
            };

            try {
                const toggle = new FrierenDarkModeToggle(options);
                toggleComponents.push(toggle);
                console.log(`✅ Toggle component created for ${position}`);
            } catch (error) {
                console.error(
                    `❌ Failed to create toggle for ${position}:`,
                    error,
                );
            }
        });

        // Create default toggle if none exist
        if (toggleComponents.length === 0) {
            createDefaultToggle(toggleOptions);
        }
    }

    /**
     * Create default toggle component
     */
    function createDefaultToggle(options) {
        // Create floating toggle in top-right corner
        const floatingContainer = document.createElement("div");
        floatingContainer.id = "frieren-dark-mode-toggle";
        floatingContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;

        document.body.appendChild(floatingContainer);

        try {
            const toggle = new FrierenDarkModeToggle({
                container: floatingContainer,
                position: "fixed",
                size: "medium",
                style: "ornate",
                showLabel: false,
                enableAnimation: true,
                enableTooltip: true,
                ...options,
            });

            toggleComponents.push(toggle);
            console.log("✅ Default floating toggle created");
        } catch (error) {
            console.error("❌ Failed to create default toggle:", error);
        }
    }

    /**
     * Setup global event handlers
     */
    function setupGlobalEventHandlers() {
        // Handle visibility changes
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden && darkModeService) {
                // Re-apply theme when tab becomes visible
                const effectiveTheme = darkModeService.getEffectiveTheme();
                darkModeService.applyTheme(effectiveTheme, false);
            }
        });

        // Handle beforeunload to save state
        window.addEventListener("beforeunload", () => {
            if (darkModeService) {
                darkModeService.saveUserPreference();
            }
        });

        // Handle resize for responsive behavior
        window.addEventListener(
            "resize",
            debounce(() => {
                // Re-apply any responsive theme adjustments
                if (darkModeService) {
                    const effectiveTheme = darkModeService.getEffectiveTheme();
                    darkModeService.applyTheme(effectiveTheme, false);
                }
            }, 250),
        );
    }

    /**
     * Setup keyboard shortcuts
     */
    function setupKeyboardShortcuts(shortcuts = {}) {
        const defaultShortcuts = {
            toggle: "Alt+T",
            reset: "Alt+R",
            auto: "Alt+A",
            ...shortcuts,
        };

        document.addEventListener("keydown", (e) => {
            // Toggle theme
            if (isKeyCombo(e, defaultShortcuts.toggle) && darkModeService) {
                e.preventDefault();
                const newTheme = darkModeService.toggle();
                console.log(`Theme toggled to: ${newTheme}`);
            }

            // Reset to system preference
            if (isKeyCombo(e, defaultShortcuts.reset) && darkModeService) {
                e.preventDefault();
                darkModeService.setTheme("auto");
                console.log("Theme reset to system preference");
            }

            // Toggle auto mode
            if (isKeyCombo(e, defaultShortcuts.auto) && darkModeService) {
                e.preventDefault();
                darkModeService.setTheme("auto");
                console.log("Theme set to auto mode");
            }
        });
    }

    /**
     * Check if key combination matches
     */
    function isKeyCombo(event, combo) {
        const keys = combo.toLowerCase().split("+");
        return keys.every((key) => {
            switch (key) {
                case "alt":
                    return event.altKey;
                case "ctrl":
                    return event.ctrlKey;
                case "shift":
                    return event.shiftKey;
                case "meta":
                    return event.metaKey;
                default:
                    return event.key.toLowerCase() === key;
            }
        });
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Get current theme information
     */
    function getThemeInfo() {
        if (!darkModeService) {
            return { error: "Dark mode service not initialized" };
        }

        return darkModeService.getInfo();
    }

    /**
     * Set theme
     */
    function setTheme(theme) {
        if (!darkModeService) {
            console.error("Dark mode service not initialized");
            return false;
        }

        darkModeService.setTheme(theme);
        return true;
    }

    /**
     * Toggle theme
     */
    function toggleTheme() {
        if (!darkModeService) {
            console.error("Dark mode service not initialized");
            return null;
        }

        return darkModeService.toggle();
    }

    /**
     * Get all toggle components
     */
    function getToggleComponents() {
        return toggleComponents;
    }

    /**
     * Add a new toggle component
     */
    function addToggle(container, options = {}) {
        try {
            const toggle = new FrierenDarkModeToggle({
                container: container,
                ...options,
            });

            toggleComponents.push(toggle);
            return toggle;
        } catch (error) {
            console.error("Failed to add toggle component:", error);
            return null;
        }
    }

    /**
     * Remove a toggle component
     */
    function removeToggle(toggle) {
        const index = toggleComponents.indexOf(toggle);
        if (index > -1) {
            toggle.destroy();
            toggleComponents.splice(index, 1);
            return true;
        }
        return false;
    }

    /**
     * Destroy all components
     */
    function destroy() {
        // Destroy all toggle components
        toggleComponents.forEach((toggle) => toggle.destroy());
        toggleComponents = [];

        // Destroy theme integration
        if (themeIntegration) {
            themeIntegration.destroy();
            themeIntegration = null;
        }

        // Destroy dark mode service
        if (darkModeService) {
            darkModeService.destroy();
            darkModeService = null;
        }

        isInitialized = false;
        console.log("🗑️ Frieren Dark Mode System destroyed");
    }

    /**
     * Check if system is initialized
     */
    function isReady() {
        return isInitialized;
    }

    /**
     * Get system status
     */
    function getStatus() {
        return {
            initialized: isInitialized,
            serviceReady: !!darkModeService,
            integrationReady: !!themeIntegration,
            toggleCount: toggleComponents.length,
            currentTheme: darkModeService ? darkModeService.getTheme() : null,
            effectiveTheme: darkModeService
                ? darkModeService.getEffectiveTheme()
                : null,
        };
    }

    // Public API
    return {
        init: init,
        getThemeInfo: getThemeInfo,
        setTheme: setTheme,
        toggleTheme: toggleTheme,
        getToggleComponents: getToggleComponents,
        addToggle: addToggle,
        removeToggle: removeToggle,
        destroy: destroy,
        isReady: isReady,
        getStatus: getStatus,

        // Constants
        VERSION: "1.0.0",
        NAME: "Frieren Dark Mode",
    };
})();

// Auto-initialize with default options
document.addEventListener("DOMContentLoaded", function () {
    // Check if auto-initialization is enabled (default: true)
    const autoInit = document.querySelector(
        'meta[name="frieren-dark-mode-auto-init"]',
    );
    const shouldAutoInit =
        !autoInit || autoInit.getAttribute("content") !== "false";

    if (shouldAutoInit) {
        FrierenDarkMode.init({
            enableTesting:
                window.location.hostname === "localhost" ||
                window.location.hostname === "127.0.0.1",
        });
    }
});

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
    module.exports = FrierenDarkMode;
}
