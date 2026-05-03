/**
 * TagManager - Advanced tag management system
 * Supports autocomplete, create-on-enter, bulk operations, and real-time suggestions
 */
class TagManager {
    constructor(options = {}) {
        this.options = {
            container: null,
            inputSelector: ".tag-input",
            tagsContainer: ".tags-container",
            suggestionsContainer: ".tag-suggestions",
            maxTags: 10,
            allowCreate: true,
            allowAutoGenerate: true,
            minChars: 1,
            debounceTime: 300,
            ...options,
        };

        this.tags = new Set();
        this.suggestions = [];
        this.debounceTimer = null;
        this.isLoading = false;

        this.init();
    }

    init() {
        if (!this.options.container) {
            throw new Error("Container element is required");
        }

        this.container =
            typeof this.options.container === "string"
                ? document.querySelector(this.options.container)
                : this.options.container;

        if (!this.container) {
            throw new Error("Container element not found");
        }

        this.setupElements();
        this.bindEvents();
        this.loadExistingTags();
    }

    setupElements() {
        // Create main structure if not exists
        if (!this.container.querySelector(this.options.inputSelector)) {
            this.container.innerHTML = this.getDefaultTemplate();
        }

        this.input = this.container.querySelector(this.options.inputSelector);
        this.tagsContainer = this.container.querySelector(
            this.options.tagsContainer,
        );
        this.suggestionsContainer = this.container.querySelector(
            this.options.suggestionsContainer,
        );

        if (!this.input || !this.tagsContainer || !this.suggestionsContainer) {
            throw new Error("Required elements not found in container");
        }
    }

    getDefaultTemplate() {
        return `
            <div class="tag-manager">
                <div class="tag-input-container">
                    <div class="${this.options.tagsContainer.replace(".", "")}"></div>
                    <input type="text" 
                           class="${this.options.inputSelector.replace(".", "")}" 
                           placeholder="Add tags..."
                           autocomplete="off">
                </div>
                <div class="${this.options.suggestionsContainer.replace(".", "")}"></div>
                <div class="tag-actions">
                    <button type="button" class="btn-generate-tags" title="Auto-generate tags">
                        <i class="fas fa-magic"></i> Generate
                    </button>
                    <button type="button" class="btn-manage-tags" title="Manage tags">
                        <i class="fas fa-cog"></i> Manage
                    </button>
                </div>
            </div>
        `;
    }

    bindEvents() {
        // Input events
        this.input.addEventListener("input", (e) => this.handleInput(e));
        this.input.addEventListener("keydown", (e) => this.handleKeydown(e));
        this.input.addEventListener("focus", () => this.showSuggestions());
        this.input.addEventListener("blur", () => {
            setTimeout(() => this.hideSuggestions(), 200);
        });

        // Action buttons
        const generateBtn = this.container.querySelector(".btn-generate-tags");
        const manageBtn = this.container.querySelector(".btn-manage-tags");

        if (generateBtn) {
            generateBtn.addEventListener("click", () => this.generateTags());
        }

        if (manageBtn) {
            manageBtn.addEventListener("click", () => this.openTagManager());
        }

        // Suggestion events
        this.suggestionsContainer.addEventListener("click", (e) => {
            const suggestion = e.target.closest(".tag-suggestion");
            if (suggestion) {
                this.selectSuggestion(
                    suggestion.dataset.tagId,
                    suggestion.dataset.tagName,
                );
            }
        });

        // Tag removal events
        this.tagsContainer.addEventListener("click", (e) => {
            const removeBtn = e.target.closest(".tag-remove");
            if (removeBtn) {
                this.removeTag(
                    removeBtn.dataset.tagId,
                    removeBtn.dataset.tagName,
                );
            }
        });
    }

    async loadExistingTags() {
        try {
            const response = await fetch("/tags", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.existingTags = data.tags.data || [];
            }
        } catch (error) {
            console.error("Failed to load existing tags:", error);
        }
    }

    handleInput(e) {
        const value = e.target.value.trim();

        clearTimeout(this.debounceTimer);

        if (value.length >= this.options.minChars) {
            this.debounceTimer = setTimeout(() => {
                this.fetchSuggestions(value);
            }, this.options.debounceTime);
        } else {
            this.hideSuggestions();
        }
    }

