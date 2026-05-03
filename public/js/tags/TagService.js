/**
 * TagService - Service for tag-related API operations
 * Handles CRUD operations, bulk operations, and tag suggestions
 */
class TagService {
    constructor() {
        this.baseUrl = "/tags";
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 minutes
    }

    /**
     * Get all tags for the current user
     */
    async getTags(params = {}) {
        const cacheKey = `tags_${JSON.stringify(params)}`;
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const queryString = new URLSearchParams(params).toString();
            const response = await fetch(`${this.baseUrl}?${queryString}`, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            this.setCache(cacheKey, data);
            return data;
        } catch (error) {
            console.error("Failed to fetch tags:", error);
            throw error;
        }
    }

    /**
     * Get autocomplete suggestions
     */
    async getAutocompleteSuggestions(query, limit = 10) {
        if (!query || query.length < 1) return [];

        const cacheKey = `autocomplete_${query}_${limit}`;
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await fetch(
                `${this.baseUrl}/autocomplete?query=${encodeURIComponent(query)}&limit=${limit}`,
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
            this.setCache(cacheKey, data.tags || []);
            return data.tags || [];
        } catch (error) {
            console.error("Failed to fetch autocomplete suggestions:", error);
            return [];
        }
    }

    /**
     * Get tag suggestions based on content
     */
    async getTagSuggestions(content, articleId = null) {
        try {
            const body = { content };
            if (articleId) body.article_id = articleId;

            const response = await fetch(`${this.baseUrl}/suggest`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data.suggestions || [];
        } catch (error) {
            console.error("Failed to fetch tag suggestions:", error);
            return [];
        }
    }

    /**
     * Create a new tag
     */
    async createTag(tagData) {
        try {
            const response = await fetch(this.baseUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify(tagData),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Failed to create tag");
            }

            const data = await response.json();
            this.clearCache(); // Clear cache after creation
            return data.tag;
        } catch (error) {
            console.error("Failed to create tag:", error);
            throw error;
        }
    }

    /**
     * Update an existing tag
     */
    async updateTag(tagId, tagData) {
        try {
            const response = await fetch(`${this.baseUrl}/${tagId}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify(tagData),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Failed to update tag");
            }

            const data = await response.json();
            this.clearCache(); // Clear cache after update
            return data.tag;
        } catch (error) {
            console.error("Failed to update tag:", error);
            throw error;
        }
    }

    /**
     * Delete a tag
     */
    async deleteTag(tagId) {
        try {
            const response = await fetch(`${this.baseUrl}/${tagId}`, {
                method: "DELETE",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Failed to delete tag");
            }

            this.clearCache(); // Clear cache after deletion
            return true;
        } catch (error) {
            console.error("Failed to delete tag:", error);
            throw error;
        }
    }

    /**
     * Get a specific tag by ID
     */
    async getTag(tagId) {
        const cacheKey = `tag_${tagId}`;
        const cached = this.getFromCache(cacheKey);
        if (cached) return cached;

        try {
            const response = await fetch(`${this.baseUrl}/${tagId}`, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            this.setCache(cacheKey, data.tag);
            return data.tag;
        } catch (error) {
            console.error("Failed to fetch tag:", error);
            throw error;
        }
    }

    /**
     * Bulk tag articles
     */
    async bulkTagArticles(tagId, articleIds) {
        try {
            const response = await fetch(`${this.baseUrl}/bulk-tag`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
                body: JSON.stringify({
                    tag_id: tagId,
                    article_ids: articleIds,
                }),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || "Failed to bulk tag articles");
            }

            const data = await response.json();
            this.clearCache(); // Clear cache after bulk operation
            return data;
        } catch (error) {
            console.error("Failed to bulk tag articles:", error);
            throw error;
        }
    }

    /**
     * Remove tag from articles
     */
    async removeTagFromArticles(tagId, articleIds) {
        try {
            const response = await fetch(
                `${this.baseUrl}/${tagId}/remove-from-articles`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                    body: JSON.stringify({
                        article_ids: articleIds,
                    }),
                },
            );

            if (!response.ok) {
                const error = await response.json();
                throw new Error(
                    error.message || "Failed to remove tag from articles",
                );
            }

            this.clearCache(); // Clear cache after operation
            return true;
        } catch (error) {
            console.error("Failed to remove tag from articles:", error);
            throw error;
        }
    }

    /**
     * Get tag statistics
     */
    async getTagStatistics() {
        try {
            const response = await fetch(`${this.baseUrl}?statistics=true`, {
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`,
                );
            }

            const data = await response.json();
            return data.statistics || {};
        } catch (error) {
            console.error("Failed to fetch tag statistics:", error);
            return {};
        }
    }

    /**
     * Cache management methods
     */
    getFromCache(key) {
        const item = this.cache.get(key);
        if (item && Date.now() - item.timestamp < this.cacheTimeout) {
            return item.data;
        }
        this.cache.delete(key);
        return null;
    }

    setCache(key, data) {
        this.cache.set(key, {
            data: data,
            timestamp: Date.now(),
        });
    }

    clearCache() {
        this.cache.clear();
    }

    /**
     * Utility methods
     */
    getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }

    /**
     * Batch operations for performance
     */
    async batchCreateTags(tagsData) {
        const results = [];
        const errors = [];

        for (const tagData of tagsData) {
            try {
                const tag = await this.createTag(tagData);
                results.push(tag);
            } catch (error) {
                errors.push({ tag: tagData, error: error.message });
            }
        }

        return { results, errors };
    }

    /**
     * Search tags with advanced filtering
     */
    async searchTags(searchParams) {
        try {
            const queryString = new URLSearchParams(searchParams).toString();
            const response = await fetch(
                `${this.baseUrl}/search?${queryString}`,
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
            return data.tags || [];
        } catch (error) {
            console.error("Failed to search tags:", error);
            return [];
        }
    }
}

// Export for use in other modules
window.TagService = TagService;
