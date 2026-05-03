/**
 * Theme (light/dark/auto) controller.
 *
 * Source of truth:
 * - documentElement[data-theme="light"|"dark"]
 * - localStorage key "darkModePreference" with shape: { theme: "light"|"dark"|"auto" }
 *
 * Notes:
 * - We intentionally keep this small + deterministic (no DOM class injection like "theme integration").
 * - If you ever want Tailwind `dark:` variants, we also toggle `.dark` class for compatibility.
 */
const STORAGE_KEY = "darkModePreference";
const THEMES = ["light", "dark", "auto"];
const DEFAULT_THEME = "auto";

function safeJsonParse(value) {
    try {
        return JSON.parse(value);
    } catch {
        return null;
    }
}

function getSystemTheme() {
    if (!window.matchMedia) return "light";
    return window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
}

function getStoredTheme() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return DEFAULT_THEME;
        const parsed = safeJsonParse(raw);
        const theme = parsed?.theme;
        return THEMES.includes(theme) ? theme : DEFAULT_THEME;
    } catch {
        return DEFAULT_THEME;
    }
}

function getEffectiveTheme(theme) {
    const t = theme ?? getStoredTheme();
    return t === "auto" ? getSystemTheme() : t;
}

function setStoredTheme(theme) {
    try {
        localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({ theme, timestamp: Date.now() }),
        );
    } catch {
        // ignore (private mode / quota / etc)
    }
}

function applyTheme(effectiveTheme, storedTheme) {
    const html = document.documentElement;
    const stored = storedTheme ?? getStoredTheme();

    html.setAttribute("data-theme", effectiveTheme);
    html.classList.toggle("dark", effectiveTheme === "dark");
    html.style.colorScheme = effectiveTheme;

    // Sync UI toggles (can be multiple buttons across layout)
    document.querySelectorAll("[data-theme-toggle]").forEach((btn) => {
        btn.setAttribute(
            "aria-label",
            stored === "auto"
                ? "Tema: System"
                : stored === "dark"
                  ? "Tema: Dark"
                  : "Tema: Light",
        );
        btn.setAttribute(
            "aria-pressed",
            effectiveTheme === "dark" ? "true" : "false",
        );
        const sun = btn.querySelector('[data-icon="sun"]');
        const moon = btn.querySelector('[data-icon="moon"]');
        if (sun) sun.classList.toggle("hidden", effectiveTheme === "dark");
        if (moon) moon.classList.toggle("hidden", effectiveTheme !== "dark");
    });

    document.querySelectorAll("[data-theme-select]").forEach((select) => {
        if (select instanceof HTMLSelectElement) {
            select.value = stored;
        }
    });
}

function toggleTheme() {
    const stored = getStoredTheme();
    const currentEffective =
        document.documentElement.getAttribute("data-theme") || "light";
    const base = stored === "auto" ? currentEffective : stored;
    const next = base === "dark" ? "light" : "dark";
    setStoredTheme(next);
    applyTheme(getEffectiveTheme(next), next);
}

function initTheme() {
    // Initial state is already set by the no-flash loader in <head>,
    // but we re-apply here to ensure UI (icons/aria) is in sync.
    const stored = getStoredTheme();
    applyTheme(getEffectiveTheme(stored), stored);

    // Bind toggle buttons
    document.querySelectorAll("[data-theme-toggle]").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.preventDefault();
            toggleTheme();
        });
    });

    document.querySelectorAll("[data-theme-select]").forEach((select) => {
        if (!(select instanceof HTMLSelectElement)) return;
        select.addEventListener("change", () => {
            const theme = select.value;
            if (!THEMES.includes(theme)) return;
            setStoredTheme(theme);
            applyTheme(getEffectiveTheme(theme), theme);
        });
    });

    // Auto mode: follow system changes
    if (window.matchMedia) {
        const mql = window.matchMedia("(prefers-color-scheme: dark)");
        const onChange = () => {
            if (getStoredTheme() === "auto")
                applyTheme(getSystemTheme(), "auto");
        };
        if (mql.addEventListener) mql.addEventListener("change", onChange);
        else if (mql.addListener) mql.addListener(onChange);
    }
}

document.addEventListener("DOMContentLoaded", initTheme);

// Optional global API (useful for Alpine/inline hooks if needed)
window.__theme = {
    get: () => getStoredTheme(),
    set: (theme) => {
        if (!THEMES.includes(theme)) return;
        setStoredTheme(theme);
        applyTheme(getEffectiveTheme(theme), theme);
    },
    toggle: toggleTheme,
};