    handleKeydown(e) {
        const value = e.target.value.trim();

        switch (e.key) {
            case "Enter":
                e.preventDefault();
                if (value) {
                    this.createOrAddTag(value);
                }
                break;

            case "Backspace":
                if (!value && this.tags.size > 0) {
                    e.preventDefault();
                    this.removeLastTag();
                }
                break;

            case "ArrowDown":
                e.preventDefault();
                this.navigateSuggestions("down");
                break;

            case "ArrowUp":
                e.preventDefault();
                this.navigateSuggestions("up");
                break;

            case "Escape":
                e.preventDefault();
                this.hideSuggestions();
                break;
        }
    }

    async fetchSuggestions(query) {
        if (this.isLoading) return;

        this.isLoading = true;
        this.showLoadingState();

        try {
            const response = await fetch(
                `/tags/autocomplete?query=${encodeURIComponent(query)}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (response.ok) {
                const data = await response.json();
                this.suggestions = data.tags || [];
                this.renderSuggestions();
            }
        } catch (error) {
            console.error("Failed to fetch suggestions:", error);
            this.suggestions = [];
            this.renderSuggestions();
        } finally {
            this.isLoading = false;
        }
    }

    renderSuggestions() {
        if (this.suggestions.length === 0) {
            this.suggestionsContainer.innerHTML = `
                <div class="no-suggestions">
                    ${this.options.allowCreate ? "Press Enter to create this tag" : "No suggestions found"}
                </div>
            `;
            return;
        }

        const suggestionsHtml = this.suggestions
            .map(
                (tag, index) => `
            <div class="tag-suggestion ${index === 0 ? "selected" : ""}" 
                 data-tag-id="${tag.id}" 
                 data-tag-name="${tag.name}"
                 data-tag-slug="${tag.slug}">
                <span class="tag-color" style="background-color: ${tag.color}"></span>
                <span class="tag-name">${tag.name}</span>
                ${tag.description ? `<span class="tag-description">${tag.description}</span>` : ""}
                <span class="tag-usage">${tag.usage_count || 0}</span>
            </div>
        `,
            )
            .join("");

        this.suggestionsContainer.innerHTML = suggestionsHtml;
        this.showSuggestions();
    }

    showLoadingState() {
        this.suggestionsContainer.innerHTML =
            '<div class="loading">Loading suggestions...</div>';
        this.showSuggestions();
    }

    showSuggestions() {
        this.suggestionsContainer.style.display = "block";
    }

    hideSuggestions() {
        this.suggestionsContainer.style.display = "none";
    }

    navigateSuggestions(direction) {
        const suggestions =
            this.suggestionsContainer.querySelectorAll(".tag-suggestion");
        if (suggestions.length === 0) return;

        const current = this.suggestionsContainer.querySelector(
            ".tag-suggestion.selected",
        );
        let next;

        if (current) {
            current.classList.remove("selected");
            if (direction === "down") {
                next = current.nextElementSibling || suggestions[0];
            } else {
                next =
                    current.previousElementSibling ||
                    suggestions[suggestions.length - 1];
            }
        } else {
            next = suggestions[0];
        }

        if (next) {
            next.classList.add("selected");
            this.input.value = next.dataset.tagName;
        }
    }

    selectSuggestion(tagId, tagName) {
        this.addTag(tagId, tagName);
        this.input.value = "";
        this.hideSuggestions();
    }

    async createOrAddTag(tagName) {
        // Check if tag already exists in suggestions
        const existingSuggestion = this.suggestions.find(
            (tag) => tag.name.toLowerCase() === tagName.toLowerCase(),
        );

        if (existingSuggestion) {
            this.addTag(existingSuggestion.id, existingSuggestion.name);
            this.input.value = "";
            return;
        }

        // Check if already added
        if (this.tags.has(tagName.toLowerCase())) {
            this.input.value = "";
            return;
        }

        // Create new tag if allowed
        if (this.options.allowCreate) {
            try {
                const response = await fetch("/tags", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                    body: JSON.stringify({
                        name: tagName,
                        color: this.generateRandomColor(),
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    this.addTag(data.tag.id, data.tag.name, data.tag.color);
                    this.input.value = "";
                } else {
                    const error = await response.json();
                    this.showError(error.message || "Failed to create tag");
                }
            } catch (error) {
                console.error("Failed to create tag:", error);
                this.showError("Failed to create tag");
            }
        }
    }

    addTag(tagId, tagName, color = "#000000") {
        if (this.tags.size >= this.options.maxTags) {
            this.showError(`Maximum ${this.options.maxTags} tags allowed`);
            return;
        }

        const tagKey = tagName.toLowerCase();
        if (this.tags.has(tagKey)) {
            return;
        }

        this.tags.add(tagKey);

        const tagElement = document.createElement("div");
        tagElement.className = "tag-item";
        tagElement.innerHTML = `
            <span class="tag-color" style="background-color: ${color}"></span>
            <span class="tag-name">${tagName}</span>
            <button type="button" 
                    class="tag-remove" 
                    data-tag-id="${tagId}" 
                    data-tag-name="${tagName}"
                    title="Remove tag">
                <i class="fas fa-times"></i>
            </button>
        `;

        this.tagsContainer.appendChild(tagElement);

        // Trigger change event
        this.dispatchChangeEvent();
    }

    removeTag(tagId, tagName) {
        const tagKey = tagName.toLowerCase();
        this.tags.delete(tagKey);

        const tagElement = this.tagsContainer
            .querySelector(
                `[data-tag-id="${tagId}"][data-tag-name="${tagName}"]`,
            )
            ?.closest(".tag-item");

        if (tagElement) {
            tagElement.remove();
        }

        this.dispatchChangeEvent();
    }

    removeLastTag() {
        const lastTag = this.tagsContainer.querySelector(
            ".tag-item:last-child",
        );
        if (lastTag) {
            const removeBtn = lastTag.querySelector(".tag-remove");
            if (removeBtn) {
                this.removeTag(
                    removeBtn.dataset.tagId,
                    removeBtn.dataset.tagName,
                );
            }
        }
    }

    async generateTags() {
        if (!this.options.allowAutoGenerate) return;

        try {
            // Get content from article or current context
            const content = this.getContentForTagGeneration();

            if (!content) {
                this.showError("No content available for tag generation");
                return;
            }

            const response = await fetch("/tags/suggest", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    content: content,
                    article_id: this.getArticleId(),
                }),
            });

            if (response.ok) {
                const data = await response.json();
                this.showGeneratedSuggestions(data.suggestions);
            }
        } catch (error) {
            console.error("Failed to generate tags:", error);
            this.showError("Failed to generate tag suggestions");
        }
    }

    showGeneratedSuggestions(suggestions) {
        if (!suggestions || suggestions.length === 0) {
            this.showError("No tag suggestions generated");
            return;
        }

        const suggestionsHtml = suggestions
            .map(
                (suggestion) => `
            <div class="generated-suggestion" data-suggestion='${JSON.stringify(suggestion)}'>
                <span class="tag-name">${suggestion.tag.name || suggestion.tag}</span>
                <span class="confidence">${Math.round((suggestion.score || 0) * 10)}%</span>
                <button type="button" class="btn-add-suggestion">Add</button>
            </div>
        `,
            )
            .join("");

        this.suggestionsContainer.innerHTML = `
            <div class="generated-suggestions">
                <h4>Suggested Tags</h4>
                ${suggestionsHtml}
            </div>
        `;

        this.showSuggestions();

        // Bind add suggestion events
        this.suggestionsContainer
            .querySelectorAll(".btn-add-suggestion")
            .forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    const suggestionDiv = e.target.closest(
                        ".generated-suggestion",
                    );
                    const suggestion = JSON.parse(
                        suggestionDiv.dataset.suggestion,
                    );

                    if (suggestion.tag.id) {
                        this.addTag(
                            suggestion.tag.id,
                            suggestion.tag.name,
                            suggestion.tag.color,
                        );
                    } else {
                        this.createOrAddTag(
                            suggestion.tag.name || suggestion.tag,
                        );
                    }

                    suggestionDiv.remove();
                });
            });
    }

    openTagManager() {
        // Dispatch event to open full tag management interface
        const event = new CustomEvent("openTagManager", {
            detail: { tagManager: this },
        });
        document.dispatchEvent(event);
    }

    // Utility methods
    getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }

    getContentForTagGeneration() {
        // Override this method based on your content source
        const articleContent =
            document.querySelector(".article-content")?.textContent;
        const articleTitle =
            document.querySelector(".article-title")?.textContent;

        return [articleTitle, articleContent].filter(Boolean).join(" ");
    }

    getArticleId() {
        // Override this method to get current article ID
        return document.querySelector("[data-article-id]")?.dataset.articleId;
    }

    generateRandomColor() {
        const colors = [
            "#FF6B6B",
            "#4ECDC4",
            "#45B7D1",
            "#96CEB4",
            "#FFEAA7",
            "#DDA0DD",
            "#98D8C8",
            "#F7DC6F",
            "#BB8FCE",
            "#85C1E9",
        ];
        return colors[Math.floor(Math.random() * colors.length)];
    }

    showError(message) {
        // Create error notification
        const errorDiv = document.createElement("div");
        errorDiv.className = "tag-error";
        errorDiv.textContent = message;

        this.container.appendChild(errorDiv);

        setTimeout(() => {
            errorDiv.remove();
        }, 3000);
    }

    dispatchChangeEvent() {
        const event = new CustomEvent("tagsChanged", {
            detail: {
                tags: Array.from(this.tags),
                tagManager: this,
            },
        });
        this.container.dispatchEvent(event);
    }

    // Public API methods
    getTags() {
        return Array.from(this.tags);
    }

    setTags(tags) {
        this.clearTags();
        tags.forEach((tag) => {
            this.addTag(tag.id, tag.name, tag.color);
        });
    }

    clearTags() {
        this.tags.clear();
        this.tagsContainer.innerHTML = "";
        this.dispatchChangeEvent();
    }

    destroy() {
        // Cleanup event listeners and elements
        this.container.removeEventListener("input", this.handleInput);
        this.container.removeEventListener("keydown", this.handleKeydown);
        this.container.removeEventListener("focus", this.showSuggestions);
        this.container.removeEventListener("blur", this.hideSuggestions);

        // Remove generated elements if any
        const generatedElements =
            this.container.querySelectorAll(".tag-manager");
        generatedElements.forEach((el) => el.remove());
    }
}

// CSS styles for tag manager
const tagManagerStyles = `
.tag-manager {
    position: relative;
    width: 100%;
}

.tag-input-container {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px;
    min-height: 48px;
    background: white;
    transition: border-color 0.2s ease;
}

.tag-input-container:focus-within {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-right: 8px;
}

.tag-item {
    display: inline-flex;
    align-items: center;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 4px 8px 4px 6px;
    font-size: 14px;
    color: #334155;
    transition: all 0.2s ease;
}

.tag-item:hover {
    background: #e2e8f0;
}

.tag-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 6px;
    flex-shrink: 0;
}

.tag-name {
    margin-right: 6px;
    font-weight: 500;
}

.tag-remove {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 2px;
    border-radius: 50%;
    transition: all 0.2s ease;
    font-size: 12px;
}

.tag-remove:hover {
    background: #dc2626;
    color: white;
}

.tag-input {
    border: none;
    outline: none;
    background: transparent;
    font-size: 14px;
    color: #334155;
    min-width: 120px;
    flex: 1;
}

.tag-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.tag-suggestion {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid #f1f5f9;
}

.tag-suggestion:last-child {
    border-bottom: none;
}

.tag-suggestion:hover,
.tag-suggestion.selected {
    background-color: #f8fafc;
}

.tag-suggestion .tag-color {
    margin-right: 8px;
}

.tag-suggestion .tag-name {
    flex: 1;
    font-weight: 500;
}

.tag-suggestion .tag-description {
    color: #64748b;
    font-size: 12px;
    margin-left: 8px;
}

.tag-suggestion .tag-usage {
    background: #e2e8f0;
    color: #475569;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.tag-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.tag-actions button {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tag-actions button:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
}

.generated-suggestions {
    padding: 12px;
}

.generated-suggestions h4 {
    margin: 0 0 8px 0;
    color: #334155;
    font-size: 14px;
}

.generated-suggestion {
    display: flex;
    align-items: center;
    padding: 6px 8px;
    margin-bottom: 4px;
    background: #f8fafc;
    border-radius: 6px;
}

.generated-suggestion .tag-name {
    flex: 1;
    font-weight: 500;
}

.generated-suggestion .confidence {
    color: #64748b;
    font-size: 11px;
    margin-right: 8px;
}

.generated-suggestion .btn-add-suggestion {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 11px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.generated-suggestion .btn-add-suggestion:hover {
    background: #2563eb;
}

.no-suggestions {
    padding: 12px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
}

.loading {
    padding: 12px;
    text-align: center;
    color: #64748b;
}

.tag-error {
    background: #fee2e2;
    color: #dc2626;
    padding: 8px 12px;
    border-radius: 6px;
    margin-top: 8px;
    font-size: 14px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive design */
@media (max-width: 640px) {
    .tag-input-container {
        padding: 6px;
        min-height: 40px;
    }
    
    .tag-item {
        font-size: 12px;
        padding: 3px 6px 3px 5px;
    }
    
    .tag-input {
        font-size: 12px;
        min-width: 80px;
    }
    
    .tag-suggestions {
        max-height: 250px;
    }
}
`;

// Inject styles if not already present
if (!document.querySelector("#tag-manager-styles")) {
    const styleSheet = document.createElement("style");
    styleSheet.id = "tag-manager-styles";
    styleSheet.textContent = tagManagerStyles;
    document.head.appendChild(styleSheet);
}

// Export for use in other modules
window.TagManager = TagManager;
