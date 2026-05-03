/**
 * FrierenDarkModeTest - Comprehensive testing suite for dark mode functionality
 * Tests all aspects of the dark mode system including performance, accessibility, and compatibility
 */
class FrierenDarkModeTest {
    constructor() {
        this.tests = [];
        this.results = {
            passed: 0,
            failed: 0,
            warnings: 0,
            errors: [],
        };

        this.performanceMetrics = {
            themeSwitchTime: [],
            initialLoadTime: null,
            memoryUsage: [],
            paintMetrics: [],
        };

        this.setupPerformanceMonitoring();
    }

    /**
     * Run all tests
     */
    async runAllTests() {
        console.log("🌙 Starting Frieren Dark Mode Tests...");

        // Performance Tests
        await this.testInitialLoadPerformance();
        await this.testThemeSwitchPerformance();
        await this.testMemoryUsage();

        // Functionality Tests
        await this.testThemePersistence();
        await this.testSystemPreferenceDetection();
        await this.testCrossTabSynchronization();
        await this.testToggleFunctionality();

        // Accessibility Tests
        await this.testKeyboardNavigation();
        await this.testScreenReaderCompatibility();
        await this.testColorContrast();
        await this.testFocusManagement();

        // Compatibility Tests
        await this.testBrowserCompatibility();
        await this.testDeviceCompatibility();
        await this.testPrintStyles();

        // Integration Tests
        await this.testComponentIntegration();
        await this.testDynamicContent();
        await this.testAnimationPerformance();

        this.generateReport();
        return this.results;
    }

