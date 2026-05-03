/**
 * FrierenDarkModeToggle - Elegant dark mode toggle with Frieren-inspired design
 * Features smooth animations, accessibility support, and magical aesthetic
 */
class FrierenDarkModeToggle {
    constructor(options = {}) {
        this.options = {
            container: null,
            size: "medium", // small, medium, large
            style: "default", // default, minimal, ornate
            position: "fixed", // fixed, absolute, relative
            showLabel: true,
            labelPosition: "right", // left, right, top, bottom
            enableTooltip: true,
            enableAnimation: true,
            enableSound: false,
            keyboardShortcut: "Alt+T",
            ...options,
        };

        this.isDarkMode = false;
        this.isAnimating = false;
        this.toggleElement = null;
        this.soundEnabled = false;

        // Frieren-inspired animation states
        this.animationStates = {
            sun: {
                rotation: 0,
                scale: 1,
                opacity: 1,
            },
            moon: {
                rotation: -180,
                scale: 0.8,
                opacity: 0,
            },
        };

        this.init();
    }

    /**
     * Initialize the toggle component
     */
    init() {
        if (!this.options.container) {
            throw new Error(
                "Container element is required for FrierenDarkModeToggle",
            );
        }

        this.container =
            typeof this.options.container === "string"
                ? document.querySelector(this.options.container)
                : this.options.container;

        if (!this.container) {
            throw new Error("Container element not found");
        }

        this.setupAudio();
        this.createToggle();
        this.bindEvents();
        this.syncWithDarkModeService();

        // Add to global registry
        if (!window.FrierenToggles) {
            window.FrierenToggles = [];
        }
        window.FrierenToggles.push(this);
    }

    /**
     * Setup audio for magical sound effects
     */
    setupAudio() {
        if (!this.options.enableSound) return;

        try {
            // Create audio context for magical sounds
            this.audioContext = new (
                window.AudioContext || window.webkitAudioContext
            )();
            this.soundEnabled = true;
        } catch (error) {
            console.warn("Web Audio API not supported, sound effects disabled");
            this.soundEnabled = false;
        }
    }

    /**
     * Create the toggle element with Frieren-inspired design
     */
    createToggle() {
        const toggleHtml = this.getToggleHTML();
        this.container.innerHTML = toggleHtml;

        this.toggleElement = this.container.querySelector(".frieren-toggle");
        this.sunIcon = this.container.querySelector(".toggle-sun");
        this.moonIcon = this.container.querySelector(".toggle-moon");
        this.toggleTrack = this.container.querySelector(".toggle-track");
        this.toggleThumb = this.container.querySelector(".toggle-thumb");

        this.applyStyling();
        this.updateVisualState();
    }

    /**
     * Get toggle HTML structure
     */
    getToggleHTML() {
        const sizeClass = `toggle-${this.options.size}`;
        const styleClass = `toggle-${this.options.style}`;
        const positionClass = `toggle-${this.options.position}`;
        const labelClass = this.options.showLabel
            ? `label-${this.options.labelPosition}`
            : "no-label";

        return `
            <div class="frieren-toggle ${sizeClass} ${styleClass} ${positionClass} ${labelClass}" 
                 role="switch" 
                 aria-checked="false" 
                 aria-label="Toggle dark mode"
                 tabindex="0"
                 title="${this.options.enableTooltip ? "Toggle dark mode (Alt+T)" : ""}">
                
                ${
                    this.options.showLabel &&
                    this.options.labelPosition === "left"
                        ? '<span class="toggle-label">Dark Mode</span>'
                        : ""
                }
                
                <div class="toggle-container">
                    <div class="toggle-track">
                        <div class="toggle-sky">
                            <div class="toggle-stars"></div>
                            <div class="toggle-clouds"></div>
                        </div>
                        <div class="toggle-thumb">
                            <div class="toggle-icon">
                                <div class="toggle-sun">
                                    <div class="sun-rays">
                                        <div class="ray ray-1"></div>
                                        <div class="ray ray-2"></div>
                                        <div class="ray ray-3"></div>
                                        <div class="ray ray-4"></div>
                                        <div class="ray ray-5"></div>
                                        <div class="ray ray-6"></div>
                                        <div class="ray ray-7"></div>
                                        <div class="ray ray-8"></div>
                                    </div>
                                    <div class="sun-core"></div>
                                </div>
                                <div class="toggle-moon">
                                    <div class="moon-phases">
                                        <div class="phase phase-1"></div>
                                        <div class="phase phase-2"></div>
                                        <div class="phase phase-3"></div>
                                    </div>
                                    <div class="moon-surface">
                                        <div class="crater crater-1"></div>
                                        <div class="crater crater-2"></div>
                                        <div class="crater crater-3"></div>
                                        <div class="crater crater-4"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${
                        this.options.enableAnimation
                            ? `
                        <div class="toggle-particles">
                            <div class="particle particle-1"></div>
                            <div class="particle particle-2"></div>
                            <div class="particle particle-3"></div>
                            <div class="particle particle-4"></div>
                            <div class="particle particle-5"></div>
                            <div class="particle particle-6"></div>
                        </div>
                    `
                            : ""
                    }
                </div>
                
                ${
                    this.options.showLabel &&
                    this.options.labelPosition === "right"
                        ? '<span class="toggle-label">Dark Mode</span>'
                        : ""
                }
                
                ${
                    this.options.showLabel &&
                    (this.options.labelPosition === "top" ||
                        this.options.labelPosition === "bottom")
                        ? `<span class="toggle-label ${this.options.labelPosition}">Dark Mode</span>`
                        : ""
                }
            </div>
        `;
    }

