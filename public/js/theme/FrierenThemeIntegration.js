/**
 * FrierenThemeIntegration - Integration layer for existing components
 * Automatically applies Frieren dark mode theme to existing components
 */
class FrierenThemeIntegration {
    constructor(options = {}) {
        this.options = {
            autoInitialize: true,
            enableComponentUpdates: true,
            enableFormStyling: true,
            enableNavigationStyling: true,
            enableCardStyling: true,
            enableButtonStyling: true,
            enableAlertStyling: true,
            enableModalStyling: true,
            enableTableStyling: true,
            enableCodeStyling: true,
            customSelectors: {},
            ...options,
        };

        this.componentSelectors = {
            // Navigation components
            navbar: ".navbar, .nav, .navigation, header, .header",
            navLinks: ".nav-link, .nav-item, .navigation-link",
            navBrand: ".navbar-brand, .brand, .logo",

            // Card components
            card: ".card, .panel, .box, .tile",
            cardHeader: ".card-header, .panel-header, .box-header",
            cardBody: ".card-body, .panel-body, .box-body",
            cardFooter: ".card-footer, .panel-footer, .box-footer",

            // Button components
            button: '.btn, .button, button, [role="button"]',
            buttonPrimary:
                ".btn-primary, .button-primary, .btn-primary:not(.btn-outline)",
            buttonSecondary:
                ".btn-secondary, .button-secondary, .btn-secondary:not(.btn-outline)",
            buttonSuccess:
                ".btn-success, .button-success, .btn-success:not(.btn-outline)",
            buttonWarning:
                ".btn-warning, .button-warning, .btn-warning:not(.btn-outline)",
            buttonError:
                ".btn-error, .btn-danger, .button-error, .btn-danger:not(.btn-outline)",

            // Form components
            form: "form, .form, .form-container",
            input: "input, .input, .form-control, .form-input",
            textarea: "textarea, .textarea",
            select: "select, .select, .form-select",
            checkbox: 'input[type="checkbox"], .checkbox, .form-check',
            radio: 'input[type="radio"], .radio, .form-radio',
            label: "label, .label, .form-label",

            // Alert components
            alert: ".alert, .notification, .message, .notice",
            alertSuccess:
                ".alert-success, .notification-success, .message-success",
            alertWarning:
                ".alert-warning, .notification-warning, .message-warning",
            alertError:
                ".alert-error, .alert-danger, .notification-error, .message-error",
            alertInfo: ".alert-info, .notification-info, .message-info",

            // Modal components
            modal: ".modal, .modal-dialog, .popup, .dialog",
            modalHeader: ".modal-header, .popup-header, .dialog-header",
            modalBody: ".modal-body, .popup-body, .dialog-body",
            modalFooter: ".modal-footer, .popup-footer, .dialog-footer",
            modalBackdrop: ".modal-backdrop, .modal-overlay, .popup-overlay",

            // Table components
            table: "table, .table, .data-table",
            tableHeader: "thead, .table-header, .table-head",
            tableBody: "tbody, .table-body",
            tableRow: "tr, .table-row",
            tableCell: "td, th, .table-cell",

            // Code components
            code: "code, .code, .code-block",
            pre: "pre, .pre, .preformatted",

            // Layout components
            container: ".container, .wrapper, .main-container",
            sidebar: ".sidebar, .side-nav, .navigation-drawer",
            content: ".content, .main-content, .page-content",
            footer: "footer, .footer, .page-footer",
        };

        // Merge with custom selectors
        this.componentSelectors = {
            ...this.componentSelectors,
            ...this.options.customSelectors,
        };

        if (this.options.autoInitialize) {
            this.init();
        }
    }

    /**
     * Initialize the integration
     */
    init() {
        this.setupThemeObserver();
        this.applyInitialStyling();
        this.bindEvents();

        // Listen for theme changes
        if (window.DarkModeService) {
            window.DarkModeService.on("theme:changed", (data) => {
                this.handleThemeChange(data);
            });
        }

        console.log("FrierenThemeIntegration initialized");
    }

