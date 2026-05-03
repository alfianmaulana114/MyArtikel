/**
 * Frieren Dark Mode No-Flash Loader
 * Prevents flash of unstyled content during theme switching
 * Must be included in <head> before any other scripts
 */
(function () {
    "use strict";

    // Configuration
    const STORAGE_KEY = "darkModePreference";
    const DEFAULT_THEME = "light";

    /**
     * Get system preference
     */
    function getSystemPreference() {
        if (window.matchMedia) {
            return window.matchMedia("(prefers-color-scheme: dark)").matches
                ? "dark"
                : "light";
        }
        return DEFAULT_THEME;
    }

    /**
     * Get user preference from storage
     */
    function getUserPreference() {
        try {
            const stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                const preference = JSON.parse(stored);
                if (
                    preference.theme &&
                    ["light", "dark", "auto"].includes(preference.theme)
                ) {
                    return preference.theme;
                }
            }
        } catch (error) {
            // Ignore storage errors
        }
        return "auto";
    }

    /**
     * Get effective theme
     */
    function getEffectiveTheme() {
        const userPreference = getUserPreference();
        if (userPreference === "auto") {
            return getSystemPreference();
        }
        return userPreference;
    }

    /**
     * Apply theme immediately
     */
    function applyThemeImmediately() {
        const theme = getEffectiveTheme();
        const html = document.documentElement;

        // Apply the theme attribute immediately (source of truth for CSS variables)
        html.setAttribute("data-theme", theme);

        // Optional Tailwind compatibility (`dark:` variants use `.dark`)
        html.classList.toggle("dark", theme === "dark");

        // Let UA render built-in controls with correct palette
        html.style.colorScheme = theme;

        // Mark ready ASAP so we don't accidentally hide the page
        html.classList.remove("theme-loading");
        html.classList.add("theme-loaded");
    }

    /**
     * Inject base styles to prevent flash
     */
    function injectBaseStyles() {
        const style = document.createElement("style");
        style.textContent = `
            /* Base theme colors to prevent flash */
            html[data-theme="light"] {
                background-color: #fafaf9;
                color: #1c1917;
            }
            
            html[data-theme="dark"] {
                background-color: #1c1917;
                color: #fafaf9;
            }
            
            /* Smooth transitions for theme changes */
            html.theme-transitioning,
            html.theme-transitioning *,
            html.theme-transitioning *::before,
            html.theme-transitioning *::after {
                transition: background-color 400ms cubic-bezier(0.4, 0, 0.2, 1),
                           color 400ms cubic-bezier(0.4, 0, 0.2, 1),
                           border-color 400ms cubic-bezier(0.4, 0, 0.2, 1),
                           box-shadow 400ms cubic-bezier(0.4, 0, 0.2, 1),
                           opacity 400ms cubic-bezier(0.4, 0, 0.2, 1) !important;
            }
        `;

        // Add styles to head
        document.head.appendChild(style);
    }

    /**
     * Monitor system preference changes
     */
    function monitorSystemPreference() {
        if (!window.matchMedia) return;

        const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");

        const handleChange = function (e) {
            const userPreference = getUserPreference();
            if (userPreference === "auto") {
                const newTheme = e.matches ? "dark" : "light";
                document.documentElement.setAttribute("data-theme", newTheme);
                document.documentElement.classList.toggle(
                    "dark",
                    newTheme === "dark",
                );
                document.documentElement.style.colorScheme = newTheme;

                // No need to persist system value; only user preference matters.
            }
        };

        // Modern browsers
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener("change", handleChange);
        } else if (mediaQuery.addListener) {
            // Older browsers
            mediaQuery.addListener(handleChange);
        }
    }

    /**
     * Monitor storage changes (cross-tab synchronization)
     */
    function monitorStorageChanges() {
        window.addEventListener("storage", function (e) {
            if (e.key === STORAGE_KEY) {
                try {
                    const preference = JSON.parse(e.newValue);
                    if (preference && preference.theme) {
                        const effectiveTheme =
                            preference.theme === "auto"
                                ? getSystemPreference()
                                : preference.theme;
                        document.documentElement.setAttribute(
                            "data-theme",
                            effectiveTheme,
                        );
                        document.documentElement.classList.toggle(
                            "dark",
                            effectiveTheme === "dark",
                        );
                        document.documentElement.style.colorScheme =
                            effectiveTheme;
                    }
                } catch (error) {
                    // Ignore parsing errors
                }
            }
        });
    }

    /**
     * Initialize the no-flash loader
     */
    function init() {
        // Inject base styles immediately
        injectBaseStyles();

        // Apply theme immediately
        applyThemeImmediately();

        // Start monitoring
        monitorSystemPreference();
        monitorStorageChanges();

        // Mark as initialized
        window.__frierenDarkModeLoaded = true;
    }

    init();

    // Expose utility functions for other scripts
    window.__frierenDarkMode = {
        getSystemPreference: getSystemPreference,
        getUserPreference: getUserPreference,
        getEffectiveTheme: getEffectiveTheme,
    };
})();
