/**
 * SearchService - Frontend service for search operations
 * Handles search requests, caching, and result management
 */
class SearchService {
    constructor() {
        this.baseUrl = "/search";
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
        this.searchHistory = [];
        this.maxHistoryItems = 20;
    }

    /**
     * Perform search with caching
     */
    async search(query, filters = {}, options = {}) {
        const cacheKey = this.generateCacheKey(query, filters, options);
        const cached = this.getFromCache(cacheKey);

        if (cached) {
            return cached;
        }

        try {
            const response = await fetch(this.baseUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    query: query,
                    filters: filters,
                    per_page: options.perPage || 20,
                    page: options.page || 1,
                    type: options.type || "all",
                }),
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            this.setCache(cacheKey, data);
            this.addToHistory(query, data);

            return data;
        } catch (error) {
            console.error("Search failed:", error);
            throw error;
        }
    }

    /**
     * Quick search for instant results
     */
    async quickSearch(query, limit = 5) {
        const cacheKey = `quick_${query}_${limit}`;
        const cached = this.getFromCache(cacheKey);

        if (cached) {
            return cached;
        }

        try {
            const response = await fetch(
                `${this.baseUrl}/quick?query=${encodeURIComponent(query)}&limit=${limit}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            this.setCache(cacheKey, data, 60000); // 1 minute cache for quick search

            return data;
        } catch (error) {
            console.error("Quick search failed:", error);
            throw error;
        }
    }

    /**
     * Advanced search with multiple criteria
     */
    async advancedSearch(criteria, options = {}) {
        try {
            const response = await fetch(`${this.baseUrl}/advanced`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    ...criteria,
                    per_page: options.perPage || 20,
                    page: options.page || 1,
                }),
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Advanced search failed:", error);
            throw error;
        }
    }

    /**
     * Get search suggestions
     */
    async getSuggestions(query, limit = 10) {
        if (query.length < 2) {
            return { suggestions: [] };
        }

        const cacheKey = `suggestions_${query}_${limit}`;
        const cached = this.getFromCache(cacheKey);

        if (cached) {
            return cached;
        }

        try {
            const response = await fetch(
                `${this.baseUrl}/suggestions?query=${encodeURIComponent(query)}&limit=${limit}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            this.setCache(cacheKey, data, 30000); // 30 seconds cache for suggestions

            return data;
        } catch (error) {
            console.error("Failed to get suggestions:", error);
            return { suggestions: [] };
        }
    }

    /**
     * Get search history
     */
    async getSearchHistory(limit = 20) {
        try {
            const response = await fetch(
                `${this.baseUrl}/history?limit=${limit}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            this.searchHistory = data.history || [];

            return data;
        } catch (error) {
            console.error("Failed to get search history:", error);
            return { history: [] };
        }
    }

    /**
     * Get search analytics
     */
    async getAnalytics(days = 30) {
        try {
            const response = await fetch(
                `${this.baseUrl}/analytics?days=${days}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Failed to get analytics:", error);
            return { analytics: {} };
        }
    }

    /**
     * Record search result click
     */
    async recordClick(query, resultId, resultType, position) {
        try {
            await fetch(`${this.baseUrl}/click`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    query: query,
                    result_id: resultId,
                    result_type: resultType,
                    position: position,
                }),
            });
        } catch (error) {
            console.error("Failed to record click:", error);
        }
    }

    /**
     * Update search index for specific article
     */
    async updateArticleIndex(articleId) {
        try {
            // This would typically be done server-side, but we can trigger it
            await fetch(`/api/articles/${articleId}/update-index`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });
        } catch (error) {
            console.error("Failed to update article index:", error);
        }
    }

    /**
     * Batch update search indices
     */
    async batchUpdateIndex(type = "articles") {
        try {
            await fetch(`/api/search/batch-update-index`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({ type: type }),
            });
        } catch (error) {
            console.error("Failed to batch update index:", error);
        }
    }

    /**
     * Search with retry mechanism
     */
    async searchWithRetry(query, filters = {}, options = {}, maxRetries = 3) {
        let lastError;

        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                return await this.search(query, filters, options);
            } catch (error) {
                lastError = error;
                console.warn(
                    `Search attempt ${attempt} failed:`,
                    error.message,
                );

                if (attempt < maxRetries) {
                    // Exponential backoff
                    await this.delay(Math.pow(2, attempt) * 1000);
                }
            }
        }

        throw lastError;
    }

    /**
     * Get search suggestions with debouncing
     */
    getSuggestionsDebounced(query, limit = 10, delay = 300) {
        return new Promise((resolve) => {
            clearTimeout(this.suggestionTimeout);
            this.suggestionTimeout = setTimeout(async () => {
                try {
                    const suggestions = await this.getSuggestions(query, limit);
                    resolve(suggestions);
                } catch (error) {
                    resolve({ suggestions: [] });
                }
            }, delay);
        });
    }

    /**
     * Search within specific date range
     */
    async searchByDateRange(query, dateFrom, dateTo, options = {}) {
        const filters = {
            date_from: dateFrom,
            date_to: dateTo,
            ...options.filters,
        };

        return await this.search(query, filters, options);
    }

    /**
     * Search by tags
     */
    async searchByTags(query, tags, options = {}) {
        const filters = {
            tags: Array.isArray(tags) ? tags : [tags],
            ...options.filters,
        };

        return await this.search(query, filters, options);
    }

    /**
     * Search with specific status
     */
    async searchByStatus(query, status, options = {}) {
        const filters = {
            status: status,
            ...options.filters,
        };

        return await this.search(query, filters, options);
    }

    /**
     * Get related content based on search results
     */
    async getRelatedContent(contentId, contentType = "article", limit = 5) {
        try {
            const response = await fetch(
                `/api/related-content/${contentType}/${contentId}?limit=${limit}`,
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data.related || [];
        } catch (error) {
            console.error("Failed to get related content:", error);
            return [];
        }
    }

    /**
     * Export search results
     */
    async exportResults(query, filters = {}, format = "json") {
        try {
            const response = await fetch("/api/search/export", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    query: query,
                    filters: filters,
                    format: format,
                }),
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Failed to export results:", error);
            throw error;
        }
    }

    // Cache management methods
    generateCacheKey(query, filters, options) {
        const filterString = JSON.stringify(filters);
        const optionString = JSON.stringify(options);
        return `search_${query}_${filterString}_${optionString}`;
    }

    getFromCache(key) {
        const item = this.cache.get(key);
        if (item && Date.now() - item.timestamp < this.cacheTimeout) {
            return item.data;
        }
        this.cache.delete(key);
        return null;
    }

    setCache(key, data, timeout = this.cacheTimeout) {
        this.cache.set(key, {
            data: data,
            timestamp: Date.now(),
        });

        // Set cleanup timeout
        setTimeout(() => {
            this.cache.delete(key);
        }, timeout);
    }

    clearCache() {
        this.cache.clear();
    }

    // History management
    addToHistory(query, results) {
        const historyItem = {
            query: query,
            timestamp: Date.now(),
            results_count: results.total_count || 0,
            filters: results.filters || {},
        };

        // Remove duplicate queries
        this.searchHistory = this.searchHistory.filter(
            (item) => item.query !== query,
        );

        // Add to beginning
        this.searchHistory.unshift(historyItem);

        // Limit history size
        if (this.searchHistory.length > this.maxHistoryItems) {
            this.searchHistory = this.searchHistory.slice(
                0,
                this.maxHistoryItems,
            );
        }

        // Store in localStorage
        this.saveHistoryToLocalStorage();
    }

    saveHistoryToLocalStorage() {
        try {
            localStorage.setItem(
                "search_history",
                JSON.stringify(this.searchHistory),
            );
        } catch (error) {
            console.warn(
                "Failed to save search history to localStorage:",
                error,
            );
        }
    }

    loadHistoryFromLocalStorage() {
        try {
            const stored = localStorage.getItem("search_history");
            if (stored) {
                this.searchHistory = JSON.parse(stored);
            }
        } catch (error) {
            console.warn(
                "Failed to load search history from localStorage:",
                error,
            );
        }
    }

    clearLocalHistory() {
        this.searchHistory = [];
        this.saveHistoryToLocalStorage();
    }

    getRecentQueries(limit = 10) {
        return this.searchHistory.slice(0, limit).map((item) => item.query);
    }

    // Utility methods
    getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }

    delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    // Statistics and analytics
    getSearchStats() {
        const stats = {
            total_searches: this.searchHistory.length,
            unique_queries: new Set(
                this.searchHistory.map((item) => item.query),
            ).size,
            avg_results:
                this.searchHistory.reduce(
                    (sum, item) => sum + item.results_count,
                    0,
                ) / this.searchHistory.length,
            recent_queries: this.getRecentQueries(5),
        };

        return stats;
    }

    // Initialize service
    init() {
        this.loadHistoryFromLocalStorage();

        // Set up periodic cache cleanup
        setInterval(
            () => {
                this.clearCache();
            },
            30 * 60 * 1000,
        ); // Clean cache every 30 minutes
    }
}

// Create global instance
window.SearchService = new SearchService();

// Auto-initialize
window.SearchService.init();
