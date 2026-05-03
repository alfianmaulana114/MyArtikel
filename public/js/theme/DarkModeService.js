/**
 * DarkModeService - Comprehensive dark mode management with Frieren-inspired design
 * Handles theme switching, persistence, system preference detection, and accessibility
 */
class DarkModeService {
    constructor(options = {}) {
        this.options = {
            storageKey: "darkModePreference",
            systemPreferenceKey: "darkModeSystemPreference",
            transitionDuration: 300,
            enableTransitions: true,
            respectSystemPreference: true,
            autoDetectSystemChange: true,
            defaultTheme: "light",
            ...options,
        };

        this.currentTheme = "light";
        this.systemPreference = null;
        this.isTransitioning = false;
        this.observers = new Set();
        this.systemObserver = null;

        // Frieren-inspired earth-tone color palette
        this.colorPalettes = {
            light: {
                // Primary earth tones
                "--primary-50": "#faf9f7",
                "--primary-100": "#f5f2ec",
                "--primary-200": "#e8e1d5",
                "--primary-300": "#d4c5b0",
                "--primary-400": "#b8a082",
                "--primary-500": "#9c7a54",
                "--primary-600": "#7d5f3f",
                "--primary-700": "#634c32",
                "--primary-800": "#4a3a28",
                "--primary-900": "#3d3122",

                // Secondary forest greens
                "--secondary-50": "#f6f7f4",
                "--secondary-100": "#e9ede3",
                "--secondary-200": "#d4dbc7",
                "--secondary-300": "#b8c2a0",
                "--secondary-400": "#9aa674",
                "--secondary-500": "#7d8a52",
                "--secondary-600": "#626d3f",
                "--secondary-700": "#4d5633",
                "--secondary-800": "#3d442a",
                "--secondary-900": "#343a24",

                // Accent autumn colors
                "--accent-50": "#fdf8f3",
                "--accent-100": "#faede0",
                "--accent-200": "#f4d9c1",
                "--accent-300": "#edbe95",
                "--accent-400": "#e49c64",
                "--accent-500": "#dc7c3a",
                "--accent-600": "#c4652c",
                "--accent-700": "#a35026",
                "--accent-800": "#854125",
                "--accent-900": "#6d3723",

                // Neutral stone colors
                "--neutral-50": "#fafaf9",
                "--neutral-100": "#f5f5f4",
                "--neutral-200": "#e7e5e4",
                "--neutral-300": "#d6d3d1",
                "--neutral-400": "#a8a29e",
                "--neutral-500": "#78716c",
                "--neutral-600": "#57534e",
                "--neutral-700": "#44403c",
                "--neutral-800": "#292524",
                "--neutral-900": "#1c1917",

                // Background and surface colors
                "--bg-primary": "#faf9f7",
                "--bg-secondary": "#f5f2ec",
                "--bg-tertiary": "#ede8e0",
                "--surface-primary": "#ffffff",
                "--surface-secondary": "#f8f6f2",
                "--surface-tertiary": "#f1ede6",

                // Text colors
                "--text-primary": "#1c1917",
                "--text-secondary": "#44403c",
                "--text-tertiary": "#78716c",
                "--text-muted": "#a8a29e",
                "--text-inverse": "#ffffff",

                // Border and divider colors
                "--border-primary": "#e7e5e4",
                "--border-secondary": "#d6d3d1",
                "--border-tertiary": "#a8a29e",
                "--divider-primary": "#e7e5e4",
                "--divider-secondary": "#d6d3d1",

                // Shadow colors
                "--shadow-primary": "rgba(28, 25, 23, 0.1)",
                "--shadow-secondary": "rgba(28, 25, 23, 0.2)",
                "--shadow-tertiary": "rgba(28, 25, 23, 0.3)",

                // Interactive states
                "--hover-bg": "#f8f6f2",
                "--hover-border": "#d4c5b0",
                "--active-bg": "#ede8e0",
                "--active-border": "#b8a082",
                "--focus-ring": "rgba(156, 122, 84, 0.3)",

                // Status colors
                "--success": "#065f46",
                "--success-bg": "#d1fae5",
                "--warning": "#92400e",
                "--warning-bg": "#fef3c7",
                "--error": "#991b1b",
                "--error-bg": "#fee2e2",
                "--info": "#1e40af",
                "--info-bg": "#dbeafe",

                // Gradient backgrounds
                "--gradient-primary":
                    "linear-gradient(135deg, #9c7a54 0%, #7d5f3f 100%)",
                "--gradient-secondary":
                    "linear-gradient(135deg, #7d8a52 0%, #626d3f 100%)",
                "--gradient-accent":
                    "linear-gradient(135deg, #dc7c3a 0%, #c4652c 100%)",
                "--gradient-surface":
                    "linear-gradient(135deg, #f5f2ec 0%, #ede8e0 100%)",
            },
            dark: {
                // Primary earth tones (darkened)
                "--primary-50": "#2a2622",
                "--primary-100": "#3d3122",
                "--primary-200": "#4a3a28",
                "--primary-300": "#634c32",
                "--primary-400": "#7d5f3f",
                "--primary-500": "#9c7a54",
                "--primary-600": "#b8a082",
                "--primary-700": "#d4c5b0",
                "--primary-800": "#e8e1d5",
                "--primary-900": "#f5f2ec",

                // Secondary forest greens (darkened)
                "--secondary-50": "#2a2f24",
                "--secondary-100": "#343a24",
                "--secondary-200": "#3d442a",
                "--secondary-300": "#4d5633",
                "--secondary-400": "#626d3f",
                "--secondary-500": "#7d8a52",
                "--secondary-600": "#9aa674",
                "--secondary-700": "#b8c2a0",
                "--secondary-800": "#d4dbc7",
                "--secondary-900": "#e9ede3",

                // Accent autumn colors (darkened)
                "--accent-50": "#3d2b1f",
                "--accent-100": "#6d3723",
                "--accent-200": "#854125",
                "--accent-300": "#a35026",
                "--accent-400": "#c4652c",
                "--accent-500": "#dc7c3a",
                "--accent-600": "#e49c64",
                "--accent-700": "#edbe95",
                "--accent-800": "#f4d9c1",
                "--accent-900": "#faede0",

                // Neutral stone colors (darkened)
                "--neutral-50": "#1c1917",
                "--neutral-100": "#292524",
                "--neutral-200": "#44403c",
                "--neutral-300": "#57534e",
                "--neutral-400": "#78716c",
                "--neutral-500": "#a8a29e",
                "--neutral-600": "#d6d3d1",
                "--neutral-700": "#e7e5e4",
                "--neutral-800": "#f5f5f4",
                "--neutral-900": "#fafaf9",

                // Background and surface colors (dark mode)
                "--bg-primary": "#1c1917",
                "--bg-secondary": "#292524",
                "--bg-tertiary": "#44403c",
                "--surface-primary": "#292524",
                "--surface-secondary": "#44403c",
                "--surface-tertiary": "#57534e",

                // Text colors (dark mode)
                "--text-primary": "#fafaf9",
                "--text-secondary": "#f5f5f4",
                "--text-tertiary": "#e7e5e4",
                "--text-muted": "#d6d3d1",
                "--text-inverse": "#1c1917",

                // Border and divider colors (dark mode)
                "--border-primary": "#44403c",
                "--border-secondary": "#57534e",
                "--border-tertiary": "#78716c",
                "--divider-primary": "#44403c",
                "--divider-secondary": "#57534e",

                // Shadow colors (dark mode)
                "--shadow-primary": "rgba(0, 0, 0, 0.3)",
                "--shadow-secondary": "rgba(0, 0, 0, 0.4)",
                "--shadow-tertiary": "rgba(0, 0, 0, 0.5)",

                // Interactive states (dark mode)
                "--hover-bg": "#44403c",
                "--hover-border": "#634c32",
                "--active-bg": "#57534e",
                "--active-border": "#7d5f3f",
                "--focus-ring": "rgba(156, 122, 84, 0.5)",

                // Status colors (dark mode)
                "--success": "#34d399",
                "--success-bg": "#065f46",
                "--warning": "#fbbf24",
                "--warning-bg": "#92400e",
                "--error": "#f87171",
                "--error-bg": "#991b1b",
                "--info": "#60a5fa",
                "--info-bg": "#1e40af",

                // Gradient backgrounds (dark mode)
                "--gradient-primary":
                    "linear-gradient(135deg, #9c7a54 0%, #7d5f3f 100%)",
                "--gradient-secondary":
                    "linear-gradient(135deg, #7d8a52 0%, #626d3f 100%)",
                "--gradient-accent":
                    "linear-gradient(135deg, #dc7c3a 0%, #c4652c 100%)",
                "--gradient-surface":
                    "linear-gradient(135deg, #44403c 0%, #57534e 100%)",
            },
        };

        this.init();
    }

