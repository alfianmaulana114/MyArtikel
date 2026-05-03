/**
 * AdvancedSearch - Comprehensive search component with autocomplete, filters, and analytics
 * Supports instant search, search history, and advanced filtering
 */
class AdvancedSearch {
    constructor(options = {}) {
        this.options = {
            container: null,
            resultsContainer: ".search-results",
            searchInput: ".search-input",
            enableAutocomplete: true,
            enableHistory: true,
            enableFilters: true,
            enableAnalytics: true,
            debounceTime: 300,
            minChars: 2,
            maxHistoryItems: 10,
            showResultCount: true,
            highlightMatches: true,
            onSearch: null,
            onResultClick: null,
            ...options,
        };

        this.searchTerm = "";
        this.searchResults = [];
        this.searchHistory = [];
        this.suggestions = [];
        this.isSearching = false;
        this.currentFilters = {};
        this.debounceTimer = null;

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

        this.resultsContainer = document.querySelector(
            this.options.resultsContainer,
        );

        this.setupElements();
        this.bindEvents();
        this.loadSearchHistory();
        this.loadPopularSearches();
    }

    setupElements() {
        // Create search structure if not exists
        if (!this.container.querySelector(".advanced-search-container")) {
            this.container.innerHTML = this.getDefaultTemplate();
        }

        this.searchContainer = this.container.querySelector(
            ".advanced-search-container",
        );
        this.searchInput = this.container.querySelector(
            this.options.searchInput,
        );
        this.suggestionsContainer = this.container.querySelector(
            ".search-suggestions",
        );
        this.historyContainer = this.container.querySelector(".search-history");
        this.filtersContainer = this.container.querySelector(".search-filters");
        this.analyticsContainer =
            this.container.querySelector(".search-analytics");
        this.searchStats = this.container.querySelector(".search-stats");

        // Hide advanced features initially
        if (this.historyContainer) {
            this.historyContainer.style.display = "none";
        }
        if (this.filtersContainer) {
            this.filtersContainer.style.display = "none";
        }
        if (this.analyticsContainer) {
            this.analyticsContainer.style.display = "none";
        }
    }

    getDefaultTemplate() {
        return `
            <div class="advanced-search-container">
                <div class="search-header">
                    <div class="search-input-container">
                        <input type="text" 
                               class="search-input" 
                               placeholder="Search articles, notes, and more..."
                               autocomplete="off">
                        <button type="button" class="search-clear" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                        <button type="button" class="search-filters-toggle">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                    
                    <div class="search-actions">
                        <button type="button" class="search-history-toggle" title="Search History">
                            <i class="fas fa-history"></i>
                        </button>
                        <button type="button" class="search-analytics-toggle" title="Search Analytics">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                    </div>
                </div>
                
                <div class="search-suggestions"></div>
                
                <div class="search-history">
                    <div class="history-header">
                        <h4>Recent Searches</h4>
                        <button type="button" class="clear-history">Clear All</button>
                    </div>
                    <div class="history-list"></div>
                </div>
                
                <div class="search-filters">
                    <div class="filters-header">
                        <h4>Advanced Filters</h4>
                        <button type="button" class="reset-filters">Reset</button>
                    </div>
                    
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label>Status</label>
                            <select class="filter-status">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Date Range</label>
                            <div class="date-range">
                                <input type="date" class="filter-date-from" placeholder="From">
                                <input type="date" class="filter-date-to" placeholder="To">
                            </div>
                        </div>
                        
                        <div class="filter-group">
                            <label>Has Notes</label>
                            <select class="filter-has-notes">
                                <option value="">Any</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Has Bookmarks</label>
                            <select class="filter-has-bookmarks">
                                <option value="">Any</option>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort By</label>
                            <select class="filter-sort-by">
                                <option value="relevance">Relevance</option>
                                <option value="date">Date</option>
                                <option value="title">Title</option>
                                <option value="popularity">Popularity</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Sort Order</label>
                            <select class="filter-sort-order">
                                <option value="desc">Descending</option>
                                <option value="asc">Ascending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" class="apply-filters">Apply Filters</button>
                        <button type="button" class="cancel-filters">Cancel</button>
                    </div>
                </div>
                
                <div class="search-analytics">
                    <div class="analytics-header">
                        <h4>Search Analytics</h4>
                        <select class="analytics-period">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                    </div>
                    
                    <div class="analytics-content">
                        <div class="analytics-loading">Loading analytics...</div>
                    </div>
                </div>
                
                <div class="search-stats" style="display: none;">
                    <span class="results-count">0 results</span>
                    <span class="search-time">0ms</span>
                </div>
            </div>
        `;
    }