    /**
     * Setup observer for dynamically added components
     */
    setupThemeObserver() {
        if (!this.options.enableComponentUpdates) return;

        this.observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        this.applyStylingToElement(node);
                        this.applyStylingToChildren(node);
                    }
                });
            });
        });

        this.observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    /**
     * Apply styling to element and its children
     */
    applyStylingToChildren(element) {
        const allElements = element.querySelectorAll("*");
        allElements.forEach((child) => {
            this.applyStylingToElement(child);
        });
    }

    /**
     * Apply initial styling to existing components
     */
    applyInitialStyling() {
        // Apply navigation styling
        if (this.options.enableNavigationStyling) {
            this.styleNavigation();
        }

        // Apply card styling
        if (this.options.enableCardStyling) {
            this.styleCards();
        }

        // Apply button styling
        if (this.options.enableButtonStyling) {
            this.styleButtons();
        }

        // Apply form styling
        if (this.options.enableFormStyling) {
            this.styleForms();
        }

        // Apply alert styling
        if (this.options.enableAlertStyling) {
            this.styleAlerts();
        }

        // Apply modal styling
        if (this.options.enableModalStyling) {
            this.styleModals();
        }

        // Apply table styling
        if (this.options.enableTableStyling) {
            this.styleTables();
        }

        // Apply code styling
        if (this.options.enableCodeStyling) {
            this.styleCode();
        }

        // Apply general styling
        this.styleGeneral();
    }

    /**
     * Style navigation components
     */
    styleNavigation() {
        const navbars = document.querySelectorAll(
            this.componentSelectors.navbar,
        );
        navbars.forEach((navbar) => {
            this.addThemeClasses(navbar, ["navbar", "theme-surface"]);

            // Style nav links
            const navLinks = navbar.querySelectorAll(
                this.componentSelectors.navLinks,
            );
            navLinks.forEach((link) => {
                this.addThemeClasses(link, [
                    "nav-link",
                    "theme-text-secondary",
                ]);
                link.addEventListener("mouseenter", () => {
                    link.classList.add("theme-hover-bg");
                });
                link.addEventListener("mouseleave", () => {
                    link.classList.remove("theme-hover-bg");
                });
            });

            // Style nav brand
            const navBrands = navbar.querySelectorAll(
                this.componentSelectors.navBrand,
            );
            navBrands.forEach((brand) => {
                this.addThemeClasses(brand, [
                    "nav-brand",
                    "theme-text-primary",
                ]);
            });
        });
    }

    /**
     * Style card components
     */
    styleCards() {
        const cards = document.querySelectorAll(this.componentSelectors.card);
        cards.forEach((card) => {
            this.addThemeClasses(card, [
                "card",
                "theme-surface",
                "theme-border-primary",
            ]);

            // Style card header
            const headers = card.querySelectorAll(
                this.componentSelectors.cardHeader,
            );
            headers.forEach((header) => {
                this.addThemeClasses(header, [
                    "card-header",
                    "theme-border-bottom",
                ]);
            });

            // Style card body
            const bodies = card.querySelectorAll(
                this.componentSelectors.cardBody,
            );
            bodies.forEach((body) => {
                this.addThemeClasses(body, [
                    "card-body",
                    "theme-text-secondary",
                ]);
            });

            // Style card footer
            const footers = card.querySelectorAll(
                this.componentSelectors.cardFooter,
            );
            footers.forEach((footer) => {
                this.addThemeClasses(footer, [
                    "card-footer",
                    "theme-border-top",
                ]);
            });
        });
    }

    /**
     * Style button components
     */
    styleButtons() {
        const buttons = document.querySelectorAll(
            this.componentSelectors.button,
        );
        buttons.forEach((button) => {
            this.addThemeClasses(button, ["btn", "theme-transition"]);

            // Determine button type and apply appropriate styling
            if (button.matches(this.componentSelectors.buttonPrimary)) {
                this.addThemeClasses(button, [
                    "btn-primary",
                    "theme-gradient-primary",
                    "theme-text-inverse",
                ]);
            } else if (
                button.matches(this.componentSelectors.buttonSecondary)
            ) {
                this.addThemeClasses(button, [
                    "btn-secondary",
                    "theme-surface",
                    "theme-text-primary",
                    "theme-border-primary",
                ]);
            } else if (button.matches(this.componentSelectors.buttonSuccess)) {
                this.addThemeClasses(button, [
                    "btn-success",
                    "theme-success-bg",
                    "theme-success-text",
                ]);
            } else if (button.matches(this.componentSelectors.buttonWarning)) {
                this.addThemeClasses(button, [
                    "btn-warning",
                    "theme-warning-bg",
                    "theme-warning-text",
                ]);
            } else if (button.matches(this.componentSelectors.buttonError)) {
                this.addThemeClasses(button, [
                    "btn-error",
                    "theme-error-bg",
                    "theme-error-text",
                ]);
            } else {
                // Default button styling
                this.addThemeClasses(button, [
                    "btn-default",
                    "theme-surface",
                    "theme-text-primary",
                    "theme-border-primary",
                ]);
            }
        });
    }

    /**
     * Style form components
     */
    styleForms() {
        const forms = document.querySelectorAll(this.componentSelectors.form);
        forms.forEach((form) => {
            this.addThemeClasses(form, ["form", "theme-text-primary"]);
        });

        // Style inputs
        const inputs = document.querySelectorAll(this.componentSelectors.input);
        inputs.forEach((input) => {
            this.addThemeClasses(input, [
                "input",
                "theme-surface",
                "theme-border-primary",
                "theme-text-primary",
            ]);
            input.addEventListener("focus", () => {
                input.classList.add("theme-focus-ring");
            });
            input.addEventListener("blur", () => {
                input.classList.remove("theme-focus-ring");
            });
        });

        // Style textareas
        const textareas = document.querySelectorAll(
            this.componentSelectors.textarea,
        );
        textareas.forEach((textarea) => {
            this.addThemeClasses(textarea, [
                "textarea",
                "theme-surface",
                "theme-border-primary",
                "theme-text-primary",
            ]);
            textarea.addEventListener("focus", () => {
                textarea.classList.add("theme-focus-ring");
            });
            textarea.addEventListener("blur", () => {
                textarea.classList.remove("theme-focus-ring");
            });
        });

        // Style selects
        const selects = document.querySelectorAll(
            this.componentSelectors.select,
        );
        selects.forEach((select) => {
            this.addThemeClasses(select, [
                "select",
                "theme-surface",
                "theme-border-primary",
                "theme-text-primary",
            ]);
        });

        // Style labels
        const labels = document.querySelectorAll(this.componentSelectors.label);
        labels.forEach((label) => {
            this.addThemeClasses(label, ["label", "theme-text-secondary"]);
        });
    }

    /**
     * Style alert components
     */
    styleAlerts() {
        const alerts = document.querySelectorAll(this.componentSelectors.alert);
        alerts.forEach((alert) => {
            this.addThemeClasses(alert, ["alert", "theme-border"]);

            // Determine alert type and apply appropriate styling
            if (alert.matches(this.componentSelectors.alertSuccess)) {
                this.addThemeClasses(alert, [
                    "alert-success",
                    "theme-success-bg",
                    "theme-success-text",
                    "theme-success-border",
                ]);
            } else if (alert.matches(this.componentSelectors.alertWarning)) {
                this.addThemeClasses(alert, [
                    "alert-warning",
                    "theme-warning-bg",
                    "theme-warning-text",
                    "theme-warning-border",
                ]);
            } else if (alert.matches(this.componentSelectors.alertError)) {
                this.addThemeClasses(alert, [
                    "alert-error",
                    "theme-error-bg",
                    "theme-error-text",
                    "theme-error-border",
                ]);
            } else if (alert.matches(this.componentSelectors.alertInfo)) {
                this.addThemeClasses(alert, [
                    "alert-info",
                    "theme-info-bg",
                    "theme-info-text",
                    "theme-info-border",
                ]);
            } else {
                // Default alert styling
                this.addThemeClasses(alert, [
                    "alert-default",
                    "theme-bg-secondary",
                    "theme-text-primary",
                    "theme-border-primary",
                ]);
            }
        });
    }

    /**
     * Style modal components
     */
    styleModals() {
        const modals = document.querySelectorAll(this.componentSelectors.modal);
        modals.forEach((modal) => {
            this.addThemeClasses(modal, [
                "modal",
                "theme-surface",
                "theme-border-primary",
                "theme-shadow-lg",
            ]);

            // Style modal header
            const headers = modal.querySelectorAll(
                this.componentSelectors.modalHeader,
            );
            headers.forEach((header) => {
                this.addThemeClasses(header, [
                    "modal-header",
                    "theme-border-bottom",
                ]);
            });

            // Style modal body
            const bodies = modal.querySelectorAll(
                this.componentSelectors.modalBody,
            );
            bodies.forEach((body) => {
                this.addThemeClasses(body, [
                    "modal-body",
                    "theme-text-secondary",
                ]);
            });

            // Style modal footer
            const footers = modal.querySelectorAll(
                this.componentSelectors.modalFooter,
            );
            footers.forEach((footer) => {
                this.addThemeClasses(footer, [
                    "modal-footer",
                    "theme-border-top",
                ]);
            });
        });

        // Style modal backdrops
        const backdrops = document.querySelectorAll(
            this.componentSelectors.modalBackdrop,
        );
        backdrops.forEach((backdrop) => {
            this.addThemeClasses(backdrop, [
                "modal-backdrop",
                "theme-backdrop",
            ]);
        });
    }

    /**
     * Style table components
     */
    styleTables() {
        const tables = document.querySelectorAll(this.componentSelectors.table);
        tables.forEach((table) => {
            this.addThemeClasses(table, [
                "table",
                "theme-surface",
                "theme-border-primary",
            ]);

            // Style table headers
            const headers = table.querySelectorAll(
                this.componentSelectors.tableHeader,
            );
            headers.forEach((header) => {
                this.addThemeClasses(header, [
                    "table-header",
                    "theme-bg-secondary",
                    "theme-text-primary",
                ]);
            });

            // Style table rows
            const rows = table.querySelectorAll(
                this.componentSelectors.tableRow,
            );
            rows.forEach((row) => {
                row.addEventListener("mouseenter", () => {
                    row.classList.add("theme-hover-bg");
                });
                row.addEventListener("mouseleave", () => {
                    row.classList.remove("theme-hover-bg");
                });
            });

            // Style table cells
            const cells = table.querySelectorAll(
                this.componentSelectors.tableCell,
            );
            cells.forEach((cell) => {
                this.addThemeClasses(cell, [
                    "table-cell",
                    "theme-text-secondary",
                    "theme-border-bottom",
                ]);
            });
        });
    }

    /**
     * Style code components
     */
    styleCode() {
        const codeElements = document.querySelectorAll(
            this.componentSelectors.code,
        );
        codeElements.forEach((code) => {
            this.addThemeClasses(code, [
                "code",
                "theme-bg-tertiary",
                "theme-text-primary",
                "theme-border-primary",
            ]);
        });

        const preElements = document.querySelectorAll(
            this.componentSelectors.pre,
        );
        preElements.forEach((pre) => {
            this.addThemeClasses(pre, [
                "pre",
                "theme-bg-tertiary",
                "theme-border-primary",
            ]);
        });
    }

    /**
     * Apply general styling
     */
    styleGeneral() {
        // Style containers
        const containers = document.querySelectorAll(
            this.componentSelectors.container,
        );
        containers.forEach((container) => {
            this.addThemeClasses(container, ["container", "theme-bg-primary"]);
        });

        // Style sidebars
        const sidebars = document.querySelectorAll(
            this.componentSelectors.sidebar,
        );
        sidebars.forEach((sidebar) => {
            this.addThemeClasses(sidebar, [
                "sidebar",
                "theme-bg-secondary",
                "theme-border-right",
            ]);
        });

        // Style content areas
        const contents = document.querySelectorAll(
            this.componentSelectors.content,
        );
        contents.forEach((content) => {
            this.addThemeClasses(content, ["content", "theme-bg-primary"]);
        });

        // Style footers
        const footers = document.querySelectorAll(
            this.componentSelectors.footer,
        );
        footers.forEach((footer) => {
            this.addThemeClasses(footer, [
                "footer",
                "theme-bg-secondary",
                "theme-border-top",
            ]);
        });
    }

    /**
     * Apply styling to a specific element
     */
    applyStylingToElement(element) {
        // Check if element matches any component selectors
        Object.entries(this.componentSelectors).forEach(
            ([componentType, selector]) => {
                if (element.matches(selector)) {
                    this.applyComponentStyling(element, componentType);
                }
            },
        );
    }

    /**
     * Apply component-specific styling
     */
    applyComponentStyling(element, componentType) {
        switch (componentType) {
            case "navbar":
                this.styleNavigationElement(element);
                break;
            case "card":
                this.styleCardElement(element);
                break;
            case "button":
                this.styleButtonElement(element);
                break;
            case "input":
                this.styleInputElement(element);
                break;
            case "alert":
                this.styleAlertElement(element);
                break;
            case "modal":
                this.styleModalElement(element);
                break;
            case "table":
                this.styleTableElement(element);
                break;
            case "code":
                this.styleCodeElement(element);
                break;
            default:
                this.addThemeClasses(element, [
                    componentType,
                    "theme-surface",
                    "theme-text-primary",
                ]);
        }
    }

    /**
     * Add theme classes to element
     */
    addThemeClasses(element, classes) {
        if (!element || !classes) return;

        classes.forEach((className) => {
            if (className && typeof className === "string") {
                element.classList.add(className);
            }
        });
    }

    /**
     * Handle theme change
     */
    handleThemeChange(data) {
        console.log("Theme changed, updating components:", data);

        // Re-apply styling to all components
        this.applyInitialStyling();

        // Emit custom event for custom integrations
        this.emit("theme:componentsUpdated", data);
    }

    /**
     * Bind events
     */
    bindEvents() {
        // Handle dynamic component updates
        if (this.options.enableComponentUpdates) {
            document.addEventListener("DOMContentLoaded", () => {
                this.applyInitialStyling();
            });
        }
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
                    `Error in integration event listener for ${event}:`,
                    error,
                );
            }
        });
    }

    /**
     * Destroy the integration
     */
    destroy() {
        // Disconnect observer
        if (this.observer) {
            this.observer.disconnect();
        }

        // Remove event listeners
        if (window.DarkModeService) {
            window.DarkModeService.off("theme:changed", this.handleThemeChange);
        }

        // Remove all theme classes
        const allElements = document.querySelectorAll("*");
        allElements.forEach((element) => {
            const classes = Array.from(element.classList);
            classes.forEach((className) => {
                if (className.startsWith("theme-")) {
                    element.classList.remove(className);
                }
            });
        });
    }

    /**
     * Get component information
     */
    getInfo() {
        const componentCounts = {};
        Object.entries(this.componentSelectors).forEach(([type, selector]) => {
            componentCounts[type] = document.querySelectorAll(selector).length;
        });

        return {
            options: this.options,
            componentCounts: componentCounts,
            isObserving: !!this.observer,
            theme: window.DarkModeService
                ? window.DarkModeService.getTheme()
                : "unknown",
        };
    }
}

// Create global instance
window.FrierenThemeIntegration = new FrierenThemeIntegration({
    autoInitialize: true,
    enableComponentUpdates: true,
    enableFormStyling: true,
    enableNavigationStyling: true,
    enableCardStyling: true,
    enableButtonStyling: true,
    enableAlertStyling: true,
    enableModalStyling: true,
    enableTableStyling: true,
    enableCodeStyling: true,
});

// Auto-initialize on DOM ready
document.addEventListener("DOMContentLoaded", function () {
    console.log(
        "FrierenThemeIntegration ready:",
        window.FrierenThemeIntegration.getInfo(),
    );
});
