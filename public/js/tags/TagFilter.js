/**
 * TagFilter - Advanced tag filtering component for article library
 * Supports multi-select, tag clouds, and dynamic filtering
 */
class TagFilter {
    constructor(options = {}) {
        this.options = {
            container: null,
            articlesContainer: ".articles-container",
            filterType: "multi", // 'multi', 'single', 'cloud'
            showCounts: true,
            showRelated: true,
            allowClear: true,
            onFilterChange: null,
            ...options,
        };

        this.selectedTags = new Set();
        this.availableTags = [];
        this.articles = [];
        this.filteredArticles = [];
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

        this.articlesContainer = document.querySelector(
            this.options.articlesContainer,
        );

        this.setupElements();
        this.bindEvents();
        this.loadTags();
        this.loadArticles();
    }

    setupElements() {
        // Create filter structure if not exists
        if (!this.container.querySelector(".tag-filter-container")) {
            this.container.innerHTML = this.getDefaultTemplate();
        }

        this.filterContainer = this.container.querySelector(
            ".tag-filter-container",
        );
        this.tagsContainer = this.container.querySelector(".available-tags");
        this.selectedContainer = this.container.querySelector(".selected-tags");
        this.relatedContainer = this.container.querySelector(".related-tags");
        this.clearButton = this.container.querySelector(".clear-filters");
        this.searchInput = this.container.querySelector(".tag-search");

        // Hide related container initially
        if (this.relatedContainer) {
            this.relatedContainer.style.display = "none";
        }
    }

    getDefaultTemplate() {
        return `
            <div class="tag-filter-container">
                <div class="filter-header">
                    <h3>Filter by Tags</h3>
                    ${this.options.allowClear ? '<button type="button" class="clear-filters" style="display: none;">Clear All</button>' : ""}
                </div>
                
                <div class="tag-search-container">
                    <input type="text" class="tag-search" placeholder="Search tags..." />
                </div>
                
                <div class="selected-tags" style="display: none;">
                    <h4>Selected Tags</h4>
                    <div class="selected-tags-list"></div>
                </div>
                
                <div class="available-tags">
                    <h4>Available Tags</h4>
                    <div class="tags-list"></div>
                </div>
                
                ${
                    this.options.showRelated
                        ? `
                    <div class="related-tags">
                        <h4>Related Tags</h4>
                        <div class="related-tags-list"></div>
                    </div>
                `
                        : ""
                }
                
                <div class="filter-stats">
                    <span class="articles-count">0 articles</span>
                    <span class="tags-count">0 tags selected</span>
                </div>
            </div>
        `;
    }

    bindEvents() {
        // Search functionality
        if (this.searchInput) {
            this.searchInput.addEventListener("input", (e) => {
                this.filterTags(e.target.value);
            });
        }

        // Clear filters
        if (this.clearButton) {
            this.clearButton.addEventListener("click", () => {
                this.clearAllFilters();
            });
        }

        // Tag selection events
        if (this.tagsContainer) {
            this.tagsContainer.addEventListener("click", (e) => {
                const tagElement = e.target.closest(".tag-filter-item");
                if (tagElement) {
                    e.preventDefault();
                    const tagId = tagElement.dataset.tagId;
                    const tagName = tagElement.dataset.tagName;
                    this.toggleTag(tagId, tagName);
                }
            });
        }

        // Selected tag removal
        if (this.selectedContainer) {
            this.selectedContainer.addEventListener("click", (e) => {
                const removeBtn = e.target.closest(".remove-tag");
                if (removeBtn) {
                    e.preventDefault();
                    const tagId = removeBtn.dataset.tagId;
                    this.removeTag(tagId);
                }
            });
        }

        // Related tags
        if (this.relatedContainer) {
            this.relatedContainer.addEventListener("click", (e) => {
                const tagElement = e.target.closest(".related-tag-item");
                if (tagElement) {
                    e.preventDefault();
                    const tagId = tagElement.dataset.tagId;
                    const tagName = tagElement.dataset.tagName;
                    this.addTag(tagId, tagName);
                }
            });
        }
    }