    bindEvents() {
        // Search input events
        if (this.searchInput) {
            this.searchInput.addEventListener("input", (e) =>
                this.handleInput(e),
            );
            this.searchInput.addEventListener("keydown", (e) =>
                this.handleKeydown(e),
            );
            this.searchInput.addEventListener("focus", () =>
                this.showSuggestions(),
            );
            this.searchInput.addEventListener("blur", () => {
                setTimeout(() => this.hideSuggestions(), 200);
            });
        }

        // Clear search button
        const clearBtn = this.container.querySelector(".search-clear");
        if (clearBtn) {
            clearBtn.addEventListener("click", () => this.clearSearch());
        }

        // Toggle buttons
        const historyToggle = this.container.querySelector(
            ".search-history-toggle",
        );
        if (historyToggle) {
            historyToggle.addEventListener("click", () => this.toggleHistory());
        }

        const filtersToggle = this.container.querySelector(
            ".search-filters-toggle",
        );
        if (filtersToggle) {
            filtersToggle.addEventListener("click", () => this.toggleFilters());
        }

        const analyticsToggle = this.container.querySelector(
            ".search-analytics-toggle",
        );
        if (analyticsToggle) {
            analyticsToggle.addEventListener("click", () =>
                this.toggleAnalytics(),
            );
        }

        // History events
        const clearHistoryBtn = this.container.querySelector(".clear-history");
        if (clearHistoryBtn) {
            clearHistoryBtn.addEventListener("click", () =>
                this.clearHistory(),
            );
        }

        if (this.historyContainer) {
            this.historyContainer.addEventListener("click", (e) => {
                const historyItem = e.target.closest(".history-item");
                if (historyItem) {
                    this.selectFromHistory(historyItem.dataset.query);
                }
            });
        }

        // Filter events
        const applyFiltersBtn = this.container.querySelector(".apply-filters");
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener("click", () =>
                this.applyFilters(),
            );
        }

        const cancelFiltersBtn =
            this.container.querySelector(".cancel-filters");
        if (cancelFiltersBtn) {
            cancelFiltersBtn.addEventListener("click", () =>
                this.cancelFilters(),
            );
        }