    /**
     * Apply styling based on options
     */
    applyStyling() {
        const styles = this.getToggleStyles();
        const styleElement = document.createElement("style");
        styleElement.textContent = styles;
        styleElement.id = "frieren-toggle-styles";

        // Remove existing styles if any
        const existingStyles = document.getElementById("frieren-toggle-styles");
        if (existingStyles) {
            existingStyles.remove();
        }

        document.head.appendChild(styleElement);
    }

    /**
     * Get CSS styles for the toggle
     */
    getToggleStyles() {
        return `
            /* Frieren Dark Mode Toggle Styles */
            .frieren-toggle {
                display: inline-flex;
                align-items: center;
                gap: 12px;
                cursor: pointer;
                user-select: none;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-family: inherit;
                -webkit-tap-highlight-color: transparent;
            }
            
            .frieren-toggle:focus {
                outline: 2px solid var(--focus-ring, #3b82f6);
                outline-offset: 2px;
                border-radius: 8px;
            }
            
            .frieren-toggle:hover {
                transform: translateY(-1px);
            }
            
            .frieren-toggle:active {
                transform: translateY(0);
            }
            
            /* Positioning */
            .frieren-toggle.toggle-fixed {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
            }
            
            .frieren-toggle.toggle-absolute {
                position: absolute;
                top: 20px;
                right: 20px;
                z-index: 1000;
            }
            
            .frieren-toggle.toggle-relative {
                position: relative;
            }
            
            /* Label positioning */
            .frieren-toggle.label-top {
                flex-direction: column;
                gap: 8px;
            }
            
            .frieren-toggle.label-bottom {
                flex-direction: column-reverse;
                gap: 8px;
            }
            
            .frieren-toggle.label-left {
                flex-direction: row-reverse;
            }
            
            /* Label styling */
            .toggle-label {
                font-size: 14px;
                font-weight: 500;
                color: var(--text-secondary, #64748b);
                transition: color 0.3s ease;
                user-select: none;
            }
            
            .frieren-toggle:hover .toggle-label {
                color: var(--text-primary, #374151);
            }
            
            /* Container */
            .toggle-container {
                position: relative;
                display: inline-block;
            }
            
            /* Track */
            .toggle-track {
                position: relative;
                border-radius: 34px;
                background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                overflow: hidden;
                box-shadow: 
                    inset 0 2px 4px rgba(0, 0, 0, 0.1),
                    0 1px 2px rgba(0, 0, 0, 0.1);
            }
            
            .frieren-toggle.dark-mode .toggle-track {
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                box-shadow: 
                    inset 0 2px 4px rgba(0, 0, 0, 0.3),
                    0 1px 2px rgba(0, 0, 0, 0.2);
            }
            
            /* Sky background */
            .toggle-sky {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                border-radius: inherit;
                overflow: hidden;
                opacity: 1;
                transition: opacity 0.6s ease;
            }
            
            .frieren-toggle.dark-mode .toggle-sky {
                opacity: 0;
            }
            
            /* Stars */
            .toggle-stars {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-image: 
                    radial-gradient(2px 2px at 20px 30px, #fff, transparent),
                    radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.8), transparent),
                    radial-gradient(1px 1px at 90px 40px, #fff, transparent),
                    radial-gradient(1px 1px at 130px 80px, rgba(255,255,255,0.6), transparent),
                    radial-gradient(2px 2px at 160px 30px, #ddd, transparent);
                background-repeat: repeat;
                background-size: 200px 100px;
                animation: sparkle 3s linear infinite;
                opacity: 0;
                transition: opacity 0.6s ease;
            }
            
            .frieren-toggle.dark-mode .toggle-stars {
                opacity: 1;
            }
            
            /* Clouds */
            .toggle-clouds {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: 
                    radial-gradient(ellipse at top, rgba(255,255,255,0.3) 0%, transparent 50%),
                    radial-gradient(ellipse at bottom right, rgba(255,255,255,0.2) 0%, transparent 40%);
                animation: drift 8s ease-in-out infinite;
                opacity: 1;
                transition: opacity 0.6s ease;
            }
            
            .frieren-toggle.dark-mode .toggle-clouds {
                opacity: 0;
            }
            
            /* Thumb */
            .toggle-thumb {
                position: absolute;
                top: 3px;
                left: 3px;
                border-radius: 50%;
                background: linear-gradient(135deg, #fef3c7 0%, #fbbf24 100%);
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 
                    0 2px 8px rgba(0, 0, 0, 0.15),
                    0 1px 3px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            
            .frieren-toggle.dark-mode .toggle-thumb {
                transform: translateX(calc(100% - 6px));
                background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            }
            
            /* Icon container */
            .toggle-icon {
                position: relative;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Sun */
            .toggle-sun {
                position: absolute;
                width: 60%;
                height: 60%;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                transform: rotate(0deg) scale(1);
                opacity: 1;
            }
            
            .frieren-toggle.dark-mode .toggle-sun {
                transform: rotate(180deg) scale(0.8);
                opacity: 0;
            }
            
            .sun-core {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 40%;
                height: 40%;
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                border-radius: 50%;
                transform: translate(-50%, -50%);
                box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
            }
            
            .sun-rays {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
            }
            
            .ray {
                position: absolute;
                top: 50%;
                left: 50%;
                width: 2px;
                height: 8px;
                background: linear-gradient(to bottom, #f59e0b, transparent);
                transform-origin: bottom center;
                border-radius: 1px;
            }
            
            .ray-1 { transform: translate(-50%, -100%) rotate(0deg); }
            .ray-2 { transform: translate(-50%, -100%) rotate(45deg); }
            .ray-3 { transform: translate(-50%, -100%) rotate(90deg); }
            .ray-4 { transform: translate(-50%, -100%) rotate(135deg); }
            .ray-5 { transform: translate(-50%, -100%) rotate(180deg); }
            .ray-6 { transform: translate(-50%, -100%) rotate(225deg); }
            .ray-7 { transform: translate(-50%, -100%) rotate(270deg); }
            .ray-8 { transform: translate(-50%, -100%) rotate(315deg); }
            
            /* Moon */
            .toggle-moon {
                position: absolute;
                width: 60%;
                height: 60%;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                transform: rotate(-180deg) scale(0.8);
                opacity: 0;
            }
            
            .frieren-toggle.dark-mode .toggle-moon {
                transform: rotate(0deg) scale(1);
                opacity: 1;
            }
            
            .moon-phases {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                border-radius: 50%;
                background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
                box-shadow: 0 0 10px rgba(226, 232, 240, 0.5);
                overflow: hidden;
            }
            
            .phase {
                position: absolute;
                background: rgba(30, 41, 59, 0.3);
                border-radius: 50%;
            }
            
            .phase-1 {
                top: 20%;
                left: 10%;
                width: 60%;
                height: 60%;
            }
            
            .phase-2 {
                top: 60%;
                left: 70%;
                width: 30%;
                height: 30%;
            }
            
            .phase-3 {
                top: 10%;
                left: 60%;
                width: 40%;
                height: 40%;
            }
            
            /* Moon craters */
            .moon-surface {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
            }
            
            .crater {
                position: absolute;
                background: rgba(30, 41, 59, 0.2);
                border-radius: 50%;
            }
            
            .crater-1 { top: 30%; left: 20%; width: 8px; height: 8px; }
            .crater-2 { top: 60%; left: 50%; width: 6px; height: 6px; }
            .crater-3 { top: 20%; left: 70%; width: 4px; height: 4px; }
            .crater-4 { top: 70%; left: 30%; width: 5px; height: 5px; }
            
            /* Particles */
            .toggle-particles {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .frieren-toggle.animating .toggle-particles {
                opacity: 1;
            }
            
            .particle {
                position: absolute;
                width: 3px;
                height: 3px;
                background: var(--accent-400, #f59e0b);
                border-radius: 50%;
                opacity: 0;
            }
            
            .particle-1 { top: 20%; left: 20%; animation: sparkle 1s ease-out; }
            .particle-2 { top: 30%; left: 80%; animation: sparkle 1s ease-out 0.1s; }
            .particle-3 { top: 70%; left: 30%; animation: sparkle 1s ease-out 0.2s; }
            .particle-4 { top: 80%; left: 70%; animation: sparkle 1s ease-out 0.3s; }
            .particle-5 { top: 50%; left: 10%; animation: sparkle 1s ease-out 0.4s; }
            .particle-6 { top: 50%; left: 90%; animation: sparkle 1s ease-out 0.5s; }
            
            /* Size variants */
            .frieren-toggle.toggle-small .toggle-track {
                width: 40px;
                height: 20px;
            }
            
            .frieren-toggle.toggle-small .toggle-thumb {
                width: 14px;
                height: 14px;
            }
            
            .frieren-toggle.toggle-small .toggle-label {
                font-size: 12px;
            }
            
            .frieren-toggle.toggle-medium .toggle-track {
                width: 50px;
                height: 24px;
            }
            
            .frieren-toggle.toggle-medium .toggle-thumb {
                width: 18px;
                height: 18px;
            }
            
            .frieren-toggle.toggle-large .toggle-track {
                width: 60px;
                height: 28px;
            }
            
            .frieren-toggle.toggle-large .toggle-thumb {
                width: 22px;
                height: 22px;
            }
            
            .frieren-toggle.toggle-large .toggle-label {
                font-size: 16px;
            }
            
            /* Style variants */
            .frieren-toggle.toggle-minimal {
                background: none;
                border: none;
                box-shadow: none;
            }
            
            .frieren-toggle.toggle-minimal .toggle-track {
                box-shadow: none;
                border: 2px solid var(--border-primary, #e5e7eb);
            }
            
            .frieren-toggle.toggle-minimal.dark-mode .toggle-track {
                border-color: var(--border-secondary, #374151);
            }
            
            .frieren-toggle.toggle-ornate .toggle-track {
                border: 1px solid var(--border-secondary, #d1d5db);
                box-shadow: 
                    inset 0 2px 4px rgba(0, 0, 0, 0.1),
                    0 2px 8px rgba(0, 0, 0, 0.15),
                    0 0 0 2px rgba(156, 122, 84, 0.1);
            }
            
            /* Animations */
            @keyframes sparkle {
                0% { opacity: 0; transform: scale(0) rotate(0deg); }
                50% { opacity: 1; transform: scale(1) rotate(180deg); }
                100% { opacity: 0; transform: scale(0) rotate(360deg); }
            }
            
            @keyframes drift {
                0%, 100% { transform: translateX(0) translateY(0); }
                25% { transform: translateX(5px) translateY(-2px); }
                50% { transform: translateX(-3px) translateY(3px); }
                75% { transform: translateX(2px) translateY(-1px); }
            }
            
            /* High contrast mode support */
            @media (prefers-contrast: high) {
                .toggle-track {
                    border: 2px solid transparent;
                }
                
                .toggle-thumb {
                    border: 2px solid currentColor;
                }
            }
            
            /* Reduced motion support */
            @media (prefers-reduced-motion: reduce) {
                .frieren-toggle,
                .toggle-track,
                .toggle-thumb,
                .toggle-sun,
                .toggle-moon,
                .toggle-stars,
                .toggle-clouds,
                .toggle-label {
                    transition: none !important;
                    animation: none !important;
                }
                
                .toggle-particles {
                    display: none !important;
                }
            }
            
            /* Focus visible */
            .frieren-toggle:focus-visible {
                outline: 2px solid var(--focus-ring, #3b82f6);
                outline-offset: 2px;
                border-radius: 8px;
            }
            
            /* Print styles */
            @media print {
                .frieren-toggle {
                    display: none !important;
                }
            }
        `;
    }