    async loadTags() {
        try {
            this.isLoading = true;
            this.showLoadingState();

            const response = await fetch(
                "/tags?sort_by=usage_count&sort_order=desc",
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (response.ok) {
                const data = await response.json();
                this.availableTags = data.tags.data || [];
                this.renderTags();
            }
        } catch (error) {
            console.error("Failed to load tags:", error);
            this.showError("Failed to load tags");
        } finally {
            this.isLoading = false;
        }
    }

    async loadArticles() {
        try {
            const response = await fetch("/articles", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.articles = data.articles.data || [];
                this.filteredArticles = [...this.articles];
                this.updateStats();
            }
        } catch (error) {
            console.error("Failed to load articles:", error);
            this.showError("Failed to load articles");
        }
    }

    renderTags() {
        if (!this.tagsContainer) return;

        const tagsList = this.tagsContainer.querySelector(".tags-list");
        if (!tagsList) return;

        if (this.availableTags.length === 0) {
            tagsList.innerHTML = '<div class="no-tags">No tags available</div>';
            return;
        }

        const tagsHtml = this.availableTags
            .map(
                (tag) => `
            <div class="tag-filter-item ${this.selectedTags.has(tag.id.toString()) ? "selected" : ""}" 
                 data-tag-id="${tag.id}" 
                 data-tag-name="${tag.name}"
                 data-tag-color="${tag.color}">
                <span class="tag-color" style="background-color: ${tag.color}"></span>
                <span class="tag-name">${tag.name}</span>
                ${this.options.showCounts ? `<span class="tag-count">${tag.usage_count || 0}</span>` : ""}
            </div>
        `,
            )
            .join("");

        tagsList.innerHTML = tagsHtml;
    }

    renderSelectedTags() {
        if (!this.selectedContainer) return;

        const selectedList = this.selectedContainer.querySelector(
            ".selected-tags-list",
        );
        if (!selectedList) return;

        if (this.selectedTags.size === 0) {
            this.selectedContainer.style.display = "none";
            return;
        }

        this.selectedContainer.style.display = "block";

        const selectedTagsData = Array.from(this.selectedTags).map((tagId) => {
            const tag = this.availableTags.find(
                (t) => t.id.toString() === tagId,
            );
            return tag || { id: tagId, name: "Unknown", color: "#ccc" };
        });

        const selectedHtml = selectedTagsData
            .map(
                (tag) => `
            <div class="selected-tag-item">
                <span class="tag-color" style="background-color: ${tag.color}"></span>
                <span class="tag-name">${tag.name}</span>
                <button type="button" class="remove-tag" data-tag-id="${tag.id}" title="Remove tag">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `,
            )
            .join("");

        selectedList.innerHTML = selectedHtml;
    }

    toggleTag(tagId, tagName) {
        if (this.selectedTags.has(tagId)) {
            this.removeTag(tagId);
        } else {
            this.addTag(tagId, tagName);
        }
    }

    addTag(tagId, tagName) {
        if (this.options.filterType === "single") {
            this.selectedTags.clear();
        }

        this.selectedTags.add(tagId);
        this.updateFilterDisplay();
        this.applyFilters();
        this.loadRelatedTags(tagId);
    }

    removeTag(tagId) {
        this.selectedTags.delete(tagId);
        this.updateFilterDisplay();
        this.applyFilters();

        if (this.selectedTags.size === 0) {
            this.hideRelatedTags();
        }
    }

    updateFilterDisplay() {
        this.renderTags();
        this.renderSelectedTags();

        // Update clear button visibility
        if (this.clearButton) {
            this.clearButton.style.display =
                this.selectedTags.size > 0 ? "block" : "none";
        }

        this.updateStats();
    }

    applyFilters() {
        if (this.selectedTags.size === 0) {
            this.filteredArticles = [...this.articles];
        } else {
            const selectedTagIds = Array.from(this.selectedTags);
            this.filteredArticles = this.articles.filter((article) => {
                if (!article.tags || article.tags.length === 0) return false;

                const articleTagIds = article.tags.map((tag) =>
                    tag.id.toString(),
                );

                if (this.options.filterType === "multi") {
                    // Articles must have ALL selected tags
                    return selectedTagIds.every((tagId) =>
                        articleTagIds.includes(tagId),
                    );
                } else {
                    // Articles must have AT LEAST ONE selected tag
                    return selectedTagIds.some((tagId) =>
                        articleTagIds.includes(tagId),
                    );
                }
            });
        }

        this.renderFilteredArticles();

        // Trigger callback
        if (this.options.onFilterChange) {
            this.options.onFilterChange(
                this.filteredArticles,
                this.selectedTags,
            );
        }
    }

    renderFilteredArticles() {
        if (!this.articlesContainer) return;

        // Dispatch custom event for article filtering
        const event = new CustomEvent("articlesFiltered", {
            detail: {
                articles: this.filteredArticles,
                selectedTags: Array.from(this.selectedTags),
            },
        });
        document.dispatchEvent(event);

        // Update article display (if articlesContainer has custom rendering)
        this.updateArticleDisplay();
    }

    updateArticleDisplay() {
        // This method should be overridden based on how articles are displayed
        // For now, we'll just dispatch the event and let other components handle it
        console.log(
            `Filtered ${this.filteredArticles.length} articles with tags:`,
            Array.from(this.selectedTags),
        );
    }

    filterTags(searchQuery) {
        if (!searchQuery.trim()) {
            this.renderTags();
            return;
        }

        const filteredTags = this.availableTags.filter(
            (tag) =>
                tag.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                (tag.description &&
                    tag.description
                        .toLowerCase()
                        .includes(searchQuery.toLowerCase())),
        );

        this.renderFilteredTags(filteredTags);
    }

    renderFilteredTags(tags) {
        if (!this.tagsContainer) return;

        const tagsList = this.tagsContainer.querySelector(".tags-list");
        if (!tagsList) return;

        if (tags.length === 0) {
            tagsList.innerHTML =
                '<div class="no-matching-tags">No matching tags</div>';
            return;
        }

        const tagsHtml = tags
            .map(
                (tag) => `
            <div class="tag-filter-item ${this.selectedTags.has(tag.id.toString()) ? "selected" : ""}" 
                 data-tag-id="${tag.id}" 
                 data-tag-name="${tag.name}"
                 data-tag-color="${tag.color}">
                <span class="tag-color" style="background-color: ${tag.color}"></span>
                <span class="tag-name">${tag.name}</span>
                ${this.options.showCounts ? `<span class="tag-count">${tag.usage_count || 0}</span>` : ""}
            </div>
        `,
            )
            .join("");

        tagsList.innerHTML = tagsHtml;
    }

    async loadRelatedTags(tagId) {
        if (!this.options.showRelated || !this.relatedContainer) return;

        try {
            const response = await fetch(`/tags/${tagId}`, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.renderRelatedTags(data.related_tags || []);
            }
        } catch (error) {
            console.error("Failed to load related tags:", error);
        }
    }

    renderRelatedTags(relatedTags) {
        if (!this.relatedContainer) return;

        const relatedList =
            this.relatedContainer.querySelector(".related-tags-list");
        if (!relatedList) return;

        if (relatedTags.length === 0) {
            this.relatedContainer.style.display = "none";
            return;
        }

        this.relatedContainer.style.display = "block";

        const relatedHtml = relatedTags
            .map(
                (tag) => `
            <div class="related-tag-item" 
                 data-tag-id="${tag.id}" 
                 data-tag-name="${tag.name}"
                 data-tag-color="${tag.color}">
                <span class="tag-color" style="background-color: ${tag.color}"></span>
                <span class="tag-name">${tag.name}</span>
                <span class="tag-usage">${tag.usage_count || 0}</span>
            </div>
        `,
            )
            .join("");

        relatedList.innerHTML = relatedHtml;
    }

    hideRelatedTags() {
        if (this.relatedContainer) {
            this.relatedContainer.style.display = "none";
        }
    }

    clearAllFilters() {
        this.selectedTags.clear();
        this.updateFilterDisplay();
        this.applyFilters();
        this.hideRelatedTags();
    }

    updateStats() {
        const articlesCount = this.filteredArticles.length;
        const tagsCount = this.selectedTags.size;

        const articlesCountEl = this.container.querySelector(".articles-count");
        const tagsCountEl = this.container.querySelector(".tags-count");

        if (articlesCountEl) {
            articlesCountEl.textContent = `${articlesCount} articles`;
        }

        if (tagsCountEl) {
            tagsCountEl.textContent = `${tagsCount} tags selected`;
        }
    }

    showLoadingState() {
        if (this.tagsContainer) {
            const tagsList = this.tagsContainer.querySelector(".tags-list");
            if (tagsList) {
                tagsList.innerHTML =
                    '<div class="loading-tags">Loading tags...</div>';
            }
        }
    }

    showError(message) {
        if (this.tagsContainer) {
            const tagsList = this.tagsContainer.querySelector(".tags-list");
            if (tagsList) {
                tagsList.innerHTML = `<div class="error-loading-tags">${message}</div>`;
            }
        }
    }

    getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }

    // Public API methods
    getSelectedTags() {
        return Array.from(this.selectedTags);
    }

    setSelectedTags(tagIds) {
        this.selectedTags.clear();
        tagIds.forEach((tagId) => {
            const tag = this.availableTags.find(
                (t) => t.id.toString() === tagId,
            );
            if (tag) {
                this.selectedTags.add(tagId);
            }
        });
        this.updateFilterDisplay();
        this.applyFilters();
    }

    getFilteredArticles() {
        return this.filteredArticles;
    }

    refresh() {
        this.loadTags();
        this.loadArticles();
    }

    destroy() {
        // Cleanup event listeners
        if (this.searchInput) {
            this.searchInput.removeEventListener("input", this.filterTags);
        }
        if (this.clearButton) {
            this.clearButton.removeEventListener("click", this.clearAllFilters);
        }

        // Remove generated elements
        const generatedElements = this.container.querySelectorAll(
            ".tag-filter-container",
        );
        generatedElements.forEach((el) => el.remove());
    }
}

// CSS styles for tag filter
const tagFilterStyles = `
.tag-filter-container {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.filter-header h3 {
    margin: 0;
    color: #334155;
    font-size: 16px;
    font-weight: 600;
}

.clear-filters {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.clear-filters:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
}

.tag-search-container {
    margin-bottom: 16px;
}

.tag-search {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.tag-search:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.selected-tags,
.available-tags,
.related-tags {
    margin-bottom: 16px;
}

.selected-tags h4,
.available-tags h4,
.related-tags h4 {
    margin: 0 0 8px 0;
    color: #475569;
    font-size: 14px;
    font-weight: 500;
}

.selected-tags-list,
.tags-list,
.related-tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.tag-filter-item,
.related-tag-item {
    display: inline-flex;
    align-items: center;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 6px 12px;
    font-size: 13px;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.tag-filter-item:hover,
.related-tag-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.tag-filter-item.selected {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.tag-filter-item .tag-color,
.related-tag-item .tag-color {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 6px;
    flex-shrink: 0;
}

.tag-filter-item .tag-name,
.related-tag-item .tag-name {
    margin-right: 6px;
}

.tag-filter-item .tag-count,
.related-tag-item .tag-usage {
    background: rgba(0, 0, 0, 0.1);
    color: inherit;
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: auto;
}

.selected-tag-item {
    display: inline-flex;
    align-items: center;
    background: #3b82f6;
    color: white;
    border-radius: 16px;
    padding: 4px 8px 4px 12px;
    font-size: 13px;
    margin: 2px;
}

.selected-tag-item .tag-color {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 6px;
    background: white !important;
}

.selected-tag-item .tag-name {
    margin-right: 6px;
}

.selected-tag-item .remove-tag {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    cursor: pointer;
    padding: 2px;
    border-radius: 50%;
    font-size: 10px;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.selected-tag-item .remove-tag:hover {
    background: rgba(255, 255, 255, 0.3);
}

.filter-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #e2e8f0;
    font-size: 12px;
    color: #64748b;
}

.loading-tags,
.no-tags,
.no-matching-tags,
.error-loading-tags {
    text-align: center;
    padding: 20px;
    color: #64748b;
    font-size: 14px;
}

.error-loading-tags {
    color: #dc2626;
}

/* Tag cloud view */
.tag-filter-container.cloud-view .tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center;
}

.tag-filter-container.cloud-view .tag-filter-item {
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 12px;
}

.tag-filter-container.cloud-view .tag-filter-item.large {
    font-size: 16px;
    padding: 8px 12px;
}

.tag-filter-container.cloud-view .tag-filter-item.medium {
    font-size: 14px;
    padding: 6px 10px;
}

.tag-filter-container.cloud-view .tag-filter-item.small {
    font-size: 11px;
    padding: 3px 6px;
}

/* Responsive design */
@media (max-width: 640px) {
    .tag-filter-container {
        padding: 12px;
    }
    
    .filter-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .selected-tags-list,
    .tags-list,
    .related-tags-list {
        gap: 6px;
    }
    
    .tag-filter-item,
    .related-tag-item {
        font-size: 12px;
        padding: 5px 10px;
    }
}
`;

// Inject styles if not already present
if (!document.querySelector("#tag-filter-styles")) {
    const styleSheet = document.createElement("style");
    styleSheet.id = "tag-filter-styles";
    styleSheet.textContent = tagFilterStyles;
    document.head.appendChild(styleSheet);
}

// Export for use in other modules
window.TagFilter = TagFilter;