    /**
     * Initialize the dark mode service
     */
    init() {
        this.detectSystemPreference();
        this.loadUserPreference();
        this.applyInitialTheme();
        this.setupSystemPreferenceObserver();
        this.bindEvents();

        // Emit initial theme event
        this.emit("theme:initialized", {
            theme: this.currentTheme,
            systemPreference: this.systemPreference,
        });
    }

    /**
     * Detect system preference for dark mode
     */
    detectSystemPreference() {
        if (window.matchMedia) {
            this.systemPreference = window.matchMedia(
                "(prefers-color-scheme: dark)",
            ).matches
                ? "dark"
                : "light";
        } else {
            this.systemPreference = "light";
        }
    }

    /**
     * Load user preference from storage
     */
    loadUserPreference() {
        try {
            const stored = localStorage.getItem(this.options.storageKey);
            if (stored) {
                const preference = JSON.parse(stored);
                if (
                    preference.theme &&
                    ["light", "dark", "auto"].includes(preference.theme)
                ) {
                    this.currentTheme = preference.theme;
                    return;
                }
            }
        } catch (error) {
            console.warn("Failed to load dark mode preference:", error);
        }

        // Use system preference if no user preference or auto mode
        if (this.options.respectSystemPreference) {
            this.currentTheme = "auto";
        } else {
            this.currentTheme = this.options.defaultTheme;
        }
    }