        const resetFiltersBtn = this.container.querySelector(".reset-filters");
        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener("click", () =>
                this.resetFilters(),
            );
        }

        // Suggestion events
        if (this.suggestionsContainer) {
            this.suggestionsContainer.addEventListener("click", (e) => {
                const suggestion = e.target.closest(".suggestion-item");
                if (suggestion) {
                    this.selectSuggestion(suggestion.dataset.suggestion);
                }
            });
        }

        // Analytics events
        const analyticsPeriod =
            this.container.querySelector(".analytics-period");
        if (analyticsPeriod) {
            analyticsPeriod.addEventListener("change", () =>
                this.loadAnalytics(),
            );
        }

        // Results events
        if (this.resultsContainer) {
            this.resultsContainer.addEventListener("click", (e) => {
                const resultItem = e.target.closest(".search-result-item");
                if (resultItem) {
                    this.handleResultClick(resultItem);
                }
            });
        }
    }

    handleInput(e) {
        const value = e.target.value.trim();

        // Show/hide clear button
        const clearBtn = this.container.querySelector(".search-clear");
        if (clearBtn) {
            clearBtn.style.display = value ? "block" : "none";
        }

        clearTimeout(this.debounceTimer);

        if (value.length >= this.options.minChars) {
            this.debounceTimer = setTimeout(() => {
                this.performSearch(value);
                this.fetchSuggestions(value);
            }, this.options.debounceTime);
        } else {
            this.hideSuggestions();
            this.clearResults();
        }
    }

    handleKeydown(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            const value = this.searchInput.value.trim();
            if (value) {
                this.performSearch(value);
                this.addToHistory(value);
            }
        }
    }

    async performSearch(query) {
        if (this.isSearching) return;

        this.isSearching = true;
        this.searchTerm = query;
        this.showSearchingState();

        try {
            const startTime = performance.now();

            const response = await fetch("/search", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    query: query,
                    filters: this.currentFilters,
                    per_page: 20,
                    page: 1,
                }),
            });

            if (response.ok) {
                const data = await response.json();
                this.searchResults = data.results || [];
                this.renderResults();
                this.updateStats(data, performance.now() - startTime);

                // Trigger callback
                if (this.options.onSearch) {
                    this.options.onSearch(data, query);
                }
            } else {
                this.showError("Search failed. Please try again.");
            }
        } catch (error) {
            console.error("Search error:", error);
            this.showError("Search failed. Please check your connection.");
        } finally {
            this.isSearching = false;
        }
    }

    async fetchSuggestions(query) {
        if (!this.options.enableAutocomplete || query.length < 2) return;

        try {
            const response = await fetch(
                `/search/suggestions?query=${encodeURIComponent(query)}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (response.ok) {
                const data = await response.json();
                this.suggestions = data.suggestions || [];
                this.renderSuggestions();
            }
        } catch (error) {
            console.error("Suggestions error:", error);
        }
    }

    renderSuggestions() {
        if (!this.suggestionsContainer || this.suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }

        const suggestionsHtml = this.suggestions
            .map(
                (suggestion) => `
            <div class="suggestion-item" data-suggestion="${suggestion.suggestion}">
                <span class="suggestion-icon">
                    <i class="fas fa-${this.getSuggestionIcon(suggestion.type)}"></i>
                </span>
                <span class="suggestion-text">${this.highlightMatch(suggestion.suggestion)}</span>
                ${
                    suggestion.type === "tag" && suggestion.metadata?.color
                        ? `<span class="suggestion-tag-color" style="background-color: ${suggestion.metadata.color}"></span>`
                        : ""
                }
                <span class="suggestion-type">${suggestion.type}</span>
            </div>
        `,
            )
            .join("");

        this.suggestionsContainer.innerHTML = suggestionsHtml;
        this.showSuggestions();
    }

    renderResults() {
        if (!this.resultsContainer) return;

        if (this.searchResults.length === 0) {
            this.resultsContainer.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No results found</h3>
                    <p>Try adjusting your search terms or filters.</p>
                    <div class="search-tips">
                        <h4>Search Tips:</h4>
                        <ul>
                            <li>Use specific keywords</li>
                            <li>Try different spellings</li>
                            <li>Use filters to narrow results</li>
                        </ul>
                    </div>
                </div>
            `;
            return;
        }

        const resultsHtml = this.searchResults
            .map((result, index) => {
                if (result.result_type === "article") {
                    return this.renderArticleResult(result, index);
                } else if (result.result_type === "note") {
                    return this.renderNoteResult(result, index);
                }
            })
            .join("");

        this.resultsContainer.innerHTML = `
            <div class="search-results-list">
                ${resultsHtml}
            </div>
        `;
    }

    renderArticleResult(article, index) {
        const excerpt = this.createExcerpt(article.content, this.searchTerm);
        const tagsHtml =
            article.tags
                ?.map(
                    (tag) => `
            <span class="result-tag" style="background-color: ${tag.color}">${tag.name}</span>
        `,
                )
                .join("") || "";

        return `
            <div class="search-result-item article-result" 
                 data-result-id="${article.id}" 
                 data-result-type="article"
                 data-position="${index + 1}">
                <div class="result-header">
                    <h3 class="result-title">${this.highlightMatch(article.title)}</h3>
                    <div class="result-meta">
                        <span class="result-type">Article</span>
                        <span class="result-date">${this.formatDate(article.created_at)}</span>
                        <span class="result-status status-${article.status}">${article.status}</span>
                    </div>
                </div>
                <div class="result-content">
                    <p class="result-excerpt">${this.highlightMatch(excerpt)}</p>
                    ${tagsHtml ? `<div class="result-tags">${tagsHtml}</div>` : ""}
                </div>
                <div class="result-stats">
                    <span class="stat">
                        <i class="fas fa-eye"></i> ${article.view_count || 0}
                    </span>
                    <span class="stat">
                        <i class="fas fa-sticky-note"></i> ${article.notes_count || 0}
                    </span>
                    <span class="stat">
                        <i class="fas fa-bookmark"></i> ${article.bookmarks_count || 0}
                    </span>
                </div>
            </div>
        `;
    }

    renderNoteResult(note, index) {
        const excerpt = this.createExcerpt(note.content, this.searchTerm);
        const articleTitle = note.article?.title || "Unknown Article";

        return `
            <div class="search-result-item note-result" 
                 data-result-id="${note.id}" 
                 data-result-type="note"
                 data-position="${index + 1}">
                <div class="result-header">
                    <h3 class="result-title">${this.highlightMatch(articleTitle)}</h3>
                    <div class="result-meta">
                        <span class="result-type">Note</span>
                        <span class="result-date">${this.formatDate(note.created_at)}</span>
                    </div>
                </div>
                <div class="result-content">
                    <p class="result-excerpt">${this.highlightMatch(excerpt)}</p>
                </div>
                <div class="result-actions">
                    <button class="view-note" data-note-id="${note.id}">View Note</button>
                </div>
            </div>
        `;
    }

    // Utility methods
    getSuggestionIcon(type) {
        const icons = {
            query: "search",
            tag: "tag",
            history: "history",
            content: "file-text",
        };
        return icons[type] || "search";
    }

    highlightMatch(text) {
        if (!this.searchTerm) return text;

        const regex = new RegExp(
            `(${this.escapeRegExp(this.searchTerm)})`,
            "gi",
        );
        return text.replace(regex, "<mark>$1</mark>");
    }

    createExcerpt(content, searchTerm, maxLength = 200) {
        if (!content) return "";

        const plainText = this.stripHtml(content);
        if (!searchTerm) {
            return plainText.length > maxLength
                ? plainText.substring(0, maxLength) + "..."
                : plainText;
        }

        const index = plainText.toLowerCase().indexOf(searchTerm.toLowerCase());
        if (index === -1) {
            return plainText.length > maxLength
                ? plainText.substring(0, maxLength) + "..."
                : plainText;
        }

        const start = Math.max(0, index - 50);
        const end = Math.min(plainText.length, index + searchTerm.length + 150);
        let excerpt = plainText.substring(start, end);

        if (start > 0) excerpt = "..." + excerpt;
        if (end < plainText.length) excerpt = excerpt + "...";

        return excerpt;
    }

    stripHtml(html) {
        const tmp = document.createElement("div");
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || "";
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return "Today";
        if (diffDays === 1) return "Yesterday";
        if (diffDays < 7) return `${diffDays} days ago`;
        if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;

        return date.toLocaleDateString();
    }

    escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    }

    getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }

    // Search history methods
    async loadSearchHistory() {
        if (!this.options.enableHistory) return;

        try {
            const response = await fetch("/search/history", {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.searchHistory = data.history || [];
                this.renderSearchHistory();
            }
        } catch (error) {
            console.error("Failed to load search history:", error);
        }
    }

    renderSearchHistory() {
        if (!this.historyContainer || this.searchHistory.length === 0) return;

        const historyList =
            this.historyContainer.querySelector(".history-list");
        if (!historyList) return;

        const historyHtml = this.searchHistory
            .slice(0, this.options.maxHistoryItems)
            .map(
                (item) => `
            <div class="history-item" data-query="${item.query}">
                <span class="history-query">${item.query}</span>
                <span class="history-date">${this.formatDate(item.created_at)}</span>
                <span class="history-results">${item.results_count} results</span>
            </div>
        `,
            )
            .join("");

        historyList.innerHTML = historyHtml;
    }

    selectFromHistory(query) {
        this.searchInput.value = query;
        this.performSearch(query);
        this.hideHistory();
    }

    addToHistory(query) {
        // Add to beginning of history
        this.searchHistory.unshift({
            query: query,
            created_at: new Date().toISOString(),
            results_count: this.searchResults.length,
        });

        // Remove duplicates and limit size
        this.searchHistory = this.searchHistory
            .filter(
                (item, index, arr) =>
                    arr.findIndex((i) => i.query === item.query) === index,
            )
            .slice(0, this.options.maxHistoryItems);

        this.renderSearchHistory();
    }

    clearHistory() {
        this.searchHistory = [];
        this.renderSearchHistory();
        // TODO: Add API call to clear server-side history
    }

    // Filter methods
    applyFilters() {
        this.currentFilters = {
            status:
                this.container.querySelector(".filter-status")?.value || null,
            date_from:
                this.container.querySelector(".filter-date-from")?.value ||
                null,
            date_to:
                this.container.querySelector(".filter-date-to")?.value || null,
            has_notes:
                this.container.querySelector(".filter-has-notes")?.value ||
                null,
            has_bookmarks:
                this.container.querySelector(".filter-has-bookmarks")?.value ||
                null,
            sort_by:
                this.container.querySelector(".filter-sort-by")?.value ||
                "relevance",
            sort_order:
                this.container.querySelector(".filter-sort-order")?.value ||
                "desc",
        };

        // Remove null values
        this.currentFilters = Object.fromEntries(
            Object.entries(this.currentFilters).filter(
                ([_, v]) => v !== null && v !== "",
            ),
        );

        if (this.searchTerm) {
            this.performSearch(this.searchTerm);
        }

        this.hideFilters();
    }

    cancelFilters() {
        this.resetFilterInputs();
        this.hideFilters();
    }

    resetFilters() {
        this.currentFilters = {};
        this.resetFilterInputs();

        if (this.searchTerm) {
            this.performSearch(this.searchTerm);
        }
    }

    resetFilterInputs() {
        const filterInputs = this.container.querySelectorAll(
            ".filter-group input, .filter-group select",
        );
        filterInputs.forEach((input) => {
            if (input.type === "date") {
                input.value = "";
            } else if (input.tagName === "SELECT") {
                input.selectedIndex = 0;
            }
        });
    }

    // Analytics methods
    async loadAnalytics() {
        if (!this.options.enableAnalytics) return;

        const period =
            this.container.querySelector(".analytics-period")?.value || 30;

        try {
            const response = await fetch(`/search/analytics?days=${period}`, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.renderAnalytics(data.analytics);
            }
        } catch (error) {
            console.error("Failed to load analytics:", error);
        }
    }

    renderAnalytics(analytics) {
        if (!this.analyticsContainer) return;

        const content =
            this.analyticsContainer.querySelector(".analytics-content");
        if (!content) return;

        const analyticsHtml = `
            <div class="analytics-summary">
                <div class="stat-card">
                    <h5>Total Searches</h5>
                    <span class="stat-value">${analytics.total_searches || 0}</span>
                </div>
                <div class="stat-card">
                    <h5>Avg. Results</h5>
                    <span class="stat-value">${Math.round(analytics.average_results || 0)}</span>
                </div>
                <div class="stat-card">
                    <h5>Click Rate</h5>
                    <span class="stat-value">${Math.round(analytics.click_through_rate || 0)}%</span>
                </div>
            </div>
            
            ${
                analytics.top_queries?.length
                    ? `
                <div class="top-queries">
                    <h5>Top Queries</h5>
                    <div class="queries-list">
                        ${analytics.top_queries
                            .map(
                                (query) => `
                            <div class="query-item">
                                <span class="query-text">${query.query}</span>
                                <span class="query-count">${query.count}x</span>
                            </div>
                        `,
                            )
                            .join("")}
                    </div>
                </div>
            `
                    : ""
            }
            
            ${
                analytics.search_trends?.length
                    ? `
                <div class="search-trends">
                    <h5>Search Trends</h5>
                    <div class="trends-chart">
                        ${this.renderTrendsChart(analytics.search_trends)}
                    </div>
                </div>
            `
                    : ""
            }
        `;

        content.innerHTML = analyticsHtml;
    }

    renderTrendsChart(trends) {
        // Simple bar chart implementation
        const maxCount = Math.max(...trends.map((t) => t.count));
        const chartHtml = trends
            .map((trend) => {
                const percentage = (trend.count / maxCount) * 100;
                return `
                <div class="trend-bar" style="height: ${percentage}%" title="${trend.date}: ${trend.count} searches">
                    <span class="trend-date">${new Date(trend.date).toLocaleDateString()}</span>
                    <span class="trend-count">${trend.count}</span>
                </div>
            `;
            })
            .join("");

        return `<div class="trends-chart-container">${chartHtml}</div>`;
    }

    // Popular searches
    async loadPopularSearches() {
        // TODO: Implement popular searches loading
    }

    // UI helper methods
    showSearchingState() {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = `
                <div class="search-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Searching...</p>
                </div>
            `;
        }

        if (this.searchStats) {
            this.searchStats.style.display = "block";
            this.searchStats.innerHTML =
                '<span class="searching-text">Searching...</span>';
        }
    }

    updateStats(data, searchTime) {
        if (!this.searchStats) return;

        this.searchStats.style.display = "block";
        this.searchStats.innerHTML = `
            <span class="results-count">${data.total_count} results</span>
            <span class="search-time">${Math.round(searchTime)}ms</span>
        `;
    }

    showError(message) {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = `
                <div class="search-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    }

    clearResults() {
        if (this.resultsContainer) {
            this.resultsContainer.innerHTML = "";
        }

        if (this.searchStats) {
            this.searchStats.style.display = "none";
        }
    }

    clearSearch() {
        this.searchInput.value = "";
        this.searchTerm = "";
        this.searchResults = [];
        this.clearResults();
        this.hideSuggestions();

        const clearBtn = this.container.querySelector(".search-clear");
        if (clearBtn) {
            clearBtn.style.display = "none";
        }
    }

    // Toggle methods
    toggleHistory() {
        const isVisible = this.historyContainer.style.display !== "none";
        this.historyContainer.style.display = isVisible ? "none" : "block";

        if (!isVisible) {
            this.hideFilters();
            this.hideAnalytics();
        }
    }

    toggleFilters() {
        const isVisible = this.filtersContainer.style.display !== "none";
        this.filtersContainer.style.display = isVisible ? "none" : "block";

        if (!isVisible) {
            this.hideHistory();
            this.hideAnalytics();
        }
    }

    toggleAnalytics() {
        const isVisible = this.analyticsContainer.style.display !== "none";
        this.analyticsContainer.style.display = isVisible ? "none" : "block";

        if (!isVisible) {
            this.loadAnalytics();
            this.hideHistory();
            this.hideFilters();
        }
    }

    hideHistory() {
        if (this.historyContainer) {
            this.historyContainer.style.display = "none";
        }
    }

    hideFilters() {
        if (this.filtersContainer) {
            this.filtersContainer.style.display = "none";
        }
    }

    hideAnalytics() {
        if (this.analyticsContainer) {
            this.analyticsContainer.style.display = "none";
        }
    }

    showSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.style.display = "block";
        }
    }

    hideSuggestions() {
        if (this.suggestionsContainer) {
            this.suggestionsContainer.style.display = "none";
        }
    }

    selectSuggestion(suggestion) {
        this.searchInput.value = suggestion;
        this.performSearch(suggestion);
        this.addToHistory(suggestion);
        this.hideSuggestions();
    }

    handleResultClick(resultItem) {
        const resultId = resultItem.dataset.resultId;
        const resultType = resultItem.dataset.resultType;
        const position = resultItem.dataset.position;

        // Record click for analytics
        if (this.options.enableAnalytics) {
            this.recordClick(this.searchTerm, resultId, resultType, position);
        }

        // Trigger callback
        if (this.options.onResultClick) {
            this.options.onResultClick(resultItem, this.searchTerm);
        }
    }

    async recordClick(query, resultId, resultType, position) {
        try {
            await fetch("/search/click", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    query: query,
                    result_id: parseInt(resultId),
                    result_type: resultType,
                    position: parseInt(position),
                }),
            });
        } catch (error) {
            console.error("Failed to record click:", error);
        }
    }

    // Public API methods
    getSearchTerm() {
        return this.searchTerm;
    }

    getResults() {
        return this.searchResults;
    }

    setFilters(filters) {
        this.currentFilters = filters;
        if (this.searchTerm) {
            this.performSearch(this.searchTerm);
        }
    }

    focus() {
        if (this.searchInput) {
            this.searchInput.focus();
        }
    }

    destroy() {
        // Cleanup event listeners
        if (this.searchInput) {
            this.searchInput.removeEventListener("input", this.handleInput);
            this.searchInput.removeEventListener("keydown", this.handleKeydown);
        }

        // Remove generated elements
        const generatedElements = this.container.querySelectorAll(
            ".advanced-search-container",
        );
        generatedElements.forEach((el) => el.remove());
    }
}

// CSS styles for advanced search
const advancedSearchStyles = `
.advanced-search-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.search-header {
    display: flex;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    gap: 12px;
}

.search-input-container {
    flex: 1;
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-clear {
    position: absolute;
    right: 50px;
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: color 0.2s ease;
}

.search-clear:hover {
    color: #374151;
}

.search-filters-toggle {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    padding: 8px;
    border-radius: 4px;
    transition: color 0.2s ease;
}

.search-filters-toggle:hover {
    color: #374151;
}

.search-actions {
    display: flex;
    gap: 8px;
}

.search-actions button {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 12px;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-actions button:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
}

.search-suggestions {
    position: absolute;
    top: 100%;
    left: 20px;
    right: 20px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.suggestion-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    cursor: pointer;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid #f1f5f9;
}

.suggestion-item:last-child {
    border-bottom: none;
}

.suggestion-item:hover {
    background-color: #f8fafc;
}

.suggestion-icon {
    margin-right: 12px;
    color: #64748b;
    width: 16px;
    text-align: center;
}

.suggestion-text {
    flex: 1;
    font-size: 14px;
    color: #334155;
}

.suggestion-tag-color {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-left: 8px;
}

.suggestion-type {
    font-size: 12px;
    color: #64748b;
    text-transform: capitalize;
    margin-left: 8px;
}

.search-history,
.search-filters,
.search-analytics {
    border-top: 1px solid #e2e8f0;
    padding: 20px;
    display: none;
}

.history-header,
.filters-header,
.analytics-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.history-header h4,
.filters-header h4,
.analytics-header h4 {
    margin: 0;
    color: #334155;
    font-size: 16px;
    font-weight: 600;
}

.clear-history,
.reset-filters {
    background: none;
    border: none;
    color: #64748b;
    cursor: pointer;
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: color 0.2s ease;
}

.clear-history:hover,
.reset-filters:hover {
    color: #374151;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.history-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.history-item:hover {
    background: #f1f5f9;
}

.history-query {
    font-size: 14px;
    color: #334155;
    flex: 1;
}

.history-date,
.history-results {
    font-size: 12px;
    color: #64748b;
    margin-left: 8px;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    font-size: 14px;
    color: #475569;
    margin-bottom: 4px;
    font-weight: 500;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    background: white;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.date-range {
    display: flex;
    gap: 8px;
}

.date-range input {
    flex: 1;
}

.filter-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.apply-filters {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.apply-filters:hover {
    background: #2563eb;
}

.cancel-filters {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 8px 16px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.cancel-filters:hover {
    background: #e2e8f0;
}

.analytics-period {
    padding: 4px 8px;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 12px;
    background: white;
}

.search-stats {
    padding: 12px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    font-size: 12px;
    color: #64748b;
    display: flex;
    justify-content: space-between;
}

.search-results {
    padding: 20px;
}

.search-result-item {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.search-result-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.result-title {
    margin: 0;
    font-size: 16px;
    color: #334155;
    font-weight: 600;
}

.result-title mark {
    background: #fef3c7;
    color: inherit;
    padding: 0 2px;
    border-radius: 2px;
}

.result-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
}

.result-type {
    background: #e2e8f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
    text-transform: uppercase;
}

.result-content {
    margin-bottom: 12px;
}

.result-excerpt {
    margin: 0;
    font-size: 14px;
    color: #475569;
    line-height: 1.5;
}

.result-excerpt mark {
    background: #fef3c7;
    color: inherit;
    padding: 0 2px;
    border-radius: 2px;
}

.result-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 8px;
}

.result-tag {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 12px;
    color: white;
    font-weight: 500;
}

.result-stats {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 12px;
    color: #64748b;
}

.stat {
    display: flex;
    align-items: center;
    gap: 4px;
}

.no-results {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
}

.no-results i {
    font-size: 48px;
    margin-bottom: 16px;
    color: #cbd5e1;
}

.no-results h3 {
    margin: 0 0 8px 0;
    color: #475569;
}

.search-tips {
    text-align: left;
    max-width: 400px;
    margin: 20px auto 0;
}

.search-tips h4 {
    margin: 0 0 12px 0;
    color: #334155;
}

.search-tips ul {
    margin: 0;
    padding-left: 20px;
}

.search-tips li {
    margin-bottom: 4px;
    color: #64748b;
}

.search-loading {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
}

.search-loading i {
    font-size: 32px;
    margin-bottom: 16px;
    color: #3b82f6;
}

.search-error {
    text-align: center;
    padding: 40px 20px;
    color: #dc2626;
}

.search-error i {
    font-size: 32px;
    margin-bottom: 16px;
}

/* Analytics styles */
.analytics-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
    text-align: center;
}

.stat-card h5 {
    margin: 0 0 8px 0;
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #334155;
}

.top-queries {
    margin-bottom: 24px;
}

.queries-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.query-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 6px;
}

.query-text {
    font-size: 14px;
    color: #334155;
}

.query-count {
    font-size: 12px;
    color: #64748b;
    background: #e2e8f0;
    padding: 2px 6px;
    border-radius: 10px;
}

.trends-chart-container {
    display: flex;
    align-items: end;
    gap: 4px;
    height: 120px;
    padding: 16px 0;
}

.trend-bar {
    flex: 1;
    background: #3b82f6;
    border-radius: 2px 2px 0 0;
    position: relative;
    min-height: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    padding: 4px 2px;
}

.trend-date {
    font-size: 10px;
    color: #64748b;
    transform: rotate(-45deg);
    white-space: nowrap;
}

.trend-count {
    font-size: 10px;
    color: white;
    font-weight: 500;
}

/* Responsive design */
@media (max-width: 640px) {
    .search-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .date-range {
        flex-direction: column;
    }
    
    .result-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .result-meta {
        flex-wrap: wrap;
    }
    
    .result-stats {
        flex-wrap: wrap;
    }
}
`;

// Inject styles if not already present
if (!document.querySelector("#advanced-search-styles")) {
    const styleSheet = document.createElement("style");
    styleSheet.id = "advanced-search-styles";
    styleSheet.textContent = advancedSearchStyles;
    document.head.appendChild(styleSheet);
}

// Export for use in other modules
window.AdvancedSearch = AdvancedSearch;
