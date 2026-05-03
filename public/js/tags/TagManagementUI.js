/**
 * TagManagementUI - Complete tag management interface
 * Provides comprehensive tag management with statistics, bulk operations, and analytics
 */
class TagManagementUI {
    constructor(options = {}) {
        this.options = {
            container: null,
            onTagSelect: null,
            onTagUpdate: null,
            onTagDelete: null,
            allowBulkOperations: true,
            showStatistics: true,
            showAnalytics: true,
            ...options,
        };

        this.tags = [];
        this.selectedTags = new Set();
        this.filteredTags = [];
        this.statistics = {};
        this.isLoading = false;
        this.tagService = new TagService();

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
        this.loadData();
    }

    setupElements() {
        this.container.innerHTML = this.getMainTemplate();

        this.mainContainer = this.container.querySelector(".tag-management-ui");
        this.headerSection = this.container.querySelector(
            ".tag-management-header",
        );
        this.statsSection = this.container.querySelector(".tag-statistics");
        this.analyticsSection = this.container.querySelector(".tag-analytics");
        this.tagsSection = this.container.querySelector(".tags-section");
        this.bulkActionsSection = this.container.querySelector(".bulk-actions");

        this.searchInput = this.container.querySelector(".tag-search-input");
        this.filterSelect = this.container.querySelector(".tag-filter-select");
        this.sortSelect = this.container.querySelector(".tag-sort-select");
        this.createTagBtn = this.container.querySelector(".create-tag-btn");
        this.refreshBtn = this.container.querySelector(".refresh-tags-btn");

        this.tagsList = this.container.querySelector(".tags-list");
        this.selectAllCheckbox =
            this.container.querySelector(".select-all-tags");

        // Hide sections based on options
        if (!this.options.showStatistics) {
            this.statsSection.style.display = "none";
        }
        if (!this.options.showAnalytics) {
            this.analyticsSection.style.display = "none";
        }
        if (!this.options.allowBulkOperations) {
            this.bulkActionsSection.style.display = "none";
        }
    }