    /**
     * Apply initial theme without flash
     */
    applyInitialTheme() {
        const theme = this.getEffectiveTheme();
        this.applyTheme(theme, false); // No transition on initial load

        // Add theme class to document element
        document.documentElement.setAttribute("data-theme", theme);
        document.documentElement.classList.remove("theme-loading");
        document.documentElement.classList.add("theme-loaded");
    }

    /**
     * Get the effective theme (considering auto mode)
     */
    getEffectiveTheme() {
        if (this.currentTheme === "auto") {
            return this.systemPreference;
        }
        return this.currentTheme;
    }

    /**
     * Apply theme to document
     */
    applyTheme(theme, withTransition = true) {
        if (this.isTransitioning) return;

        const effectiveTheme = theme || this.getEffectiveTheme();
        const colors = this.colorPalettes[effectiveTheme];

        if (!colors) {
            console.warn(`Unknown theme: ${effectiveTheme}`);
            return;
        }

        this.isTransitioning = true;

        // Apply transition class if enabled
        if (withTransition && this.options.enableTransitions) {
            document.documentElement.classList.add("theme-transitioning");
        }

        // Apply colors to CSS custom properties
        Object.entries(colors).forEach(([property, value]) => {
            document.documentElement.style.setProperty(property, value);
        });

        const previousTheme =
            document.documentElement.getAttribute("data-theme");

        // Update theme attribute
        document.documentElement.setAttribute("data-theme", effectiveTheme);

        // Emit theme change event
        this.emit("theme:changed", {
            theme: effectiveTheme,
            previousTheme,
            colors: colors,
        });

        // Remove transition class after delay
        if (withTransition && this.options.enableTransitions) {
            setTimeout(() => {
                document.documentElement.classList.remove(
                    "theme-transitioning",
                );
                this.isTransitioning = false;
                this.emit("theme:transitioned", { theme: effectiveTheme });
            }, this.options.transitionDuration);
        } else {
            this.isTransitioning = false;
        }
    }

    /**
     * Toggle between light and dark themes
     */
    toggle() {
        const current = this.getEffectiveTheme();
        const newTheme = current === "light" ? "dark" : "light";
        this.setTheme(newTheme);
        return newTheme;
    }

    /**
     * Set theme (light, dark, or auto)
     */
    setTheme(theme) {
        if (!["light", "dark", "auto"].includes(theme)) {
            console.warn(
                `Invalid theme: ${theme}. Use 'light', 'dark', or 'auto'.`,
            );
            return;
        }

        const previousTheme = this.getEffectiveTheme();
        this.currentTheme = theme;

        // Save preference
        this.saveUserPreference();

        // Apply theme
        const effectiveTheme = this.getEffectiveTheme();
        if (effectiveTheme !== previousTheme) {
            this.applyTheme(effectiveTheme);
        }

        // Emit theme set event
        this.emit("theme:set", {
            theme: theme,
            effectiveTheme: effectiveTheme,
            previousTheme: previousTheme,
        });
    }

    /**
     * Save user preference to storage
     */
    saveUserPreference() {
        try {
            const preference = {
                theme: this.currentTheme,
                timestamp: Date.now(),
            };
            localStorage.setItem(
                this.options.storageKey,
                JSON.stringify(preference),
            );
        } catch (error) {
            console.warn("Failed to save dark mode preference:", error);
        }
    }