    /**
     * Bind events to the toggle
     */
    bindEvents() {
        if (!this.toggleElement) return;

        // Click event
        this.toggleElement.addEventListener("click", (e) => {
            e.preventDefault();
            this.handleToggle();
        });

        // Keyboard events
        this.toggleElement.addEventListener("keydown", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                this.handleToggle();
            }
        });

        // Keyboard shortcut
        if (this.options.keyboardShortcut) {
            document.addEventListener("keydown", (e) => {
                this.handleKeyboardShortcut(e);
            });
        }

        // Touch events for mobile
        this.toggleElement.addEventListener("touchstart", (e) => {
            e.preventDefault();
            this.toggleElement.classList.add("touch-active");
        });

        this.toggleElement.addEventListener("touchend", (e) => {
            e.preventDefault();
            this.toggleElement.classList.remove("touch-active");
            this.handleToggle();
        });

        // Dark mode service events
        if (window.DarkModeService) {
            window.DarkModeService.on("theme:changed", (data) => {
                this.syncVisualState(data.theme === "dark");
            });
        }
    }

    /**
     * Handle toggle interaction
     */
    handleToggle() {
        if (this.isAnimating) return;

        this.isAnimating = true;
        this.isDarkMode = !this.isDarkMode;

        // Add animation class
        if (this.options.enableAnimation) {
            this.toggleElement.classList.add("animating");
        }

        // Play sound effect
        if (this.options.enableSound && this.soundEnabled) {
            this.playToggleSound();
        }

        // Update DarkModeService
        if (window.DarkModeService) {
            const newTheme = this.isDarkMode ? "dark" : "light";
            window.DarkModeService.setTheme(newTheme);
        }

        // Update visual state
        this.updateVisualState();

        // Update ARIA attributes
        this.toggleElement.setAttribute(
            "aria-checked",
            this.isDarkMode.toString(),
        );

        // Emit custom event
        this.emit("toggle:changed", {
            isDarkMode: this.isDarkMode,
            theme: this.isDarkMode ? "dark" : "light",
        });

        // Remove animation class after delay
        setTimeout(() => {
            this.toggleElement.classList.remove("animating");
            this.isAnimating = false;
        }, 600);
    }

    /**
     * Update visual state of the toggle
     */
    updateVisualState() {
        if (this.isDarkMode) {
            this.toggleElement.classList.add("dark-mode");
            this.animationStates.sun.opacity = 0;
            this.animationStates.sun.rotation = 180;
            this.animationStates.sun.scale = 0.8;
            this.animationStates.moon.opacity = 1;
            this.animationStates.moon.rotation = 0;
            this.animationStates.moon.scale = 1;
        } else {
            this.toggleElement.classList.remove("dark-mode");
            this.animationStates.sun.opacity = 1;
            this.animationStates.sun.rotation = 0;
            this.animationStates.sun.scale = 1;
            this.animationStates.moon.opacity = 0;
            this.animationStates.moon.rotation = -180;
            this.animationStates.moon.scale = 0.8;
        }
    }

    /**
     * Sync visual state with external dark mode state
     */
    syncVisualState(isDarkMode) {
        if (this.isDarkMode !== isDarkMode) {
            this.isDarkMode = isDarkMode;
            this.updateVisualState();
            this.toggleElement.setAttribute(
                "aria-checked",
                this.isDarkMode.toString(),
            );
        }
    }

    /**
     * Sync with DarkModeService
     */
    syncWithDarkModeService() {
        if (window.DarkModeService) {
            const isDark = window.DarkModeService.isDark();
            this.syncVisualState(isDark);
        }
    }

    /**
     * Handle keyboard shortcut
     */
    handleKeyboardShortcut(e) {
        if (!this.options.keyboardShortcut) return;

        const keys = this.options.keyboardShortcut.toLowerCase().split("+");
        const isMatch = keys.every((key) => {
            switch (key) {
                case "alt":
                    return e.altKey;
                case "ctrl":
                    return e.ctrlKey;
                case "shift":
                    return e.shiftKey;
                case "meta":
                    return e.metaKey;
                default:
                    return e.key.toLowerCase() === key;
            }
        });

        if (isMatch) {
            e.preventDefault();
            this.handleToggle();
        }
    }

    /**
     * Play magical toggle sound
     */
    playToggleSound() {
        if (!this.audioContext || !this.soundEnabled) return;

        try {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);

            // Magical sound frequencies
            const frequencies = this.isDarkMode
                ? [523.25, 659.25, 783.99] // C5, E5, G5 (ascending magic)
                : [783.99, 659.25, 523.25]; // G5, E5, C5 (descending magic)

            oscillator.frequency.setValueAtTime(
                frequencies[0],
                this.audioContext.currentTime,
            );
            oscillator.frequency.setValueAtTime(
                frequencies[1],
                this.audioContext.currentTime + 0.1,
            );
            oscillator.frequency.setValueAtTime(
                frequencies[2],
                this.audioContext.currentTime + 0.2,
            );

            gainNode.gain.setValueAtTime(0.1, this.audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(
                0.01,
                this.audioContext.currentTime + 0.3,
            );

            oscillator.type = "sine";
            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + 0.3);
        } catch (error) {
            console.warn("Failed to play toggle sound:", error);
        }
    }

    /**
     * Set dark mode state programmatically
     */
    setDarkMode(isDarkMode) {
        if (this.isDarkMode !== isDarkMode) {
            this.handleToggle();
        }
    }

    /**
     * Get current state
     */
    getState() {
        return {
            isDarkMode: this.isDarkMode,
            isAnimating: this.isAnimating,
            options: this.options,
        };
    }

    /**
     * Update options
     */
    updateOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        this.createToggle(); // Recreate with new options
    }

    /**
     * Add event listener
     */
    on(event, callback) {
        if (!this._events) this._events = {};
        if (!this._events[event]) this._events[event] = [];
        this._events[event].push(callback);
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (!this._events || !this._events[event]) return;
        this._events[event] = this._events[event].filter(
            (cb) => cb !== callback,
        );
    }

    /**
     * Emit event
     */
    emit(event, data) {
        if (!this._events || !this._events[event]) return;
        this._events[event].forEach((callback) => {
            try {
                callback(data);
            } catch (error) {
                console.error(
                    `Error in toggle event listener for ${event}:`,
                    error,
                );
            }
        });
    }

    /**
     * Destroy the toggle
     */
    destroy() {
        // Remove from global registry
        if (window.FrierenToggles) {
            window.FrierenToggles = window.FrierenToggles.filter(
                (toggle) => toggle !== this,
            );
        }

        // Remove event listeners
        if (this.toggleElement) {
            this.toggleElement.removeEventListener("click", this.handleToggle);
            this.toggleElement.removeEventListener(
                "keydown",
                this.handleToggle,
            );
            this.toggleElement.removeEventListener(
                "touchstart",
                this.handleToggle,
            );
            this.toggleElement.removeEventListener(
                "touchend",
                this.handleToggle,
            );
        }

        // Remove from DOM
        if (this.container) {
            this.container.innerHTML = "";
        }

        // Remove styles
        const styleElement = document.getElementById("frieren-toggle-styles");
        if (styleElement) {
            styleElement.remove();
        }
    }
}

// Global initialization function
window.createFrierenToggle = function (options) {
    return new FrierenDarkModeToggle(options);
};

// Auto-initialize common positions
document.addEventListener("DOMContentLoaded", function () {
    // Create toggle in header if exists
    const header = document.querySelector(
        'header, .header, [data-frieren-toggle="header"]',
    );
    if (header && !header.querySelector(".frieren-toggle")) {
        const toggleContainer = document.createElement("div");
        toggleContainer.className = "frieren-toggle-container";
        header.appendChild(toggleContainer);

        createFrierenToggle({
            container: toggleContainer,
            position: "absolute",
            size: "medium",
            style: "ornate",
        });
    }

    // Create floating toggle if requested
    const floatingToggle = document.querySelector(
        '[data-frieren-toggle="floating"]',
    );
    if (floatingToggle) {
        createFrierenToggle({
            container: floatingToggle,
            position: "fixed",
            size: "large",
            style: "ornate",
        });
    }
});