    getMainTemplate() {
        return `
            <div class="tag-management-ui">
                <div class="tag-management-header">
                    <h2>Tag Management</h2>
                    <div class="header-actions">
                        <button type="button" class="refresh-tags-btn" title="Refresh">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button type="button" class="create-tag-btn btn-primary">
                            <i class="fas fa-plus"></i> Create Tag
                        </button>
                    </div>
                </div>
                
                <div class="tag-statistics">
                    <h3>Tag Statistics</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value" id="total-tags">0</div>
                            <div class="stat-label">Total Tags</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="most-used-tag">-</div>
                            <div class="stat-label">Most Used Tag</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="auto-generated-tags">0</div>
                            <div class="stat-label">Auto Generated</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="total-usage">0</div>
                            <div class="stat-label">Total Usage</div>
                        </div>
                    </div>
                </div>
                
                <div class="tag-analytics">
                    <h3>Tag Analytics</h3>
                    <div class="analytics-container">
                        <div class="chart-container">
                            <canvas id="tag-usage-chart"></canvas>
                        </div>
                        <div class="analytics-summary">
                            <div class="summary-item">
                                <span class="summary-label">Average Usage:</span>
                                <span class="summary-value" id="avg-usage">0</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Most Active:</span>
                                <span class="summary-value" id="most-active-period">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="tags-controls">
                    <div class="search-filter-group">
                        <input type="text" class="tag-search-input" placeholder="Search tags...">
                        <select class="tag-filter-select">
                            <option value="all">All Tags</option>
                            <option value="custom">Custom Tags</option>
                            <option value="auto">Auto Generated</option>
                            <option value="system">System Tags</option>
                        </select>
                        <select class="tag-sort-select">
                            <option value="name">Sort by Name</option>
                            <option value="usage">Sort by Usage</option>
                            <option value="created">Sort by Created Date</option>
                            <option value="updated">Sort by Updated Date</option>
                        </select>
                    </div>
                </div>
                
                <div class="bulk-actions" style="display: none;">
                    <div class="bulk-actions-header">
                        <input type="checkbox" class="select-all-tags">
                        <span class="selected-count">0 selected</span>
                        <div class="bulk-actions-buttons">
                            <button type="button" class="bulk-delete-btn btn-danger" disabled>
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                            <button type="button" class="bulk-merge-btn btn-warning" disabled>
                                <i class="fas fa-compress-arrows-alt"></i> Merge Selected
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="tags-section">
                    <div class="tags-list-header">
                        <h3>Tags</h3>
                        <div class="tags-count">
                            <span id="visible-tags-count">0</span> of <span id="total-tags-count">0</span> tags
                        </div>
                    </div>
                    <div class="tags-list"></div>
                    <div class="loading-state" style="display: none;">
                        <div class="loading-spinner"></div>
                        <p>Loading tags...</p>
                    </div>
                    <div class="empty-state" style="display: none;">
                        <i class="fas fa-tags"></i>
                        <p>No tags found</p>
                    </div>
                </div>
                
                <!-- Create/Edit Tag Modal -->
                <div class="tag-modal" id="tag-modal" style="display: none;">
                    <div class="modal-overlay"></div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 id="modal-title">Create Tag</h3>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <form class="tag-form" id="tag-form">
                            <div class="form-group">
                                <label for="tag-name">Name *</label>
                                <input type="text" id="tag-name" name="name" required>
                                <div class="error-message" id="name-error"></div>
                            </div>
                            <div class="form-group">
                                <label for="tag-description">Description</label>
                                <textarea id="tag-description" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="tag-color">Color</label>
                                <div class="color-picker-container">
                                    <input type="color" id="tag-color" name="color" value="#3b82f6">
                                    <div class="color-presets">
                                        <button type="button" class="color-preset" data-color="#3b82f6" style="background-color: #3b82f6;"></button>
                                        <button type="button" class="color-preset" data-color="#ef4444" style="background-color: #ef4444;"></button>
                                        <button type="button" class="color-preset" data-color="#10b981" style="background-color: #10b981;"></button>
                                        <button type="button" class="color-preset" data-color="#f59e0b" style="background-color: #f59e0b;"></button>
                                        <button type="button" class="color-preset" data-color="#8b5cf6" style="background-color: #8b5cf6;"></button>
                                        <button type="button" class="color-preset" data-color="#06b6d4" style="background-color: #06b6d4;"></button>
                                    </div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn-secondary modal-cancel">Cancel</button>
                                <button type="submit" class="btn-primary">Save Tag</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }

    bindEvents() {
        // Header actions
        this.createTagBtn.addEventListener("click", () =>
            this.showCreateModal(),
        );
        this.refreshBtn.addEventListener("click", () => this.refreshData());

        // Search and filter
        this.searchInput.addEventListener("input", (e) =>
            this.handleSearch(e.target.value),
        );
        this.filterSelect.addEventListener("change", (e) =>
            this.handleFilter(e.target.value),
        );
        this.sortSelect.addEventListener("change", (e) =>
            this.handleSort(e.target.value),
        );

        // Bulk operations
        if (this.options.allowBulkOperations) {
            this.selectAllCheckbox.addEventListener("change", (e) =>
                this.handleSelectAll(e.target.checked),
            );
            this.container
                .querySelector(".bulk-delete-btn")
                .addEventListener("click", () => this.handleBulkDelete());
            this.container
                .querySelector(".bulk-merge-btn")
                .addEventListener("click", () => this.handleBulkMerge());
        }

        // Modal events
        this.setupModalEvents();

        // Color preset events
        this.container.querySelectorAll(".color-preset").forEach((preset) => {
            preset.addEventListener("click", (e) => {
                const color = e.target.dataset.color;
                this.container.querySelector("#tag-color").value = color;
            });
        });
    }

    setupModalEvents() {
        const modal = this.container.querySelector("#tag-modal");
        const form = this.container.querySelector("#tag-form");

        // Close modal
        modal
            .querySelector(".modal-close")
            .addEventListener("click", () => this.hideModal());
        modal
            .querySelector(".modal-cancel")
            .addEventListener("click", () => this.hideModal());
        modal
            .querySelector(".modal-overlay")
            .addEventListener("click", () => this.hideModal());

        // Form submission
        form.addEventListener("submit", (e) => this.handleFormSubmit(e));

        // Enter key handling
        modal.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                this.hideModal();
            }
        });
    }

    async loadData() {
        this.showLoading();

        try {
            const [tagsData, statsData] = await Promise.all([
                this.tagService.getTags({ per_page: 100 }),
                this.tagService.getTagStatistics(),
            ]);

            this.tags = tagsData.tags?.data || [];
            this.filteredTags = [...this.tags];
            this.statistics = statsData;

            this.renderStatistics();
            this.renderTags();
            this.hideLoading();
        } catch (error) {
            console.error("Failed to load data:", error);
            this.showError("Failed to load tag data");
            this.hideLoading();
        }
    }

    renderStatistics() {
        if (!this.options.showStatistics) return;

        const stats = this.statistics;

        document.getElementById("total-tags").textContent =
            stats.total_user_tags || 0;
        document.getElementById("most-used-tag").textContent =
            stats.most_used_tag?.name || "-";
        document.getElementById("auto-generated-tags").textContent =
            stats.auto_generated_tags || 0;
        document.getElementById("total-usage").textContent =
            stats.total_tag_usage || 0;

        // Update analytics
        if (this.options.showAnalytics) {
            const avgUsage =
                this.tags.length > 0
                    ? Math.round(stats.total_tag_usage / this.tags.length)
                    : 0;
            document.getElementById("avg-usage").textContent = avgUsage;
        }
    }

    renderTags() {
        if (this.filteredTags.length === 0) {
            this.showEmptyState();
            return;
        }

        this.hideEmptyState();

        const tagsHtml = this.filteredTags
            .map(
                (tag) => `
            <div class="tag-item ${this.selectedTags.has(tag.id.toString()) ? "selected" : ""}" 
                 data-tag-id="${tag.id}">
                <div class="tag-checkbox">
                    <input type="checkbox" class="tag-select" data-tag-id="${tag.id}">
                </div>
                <div class="tag-info">
                    <div class="tag-header">
                        <span class="tag-color" style="background-color: ${tag.color}"></span>
                        <span class="tag-name">${tag.name}</span>
                        <span class="tag-type ${tag.type}">${tag.type}</span>
                    </div>
                    ${tag.description ? `<div class="tag-description">${tag.description}</div>` : ""}
                    <div class="tag-stats">
                        <span class="usage-count">Used ${tag.usage_count || 0} times</span>
                        <span class="articles-count">${tag.articles_count || 0} articles</span>
                    </div>
                </div>
                <div class="tag-actions">
                    <button type="button" class="tag-edit-btn" data-tag-id="${tag.id}" title="Edit tag">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="tag-delete-btn" data-tag-id="${tag.id}" title="Delete tag">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `,
            )
            .join("");

        this.tagsList.innerHTML = tagsHtml;

        // Update counts
        document.getElementById("visible-tags-count").textContent =
            this.filteredTags.length;
        document.getElementById("total-tags-count").textContent =
            this.tags.length;

        // Bind tag events
        this.bindTagEvents();
    }

    bindTagEvents() {
        // Tag selection
        this.container.querySelectorAll(".tag-select").forEach((checkbox) => {
            checkbox.addEventListener("change", (e) => {
                const tagId = e.target.dataset.tagId;
                if (e.target.checked) {
                    this.selectedTags.add(tagId);
                } else {
                    this.selectedTags.delete(tagId);
                }
                this.updateBulkActions();
            });
        });

        // Edit button
        this.container.querySelectorAll(".tag-edit-btn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const tagId = e.target.closest(".tag-edit-btn").dataset.tagId;
                this.showEditModal(tagId);
            });
        });

        // Delete button
        this.container.querySelectorAll(".tag-delete-btn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                const tagId = e.target.closest(".tag-delete-btn").dataset.tagId;
                this.handleDeleteTag(tagId);
            });
        });
    }

    handleSearch(query) {
        if (!query.trim()) {
            this.filteredTags = [...this.tags];
        } else {
            this.filteredTags = this.tags.filter(
                (tag) =>
                    tag.name.toLowerCase().includes(query.toLowerCase()) ||
                    (tag.description &&
                        tag.description
                            .toLowerCase()
                            .includes(query.toLowerCase())),
            );
        }
        this.renderTags();
    }

    handleFilter(filterType) {
        switch (filterType) {
            case "custom":
                this.filteredTags = this.tags.filter(
                    (tag) => tag.type === "custom",
                );
                break;
            case "auto":
                this.filteredTags = this.tags.filter(
                    (tag) => tag.is_auto_generated,
                );
                break;
            case "system":
                this.filteredTags = this.tags.filter((tag) => !tag.user_id);
                break;
            default:
                this.filteredTags = [...this.tags];
        }
        this.renderTags();
    }

    handleSort(sortBy) {
        switch (sortBy) {
            case "name":
                this.filteredTags.sort((a, b) => a.name.localeCompare(b.name));
                break;
            case "usage":
                this.filteredTags.sort(
                    (a, b) => (b.usage_count || 0) - (a.usage_count || 0),
                );
                break;
            case "created":
                this.filteredTags.sort(
                    (a, b) => new Date(b.created_at) - new Date(a.created_at),
                );
                break;
            case "updated":
                this.filteredTags.sort(
                    (a, b) => new Date(b.updated_at) - new Date(a.updated_at),
                );
                break;
        }
        this.renderTags();
    }

    handleSelectAll(checked) {
        this.container.querySelectorAll(".tag-select").forEach((checkbox) => {
            checkbox.checked = checked;
            const tagId = checkbox.dataset.tagId;
            if (checked) {
                this.selectedTags.add(tagId);
            } else {
                this.selectedTags.delete(tagId);
            }
        });
        this.updateBulkActions();
    }

    updateBulkActions() {
        const selectedCount = this.selectedTags.size;
        const bulkDeleteBtn = this.container.querySelector(".bulk-delete-btn");
        const bulkMergeBtn = this.container.querySelector(".bulk-merge-btn");

        document.querySelector(".selected-count").textContent =
            `${selectedCount} selected`;

        if (selectedCount > 0) {
            bulkDeleteBtn.disabled = false;
            bulkMergeBtn.disabled = selectedCount < 2;
        } else {
            bulkDeleteBtn.disabled = true;
            bulkMergeBtn.disabled = true;
        }
    }

    showCreateModal() {
        this.currentEditingTag = null;
        document.getElementById("modal-title").textContent = "Create Tag";
        document.getElementById("tag-form").reset();
        document.getElementById("tag-color").value = "#3b82f6";
        this.showModal();
    }

    showEditModal(tagId) {
        const tag = this.tags.find((t) => t.id.toString() === tagId);
        if (!tag) return;

        this.currentEditingTag = tag;
        document.getElementById("modal-title").textContent = "Edit Tag";
        document.getElementById("tag-name").value = tag.name;
        document.getElementById("tag-description").value =
            tag.description || "";
        document.getElementById("tag-color").value = tag.color;
        this.showModal();
    }

    showModal() {
        const modal = this.container.querySelector("#tag-modal");
        modal.style.display = "block";
        document.getElementById("tag-name").focus();
    }

    hideModal() {
        const modal = this.container.querySelector("#tag-modal");
        modal.style.display = "none";
        this.currentEditingTag = null;
    }

    async handleFormSubmit(e) {
        e.preventDefault();

        const formData = new FormData(e.target);
        const tagData = {
            name: formData.get("name"),
            description: formData.get("description"),
            color: formData.get("color"),
        };

        // Clear previous errors
        document.getElementById("name-error").textContent = "";

        try {
            if (this.currentEditingTag) {
                await this.tagService.updateTag(
                    this.currentEditingTag.id,
                    tagData,
                );
            } else {
                await this.tagService.createTag(tagData);
            }

            this.hideModal();
            this.loadData();

            if (this.options.onTagUpdate) {
                this.options.onTagUpdate(tagData);
            }
        } catch (error) {
            if (error.message.includes("name")) {
                document.getElementById("name-error").textContent =
                    error.message;
            } else {
                this.showError(error.message);
            }
        }
    }

    async handleDeleteTag(tagId) {
        const tag = this.tags.find((t) => t.id.toString() === tagId);
        if (!tag) return;

        if (
            confirm(
                `Are you sure you want to delete the tag "${tag.name}"? This action cannot be undone.`,
            )
        ) {
            try {
                await this.tagService.deleteTag(tagId);
                this.loadData();

                if (this.options.onTagDelete) {
                    this.options.onTagDelete(tag);
                }
            } catch (error) {
                this.showError(error.message);
            }
        }
    }

    async handleBulkDelete() {
        if (this.selectedTags.size === 0) return;

        const tagNames = Array.from(this.selectedTags).map((id) => {
            const tag = this.tags.find((t) => t.id.toString() === id);
            return tag ? tag.name : "Unknown";
        });

        if (
            confirm(
                `Are you sure you want to delete ${this.selectedTags.size} tags?\n\nTags: ${tagNames.join(", ")}\n\nThis action cannot be undone.`,
            )
        ) {
            try {
                const promises = Array.from(this.selectedTags).map((tagId) =>
                    this.tagService.deleteTag(tagId),
                );
                await Promise.all(promises);

                this.selectedTags.clear();
                this.loadData();
            } catch (error) {
                this.showError("Failed to delete some tags: " + error.message);
            }
        }
    }

    handleBulkMerge() {
        if (this.selectedTags.size < 2) return;

        // Implementation for tag merging
        alert("Tag merging feature coming soon!");
    }

    refreshData() {
        this.loadData();
    }

    showLoading() {
        this.container.querySelector(".loading-state").style.display = "block";
        this.tagsList.style.display = "none";
    }

    hideLoading() {
        this.container.querySelector(".loading-state").style.display = "none";
        this.tagsList.style.display = "block";
    }

    showEmptyState() {
        this.container.querySelector(".empty-state").style.display = "block";
        this.tagsList.style.display = "none";
    }

    hideEmptyState() {
        this.container.querySelector(".empty-state").style.display = "none";
        this.tagsList.style.display = "block";
    }

    showError(message) {
        // Create error notification
        const errorDiv = document.createElement("div");
        errorDiv.className = "error-notification";
        errorDiv.textContent = message;

        this.container.insertBefore(errorDiv, this.mainContainer);

        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }

    // Public API methods
    getSelectedTags() {
        return Array.from(this.selectedTags)
            .map((id) => this.tags.find((tag) => tag.id.toString() === id))
            .filter(Boolean);
    }

    getAllTags() {
        return [...this.tags];
    }

    refresh() {
        this.loadData();
    }

    destroy() {
        // Cleanup event listeners
        this.searchInput.removeEventListener("input", this.handleSearch);
        this.filterSelect.removeEventListener("change", this.handleFilter);
        this.sortSelect.removeEventListener("change", this.handleSort);
        this.createTagBtn.removeEventListener("click", this.showCreateModal);
        this.refreshBtn.removeEventListener("click", this.refreshData);

        // Remove generated elements
        this.container.innerHTML = "";
    }
}

// CSS styles for tag management UI
const tagManagementUIStyles = `
.tag-management-ui {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.tag-management-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.tag-management-header h2 {
    margin: 0;
    color: #1e293b;
    font-size: 24px;
    font-weight: 600;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-warning {
    background: #f59e0b;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: background-color 0.2s ease;
}

.btn-warning:hover {
    background: #d97706;
}

.refresh-tags-btn {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.refresh-tags-btn:hover {
    background: #e2e8f0;
}

.tag-statistics {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
}

.tag-statistics h3 {
    margin: 0 0 16px 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.stat-card {
    background: #f8fafc;
    padding: 16px;
    border-radius: 6px;
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #3b82f6;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #64748b;
}

.tag-analytics {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
}

.tag-analytics h3 {
    margin: 0 0 16px 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
}

.analytics-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    align-items: start;
}

.chart-container {
    background: #f8fafc;
    padding: 16px;
    border-radius: 6px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.analytics-summary {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border-radius: 6px;
}

.summary-label {
    color: #64748b;
    font-size: 14px;
}

.summary-value {
    color: #1e293b;
    font-weight: 600;
    font-size: 14px;
}

.tags-controls {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
}

.search-filter-group {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.tag-search-input {
    flex: 1;
    min-width: 200px;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
}

.tag-filter-select,
.tag-sort-select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.bulk-actions {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 16px;
}

.bulk-actions-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.selected-count {
    font-weight: 600;
    color: #92400e;
}

.bulk-actions-buttons {
    margin-left: auto;
    display: flex;
    gap: 8px;
}

.tags-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.tags-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.tags-list-header h3 {
    margin: 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
}

.tags-count {
    color: #64748b;
    font-size: 14px;
}

.tags-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tag-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.tag-item:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.tag-item.selected {
    background: #dbeafe;
    border-color: #3b82f6;
}

.tag-checkbox {
    margin-right: 12px;
}

.tag-info {
    flex: 1;
}

.tag-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.tag-color {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    flex-shrink: 0;
}

.tag-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 16px;
}

.tag-type {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.tag-type.custom {
    background: #dbeafe;
    color: #1d4ed8;
}

.tag-type.auto {
    background: #fef3c7;
    color: #d97706;
}

.tag-type.system {
    background: #f3f4f6;
    color: #374151;
}

.tag-description {
    color: #64748b;
    font-size: 14px;
    margin-bottom: 8px;
}

.tag-stats {
    display: flex;
    gap: 16px;
    font-size: 12px;
    color: #64748b;
}

.usage-count,
.articles-count {
    display: flex;
    align-items: center;
    gap: 4px;
}

.tag-actions {
    display: flex;
    gap: 8px;
}

.tag-edit-btn,
.tag-delete-btn {
    background: none;
    border: 1px solid #e2e8f0;
    padding: 6px 8px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #64748b;
}

.tag-edit-btn:hover {
    background: #e0f2fe;
    border-color: #0284c7;
    color: #0284c7;
}

.tag-delete-btn:hover {
    background: #fee2e2;
    border-color: #dc2626;
    color: #dc2626;
}

.loading-state,
.empty-state {
    text-align: center;
    padding: 40px;
    color: #64748b;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f4f6;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 16px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-notification {
    background: #fee2e2;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    border: 1px solid #fecaca;
}

/* Modal Styles */
.tag-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1000;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
}

.modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    margin: 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #64748b;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #374151;
}

.tag-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 4px;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.error-message {
    color: #dc2626;
    font-size: 12px;
    margin-top: 4px;
}

.color-picker-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.color-presets {
    display: flex;
    gap: 8px;
}

.color-preset {
    width: 24px;
    height: 24px;
    border: 2px solid transparent;
    border-radius: 50%;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.color-preset:hover {
    border-color: #374151;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .tag-management-ui {
        padding: 16px;
    }
    
    .analytics-container {
        grid-template-columns: 1fr;
    }
    
    .search-filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .tag-search-input,
    .tag-filter-select,
    .tag-sort-select {
        width: 100%;
    }
    
    .bulk-actions-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .bulk-actions-buttons {
        margin-left: 0;
    }
    
    .tag-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .tag-actions {
        align-self: flex-end;
    }
}
`;

// Inject styles if not already present
if (!document.querySelector("#tag-management-ui-styles")) {
    const styleSheet = document.createElement("style");
    styleSheet.id = "tag-management-ui-styles";
    styleSheet.textContent = tagManagementUIStyles;
    document.head.appendChild(styleSheet);
}

// Export for use in other modules
window.TagManagementUI = TagManagementUI;