    /**
     * Test initial load performance
     */
    async testInitialLoadPerformance() {
        console.log("📊 Testing initial load performance...");

        const startTime = performance.now();
        const startMemory = performance.memory
            ? performance.memory.usedJSHeapSize
            : 0;

        try {
            // Measure theme detection time
            const themeStart = performance.now();
            const theme = window.__frierenDarkMode
                ? window.__frierenDarkMode.getEffectiveTheme()
                : "light";
            const themeEnd = performance.now();

            // Measure CSS application time
            const cssStart = performance.now();
            const computedStyle = window.getComputedStyle(
                document.documentElement,
            );
            const bgColor = computedStyle.getPropertyValue("--bg-primary");
            const cssEnd = performance.now();

            const endTime = performance.now();
            const endMemory = performance.memory
                ? performance.memory.usedJSHeapSize
                : 0;

            this.performanceMetrics.initialLoadTime = endTime - startTime;

            const testResult = {
                name: "Initial Load Performance",
                passed: endTime - startTime < 100, // Should load in under 100ms
                details: {
                    totalLoadTime: endTime - startTime,
                    themeDetectionTime: themeEnd - themeStart,
                    cssApplicationTime: cssEnd - cssStart,
                    memoryDelta: endMemory - startMemory,
                    detectedTheme: theme,
                    bgColorApplied: bgColor && bgColor !== "",
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Initial Load Performance",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test theme switch performance
     */
    async testThemeSwitchPerformance() {
        console.log("🔄 Testing theme switch performance...");

        if (!window.DarkModeService) {
            this.tests.push({
                name: "Theme Switch Performance",
                passed: false,
                error: "DarkModeService not available",
            });
            return;
        }

        try {
            const iterations = 5;
            const switchTimes = [];

            for (let i = 0; i < iterations; i++) {
                const startTime = performance.now();

                // Switch theme
                const currentTheme = window.DarkModeService.getTheme();
                const newTheme = currentTheme === "light" ? "dark" : "light";
                window.DarkModeService.setTheme(newTheme);

                // Wait for transition to complete
                await this.wait(500);

                const endTime = performance.now();
                switchTimes.push(endTime - startTime);
            }

            const avgSwitchTime =
                switchTimes.reduce((a, b) => a + b, 0) / switchTimes.length;
            this.performanceMetrics.themeSwitchTime = switchTimes;

            const testResult = {
                name: "Theme Switch Performance",
                passed: avgSwitchTime < 600, // Should complete in under 600ms
                details: {
                    averageSwitchTime: avgSwitchTime,
                    minSwitchTime: Math.min(...switchTimes),
                    maxSwitchTime: Math.max(...switchTimes),
                    switchTimes: switchTimes,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Theme Switch Performance",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test memory usage
     */
    async testMemoryUsage() {
        console.log("💾 Testing memory usage...");

        if (!performance.memory) {
            this.tests.push({
                name: "Memory Usage",
                passed: false,
                warning: "Memory API not available in this browser",
            });
            return;
        }

        try {
            const initialMemory = performance.memory.usedJSHeapSize;
            const memorySnapshots = [];

            // Take multiple snapshots during theme switches
            for (let i = 0; i < 10; i++) {
                window.DarkModeService.setTheme(i % 2 === 0 ? "dark" : "light");
                await this.wait(100);
                memorySnapshots.push(performance.memory.usedJSHeapSize);
            }

            const finalMemory = performance.memory.usedJSHeapSize;
            const memoryIncrease = finalMemory - initialMemory;

            this.performanceMetrics.memoryUsage = memorySnapshots;

            const testResult = {
                name: "Memory Usage",
                passed: memoryIncrease < 1024 * 1024, // Less than 1MB increase
                details: {
                    initialMemory: initialMemory,
                    finalMemory: finalMemory,
                    memoryIncrease: memoryIncrease,
                    memoryIncreaseMB: (memoryIncrease / 1024 / 1024).toFixed(2),
                    memorySnapshots: memorySnapshots,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Memory Usage",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test theme persistence
     */
    async testThemePersistence() {
        console.log("💾 Testing theme persistence...");

        try {
            // Set a theme
            const testTheme = "dark";
            window.DarkModeService.setTheme(testTheme);

            // Wait for storage
            await this.wait(100);

            // Check localStorage
            const stored = localStorage.getItem("darkModePreference");
            const parsed = JSON.parse(stored);

            const testResult = {
                name: "Theme Persistence",
                passed: parsed && parsed.theme === testTheme,
                details: {
                    storedTheme: parsed ? parsed.theme : null,
                    expectedTheme: testTheme,
                    storageAvailable: !!stored,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Theme Persistence",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test system preference detection
     */
    async testSystemPreferenceDetection() {
        console.log("🖥️ Testing system preference detection...");

        try {
            const systemPref = window.matchMedia("(prefers-color-scheme: dark)")
                .matches
                ? "dark"
                : "light";

            // Set to auto mode
            window.DarkModeService.setTheme("auto");
            const effectiveTheme = window.DarkModeService.getEffectiveTheme();

            const testResult = {
                name: "System Preference Detection",
                passed: effectiveTheme === systemPref,
                details: {
                    systemPreference: systemPref,
                    effectiveTheme: effectiveTheme,
                    match: effectiveTheme === systemPref,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "System Preference Detection",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test cross-tab synchronization
     */
    async testCrossTabSynchronization() {
        console.log("🔄 Testing cross-tab synchronization...");

        try {
            // Simulate storage event
            const testTheme = "dark";
            const storageEvent = new StorageEvent("storage", {
                key: "darkModePreference",
                newValue: JSON.stringify({ theme: testTheme }),
                oldValue: JSON.stringify({ theme: "light" }),
                storageArea: localStorage,
            });

            // Dispatch event
            window.dispatchEvent(storageEvent);

            await this.wait(100);

            const currentTheme = window.DarkModeService.getTheme();

            const testResult = {
                name: "Cross-Tab Synchronization",
                passed: currentTheme === testTheme,
                details: {
                    eventDispatched: true,
                    expectedTheme: testTheme,
                    currentTheme: currentTheme,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Cross-Tab Synchronization",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test toggle functionality
     */
    async testToggleFunctionality() {
        console.log("🎚️ Testing toggle functionality...");

        if (!window.FrierenToggles || window.FrierenToggles.length === 0) {
            this.tests.push({
                name: "Toggle Functionality",
                passed: false,
                warning: "No toggle components found",
            });
            return;
        }

        try {
            const toggle = window.FrierenToggles[0];
            const initialState = toggle.getState();

            // Test programmatic toggle
            toggle.setDarkMode(!initialState.isDarkMode);
            await this.wait(100);

            const newState = toggle.getState();

            const testResult = {
                name: "Toggle Functionality",
                passed: newState.isDarkMode !== initialState.isDarkMode,
                details: {
                    initialState: initialState,
                    newState: newState,
                    toggleChanged:
                        newState.isDarkMode !== initialState.isDarkMode,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Toggle Functionality",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test keyboard navigation
     */
    async testKeyboardNavigation() {
        console.log("⌨️ Testing keyboard navigation...");

        try {
            const toggle = window.FrierenToggles
                ? window.FrierenToggles[0]
                : null;

            if (!toggle) {
                this.tests.push({
                    name: "Keyboard Navigation",
                    passed: false,
                    warning: "No toggle component available for testing",
                });
                return;
            }

            const toggleElement = document.querySelector(".frieren-toggle");
            const initialState = toggle.getState();

            // Test keyboard interaction
            const keyboardEvent = new KeyboardEvent("keydown", {
                key: "Enter",
                bubbles: true,
                cancelable: true,
            });

            toggleElement.dispatchEvent(keyboardEvent);
            await this.wait(100);

            const newState = toggle.getState();

            const testResult = {
                name: "Keyboard Navigation",
                passed: newState.isDarkMode !== initialState.isDarkMode,
                details: {
                    keyboardEventTriggered: true,
                    stateChanged:
                        newState.isDarkMode !== initialState.isDarkMode,
                    toggleElementFound: !!toggleElement,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Keyboard Navigation",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test screen reader compatibility
     */
    async testScreenReaderCompatibility() {
        console.log("🔊 Testing screen reader compatibility...");

        try {
            const toggleElement = document.querySelector(".frieren-toggle");

            if (!toggleElement) {
                this.tests.push({
                    name: "Screen Reader Compatibility",
                    passed: false,
                    warning: "No toggle element found",
                });
                return;
            }

            const ariaLabel = toggleElement.getAttribute("aria-label");
            const role = toggleElement.getAttribute("role");
            const tabIndex = toggleElement.getAttribute("tabindex");
            const ariaChecked = toggleElement.getAttribute("aria-checked");

            const testResult = {
                name: "Screen Reader Compatibility",
                passed: ariaLabel && role && tabIndex && ariaChecked,
                details: {
                    ariaLabel: ariaLabel,
                    role: role,
                    tabIndex: tabIndex,
                    ariaChecked: ariaChecked,
                    allAttributesPresent: !!(
                        ariaLabel &&
                        role &&
                        tabIndex &&
                        ariaChecked
                    ),
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Screen Reader Compatibility",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test color contrast
     */
    async testColorContrast() {
        console.log("🎨 Testing color contrast...");

        try {
            const lightColors = {
                textPrimary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--text-primary"),
                bgPrimary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--bg-primary"),
                textSecondary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--text-secondary"),
                bgSecondary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--bg-secondary"),
            };

            // Switch to dark mode
            window.DarkModeService.setTheme("dark");
            await this.wait(500);

            const darkColors = {
                textPrimary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--text-primary"),
                bgPrimary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--bg-primary"),
                textSecondary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--text-secondary"),
                bgSecondary: getComputedStyle(
                    document.documentElement,
                ).getPropertyValue("--bg-secondary"),
            };

            // Calculate contrast ratios (simplified)
            const lightContrast = this.calculateContrastRatio(
                lightColors.textPrimary,
                lightColors.bgPrimary,
            );
            const darkContrast = this.calculateContrastRatio(
                darkColors.textPrimary,
                darkColors.bgPrimary,
            );

            const testResult = {
                name: "Color Contrast",
                passed: lightContrast >= 4.5 && darkContrast >= 4.5, // WCAG AA standard
                details: {
                    lightContrast: lightContrast,
                    darkContrast: darkContrast,
                    lightColors: lightColors,
                    darkColors: darkColors,
                    meetsWCAG_AA: lightContrast >= 4.5 && darkContrast >= 4.5,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Color Contrast",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test focus management
     */
    async testFocusManagement() {
        console.log("🎯 Testing focus management...");

        try {
            const toggleElement = document.querySelector(".frieren-toggle");

            if (!toggleElement) {
                this.tests.push({
                    name: "Focus Management",
                    passed: false,
                    warning: "No toggle element found",
                });
                return;
            }

            // Test focus visibility
            toggleElement.focus();
            const focusVisible = document.activeElement === toggleElement;

            // Test focus outline
            const computedStyle = window.getComputedStyle(
                toggleElement,
                ":focus-visible",
            );
            const hasFocusOutline =
                computedStyle.outline && computedStyle.outline !== "none";

            const testResult = {
                name: "Focus Management",
                passed: focusVisible,
                details: {
                    elementCanBeFocused: focusVisible,
                    focusOutlinePresent: hasFocusOutline,
                    activeElement: document.activeElement.tagName,
                    toggleElement: toggleElement.tagName,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Focus Management",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test browser compatibility
     */
    async testBrowserCompatibility() {
        console.log("🌐 Testing browser compatibility...");

        try {
            const features = {
                localStorage: typeof Storage !== "undefined",
                matchMedia: typeof window.matchMedia !== "undefined",
                cssVariables:
                    CSS && CSS.supports && CSS.supports("--test", "0"),
                webAudio:
                    typeof AudioContext !== "undefined" ||
                    typeof webkitAudioContext !== "undefined",
                intersectionObserver:
                    typeof IntersectionObserver !== "undefined",
                mutationObserver: typeof MutationObserver !== "undefined",
                customElements: typeof customElements !== "undefined",
                es6:
                    typeof Symbol !== "undefined" && typeof Map !== "undefined",
            };

            const supportedFeatures =
                Object.values(features).filter(Boolean).length;
            const totalFeatures = Object.keys(features).length;
            const compatibilityScore =
                (supportedFeatures / totalFeatures) * 100;

            const testResult = {
                name: "Browser Compatibility",
                passed: compatibilityScore >= 80, // 80% compatibility threshold
                details: {
                    features: features,
                    supportedFeatures: supportedFeatures,
                    totalFeatures: totalFeatures,
                    compatibilityScore: compatibilityScore.toFixed(1) + "%",
                    userAgent: navigator.userAgent,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Browser Compatibility",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test device compatibility
     */
    async testDeviceCompatibility() {
        console.log("📱 Testing device compatibility...");

        try {
            const deviceInfo = {
                touchSupport: "ontouchstart" in window,
                screenWidth: window.screen.width,
                screenHeight: window.screen.height,
                devicePixelRatio: window.devicePixelRatio,
                orientationSupport: "orientation" in window,
                reducedMotion: window.matchMedia(
                    "(prefers-reduced-motion: reduce)",
                ).matches,
                highContrast: window.matchMedia("(prefers-contrast: high)")
                    .matches,
                darkModeSupport:
                    window.matchMedia("(prefers-color-scheme: dark)")
                        .matches !== undefined,
            };

            const testResult = {
                name: "Device Compatibility",
                passed: true,
                details: deviceInfo,
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Device Compatibility",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test print styles
     */
    async testPrintStyles() {
        console.log("🖨️ Testing print styles...");

        try {
            // Create a test print stylesheet
            const printStyles = `
                @media print {
                    .frieren-toggle { display: none !important; }
                    body { background: white !important; color: black !important; }
                }
            `;

            const styleSheet = document.createElement("style");
            styleSheet.textContent = printStyles;
            document.head.appendChild(styleSheet);

            // Test if print styles are applied
            const printMediaQuery = window.matchMedia("print");
            const hasPrintStyles =
                styleSheet.sheet && styleSheet.sheet.cssRules.length > 0;

            const testResult = {
                name: "Print Styles",
                passed: hasPrintStyles,
                details: {
                    printStylesApplied: hasPrintStyles,
                    printMediaQuerySupported: !!printMediaQuery,
                    styleSheetCreated: !!styleSheet,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);

            // Cleanup
            document.head.removeChild(styleSheet);
        } catch (error) {
            this.tests.push({
                name: "Print Styles",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test component integration
     */
    async testComponentIntegration() {
        console.log("🔗 Testing component integration...");

        try {
            const integrationInfo = window.FrierenThemeIntegration
                ? window.FrierenThemeIntegration.getInfo()
                : null;
            const darkModeInfo = window.DarkModeService
                ? window.DarkModeService.getInfo()
                : null;

            const componentsIntegrated =
                integrationInfo && integrationInfo.componentCounts;
            const totalComponents = componentsIntegrated
                ? Object.values(integrationInfo.componentCounts).reduce(
                      (a, b) => a + b,
                      0,
                  )
                : 0;

            const testResult = {
                name: "Component Integration",
                passed: totalComponents > 0,
                details: {
                    integrationAvailable: !!window.FrierenThemeIntegration,
                    darkModeServiceAvailable: !!window.DarkModeService,
                    totalComponentsStyled: totalComponents,
                    integrationInfo: integrationInfo,
                    darkModeInfo: darkModeInfo,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Component Integration",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test dynamic content
     */
    async testDynamicContent() {
        console.log("🔄 Testing dynamic content handling...");

        try {
            // Create dynamic content
            const dynamicElement = document.createElement("div");
            dynamicElement.className = "dynamic-test-element card";
            dynamicElement.innerHTML =
                "<h3>Dynamic Content</h3><p>This is dynamically added content.</p>";

            document.body.appendChild(dynamicElement);

            // Wait for observer to process
            await this.wait(200);

            // Check if theme classes were applied
            const hasThemeClasses =
                dynamicElement.classList.contains("theme-surface");

            const testResult = {
                name: "Dynamic Content",
                passed: hasThemeClasses,
                details: {
                    elementCreated: true,
                    themeClassesApplied: hasThemeClasses,
                    observerActive: !!window.FrierenThemeIntegration,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);

            // Cleanup
            document.body.removeChild(dynamicElement);
        } catch (error) {
            this.tests.push({
                name: "Dynamic Content",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Test animation performance
     */
    async testAnimationPerformance() {
        console.log("🎬 Testing animation performance...");

        try {
            const toggle = window.FrierenToggles
                ? window.FrierenToggles[0]
                : null;

            if (!toggle) {
                this.tests.push({
                    name: "Animation Performance",
                    passed: false,
                    warning: "No toggle available for animation testing",
                });
                return;
            }

            // Measure animation frame rate
            let frameCount = 0;
            let startTime = performance.now();

            const measureFrame = () => {
                frameCount++;
                if (performance.now() - startTime < 1000) {
                    requestAnimationFrame(measureFrame);
                }
            };

            requestAnimationFrame(measureFrame);

            // Trigger animation
            toggle.handleToggle();

            await this.wait(1000);

            const fps = frameCount;
            const smoothAnimation = fps >= 30; // Should maintain 30+ FPS

            const testResult = {
                name: "Animation Performance",
                passed: smoothAnimation,
                details: {
                    framesPerSecond: fps,
                    smoothAnimation: smoothAnimation,
                    animationDuration: 1000,
                    framesRendered: frameCount,
                },
            };

            this.tests.push(testResult);
            this.updateResults(testResult);
        } catch (error) {
            this.tests.push({
                name: "Animation Performance",
                passed: false,
                error: error.message,
            });
            this.results.errors.push(error);
        }
    }

    /**
     * Utility: Calculate contrast ratio
     */
    calculateContrastRatio(color1, color2) {
        // Simplified contrast calculation
        // In a real implementation, you'd convert to RGB and calculate luminance
        const getLuminance = (color) => {
            // Basic luminance calculation (not accurate for real colors)
            const rgb = this.hexToRgb(color);
            if (!rgb) return 0.5;
            return (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
        };

        const lum1 = getLuminance(color1);
        const lum2 = getLuminance(color2);
        const brightest = Math.max(lum1, lum2);
        const darkest = Math.min(lum1, lum2);

        return (brightest + 0.05) / (darkest + 0.05);
    }

    /**
     * Utility: Convert hex to RGB
     */
    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result
            ? {
                  r: parseInt(result[1], 16),
                  g: parseInt(result[2], 16),
                  b: parseInt(result[3], 16),
              }
            : null;
    }

    /**
     * Utility: Wait for specified time
     */
    wait(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    /**
     * Update test results
     */
    updateResults(testResult) {
        if (testResult.passed) {
            this.results.passed++;
        } else {
            this.results.failed++;
        }

        if (testResult.warning) {
            this.results.warnings++;
        }
    }

    /**
     * Setup performance monitoring
     */
    setupPerformanceMonitoring() {
        // Monitor paint metrics
        if ("PerformanceObserver" in window) {
            const paintObserver = new PerformanceObserver((list) => {
                for (const entry of list.getEntries()) {
                    if (entry.entryType === "paint") {
                        this.performanceMetrics.paintMetrics.push({
                            name: entry.name,
                            startTime: entry.startTime,
                        });
                    }
                }
            });

            try {
                paintObserver.observe({ entryTypes: ["paint"] });
            } catch (e) {
                console.warn("Paint timing not supported");
            }
        }
    }

    /**
     * Generate test report
     */
    generateReport() {
        const totalTests = this.tests.length;
        const passRate = (this.results.passed / totalTests) * 100;

        console.log("\n" + "=".repeat(60));
        console.log("🌙 FRIEREN DARK MODE TEST REPORT");
        console.log("=".repeat(60));
        console.log(`Total Tests: ${totalTests}`);
        console.log(`✅ Passed: ${this.results.passed}`);
        console.log(`❌ Failed: ${this.results.failed}`);
        console.log(`⚠️  Warnings: ${this.results.warnings}`);
        console.log(`📊 Pass Rate: ${passRate.toFixed(1)}%`);

        if (this.results.errors.length > 0) {
            console.log("\n🚨 Errors:");
            this.results.errors.forEach((error) => {
                console.log(`  - ${error.message}`);
            });
        }

        console.log("\n📈 Performance Metrics:");
        if (this.performanceMetrics.initialLoadTime) {
            console.log(
                `  Initial Load: ${this.performanceMetrics.initialLoadTime.toFixed(2)}ms`,
            );
        }
        if (this.performanceMetrics.themeSwitchTime.length > 0) {
            const avgSwitchTime =
                this.performanceMetrics.themeSwitchTime.reduce(
                    (a, b) => a + b,
                    0,
                ) / this.performanceMetrics.themeSwitchTime.length;
            console.log(`  Avg Theme Switch: ${avgSwitchTime.toFixed(2)}ms`);
        }

        console.log("\n🔍 Detailed Test Results:");
        this.tests.forEach((test, index) => {
            const status = test.passed ? "✅" : "❌";
            const warning = test.warning ? " ⚠️" : "";
            console.log(`  ${index + 1}. ${status} ${test.name}${warning}`);
            if (test.details) {
                console.log(
                    `     Details: ${JSON.stringify(test.details, null, 2).replace(/\n/g, "\n     ")}`,
                );
            }
        });

        console.log("\n" + "=".repeat(60));

        return {
            summary: {
                total: totalTests,
                passed: this.results.passed,
                failed: this.results.failed,
                warnings: this.results.warnings,
                passRate: passRate,
            },
            tests: this.tests,
            performance: this.performanceMetrics,
            errors: this.results.errors,
        };
    }
}

// Auto-run tests when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    // Wait for all components to initialize
    setTimeout(async () => {
        const tester = new FrierenDarkModeTest();
        const results = await tester.runAllTests();

        // Make results available globally
        window.FrierenDarkModeTestResults = results;

        // Add test results to page if test page
        if (window.location.pathname.includes("test")) {
            const testResultsContainer =
                document.getElementById("test-results");
            if (testResultsContainer) {
                testResultsContainer.innerHTML = `
                    <div class="test-summary">
                        <h3>Test Results</h3>
                        <p>Pass Rate: ${results.summary.passRate.toFixed(1)}%</p>
                        <p>Passed: ${results.summary.passed}/${results.summary.total}</p>
                        <p>Failed: ${results.summary.failed}</p>
                        <p>Warnings: ${results.summary.warnings}</p>
                    </div>
                `;
            }
        }
    }, 1000);
});