    /**
     * Setup system preference observer
     */
    setupSystemPreferenceObserver() {
        if (!window.matchMedia || !this.options.autoDetectSystemChange) return;

        const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");

        const handleChange = (e) => {
            const newSystemPreference = e.matches ? "dark" : "light";
            if (newSystemPreference !== this.systemPreference) {
                this.systemPreference = newSystemPreference;

                // Save system preference
                try {
                    localStorage.setItem(
                        this.options.systemPreferenceKey,
                        newSystemPreference,
                    );
                } catch (error) {
                    console.warn("Failed to save system preference:", error);
                }

                // Apply new theme if in auto mode
                if (this.currentTheme === "auto") {
                    this.applyTheme(newSystemPreference);

                    this.emit("theme:systemChanged", {
                        systemPreference: newSystemPreference,
                        effectiveTheme: newSystemPreference,
                    });
                }
            }
        };

        // Modern browsers
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener("change", handleChange);
        } else if (mediaQuery.addListener) {
            // Older browsers
            mediaQuery.addListener(handleChange);
        }

        this.systemObserver = { mediaQuery, handleChange };
    }

    /**
     * Bind events
     */
    bindEvents() {
        // Listen for visibility changes to apply theme when tab becomes active
        document.addEventListener("visibilitychange", () => {
            if (!document.hidden) {
                // Re-apply theme when tab becomes visible
                const effectiveTheme = this.getEffectiveTheme();
                this.applyTheme(effectiveTheme, false);
            }
        });

        // Listen for storage changes (cross-tab synchronization)
        window.addEventListener("storage", (e) => {
            if (e.key === this.options.storageKey) {
                try {
                    const preference = JSON.parse(e.newValue);
                    if (
                        preference &&
                        preference.theme &&
                        preference.theme !== this.currentTheme
                    ) {
                        this.currentTheme = preference.theme;
                        const effectiveTheme = this.getEffectiveTheme();
                        this.applyTheme(effectiveTheme);
                    }
                } catch (error) {
                    console.warn("Failed to handle storage change:", error);
                }
            }
        });
    }

    /**
     * Get current theme
     */
    getTheme() {
        return this.currentTheme;
    }

    /**
     * Check if current theme is dark
     */
    isDark() {
        return this.getEffectiveTheme() === "dark";
    }

    /**
     * Check if current theme is light
     */
    isLight() {
        return this.getEffectiveTheme() === "light";
    }

    /**
     * Get current color palette
     */
    getCurrentColors() {
        const effectiveTheme = this.getEffectiveTheme();
        return this.colorPalettes[effectiveTheme];
    }

    /**
     * Get specific color value
     */
    getColor(colorName) {
        const colors = this.getCurrentColors();
        return colors[colorName] || null;
    }

    /**
     * Add event observer
     */
    on(event, callback) {
        if (!this.observers.has(event)) {
            this.observers.set(event, new Set());
        }
        this.observers.get(event).add(callback);
    }

    /**
     * Remove event observer
     */
    off(event, callback) {
        if (this.observers.has(event)) {
            this.observers.get(event).delete(callback);
        }
    }

    /**
     * Emit event
     */
    emit(event, data) {
        if (this.observers.has(event)) {
            this.observers.get(event).forEach((callback) => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(
                        `Error in theme event listener for ${event}:`,
                        error,
                    );
                }
            });
        }
    }

    /**
     * Get theme information
     */
    getInfo() {
        return {
            currentTheme: this.currentTheme,
            effectiveTheme: this.getEffectiveTheme(),
            systemPreference: this.systemPreference,
            isDark: this.isDark(),
            isLight: this.isLight(),
            colors: this.getCurrentColors(),
            options: this.options,
        };
    }

    /**
     * Destroy the service and cleanup
     */
    destroy() {
        // Remove system observer
        if (this.systemObserver) {
            const { mediaQuery, handleChange } = this.systemObserver;
            if (mediaQuery.removeEventListener) {
                mediaQuery.removeEventListener("change", handleChange);
            } else if (mediaQuery.removeListener) {
                mediaQuery.removeListener(handleChange);
            }
        }

        // Clear observers
        this.observers.clear();

        // Remove theme classes and attributes
        document.documentElement.removeAttribute("data-theme");
        document.documentElement.classList.remove(
            "theme-loaded",
            "theme-transitioning",
        );

        // Clear all custom properties
        Object.keys(this.colorPalettes.light).forEach((property) => {
            document.documentElement.style.removeProperty(property);
        });
    }

    /**
     * Delay utility
     */
    delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }
}

// Create global instance
window.DarkModeService = new DarkModeService({
    transitionDuration: 400,
    enableTransitions: true,
    respectSystemPreference: true,
    autoDetectSystemChange: true,
    defaultTheme: "light",
});

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
    // Service is already initialized in constructor
    console.log(
        "DarkModeService initialized:",
        window.DarkModeService.getInfo(),
    );
});
