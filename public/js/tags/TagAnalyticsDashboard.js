/**
 * TagAnalyticsDashboard - Comprehensive tag analytics and statistics
 * Provides detailed insights into tag usage, trends, and performance metrics
 */
class TagAnalyticsDashboard {
    constructor(options = {}) {
        this.options = {
            container: null,
            refreshInterval: 300000, // 5 minutes
            showCharts: true,
            showTrends: true,
            showRecommendations: true,
            ...options,
        };

        this.analyticsData = {};
        this.charts = {};
        this.refreshTimer = null;
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
        this.loadAnalytics();
        this.startAutoRefresh();
    }

    setupElements() {
        this.container.innerHTML = this.getDashboardTemplate();

        this.dashboardContainer = this.container.querySelector(
            ".tag-analytics-dashboard",
        );
        this.refreshBtn = this.container.querySelector(".refresh-analytics");
        this.dateRangeSelect =
            this.container.querySelector(".date-range-select");
        this.exportBtn = this.container.querySelector(".export-analytics");

        // Chart containers
        this.usageChartContainer = this.container.querySelector("#usage-chart");
        this.trendChartContainer = this.container.querySelector("#trend-chart");
        this.categoryChartContainer =
            this.container.querySelector("#category-chart");

        // Stats containers
        this.overviewStats = this.container.querySelector(".overview-stats");
        this.topTagsContainer = this.container.querySelector(".top-tags-list");
        this.recommendationsContainer = this.container.querySelector(
            ".recommendations-list",
        );

        // Loading states
        this.loadingStates = this.container.querySelectorAll(".loading-state");
        this.errorStates = this.container.querySelectorAll(".error-state");
    }

    getDashboardTemplate() {
        return `
            <div class="tag-analytics-dashboard">
                <div class="dashboard-header">
                    <h2>Tag Analytics Dashboard</h2>
                    <div class="dashboard-controls">
                        <select class="date-range-select">
                            <option value="7">Last 7 days</option>
                            <option value="30" selected>Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                        <button type="button" class="refresh-analytics btn-secondary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button type="button" class="export-analytics btn-primary">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                
                <div class="analytics-overview">
                    <h3>Overview</h3>
                    <div class="overview-stats">
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                            <p>Loading analytics...</p>
                        </div>
                    </div>
                </div>
                
                <div class="charts-section">
                    <div class="chart-container">
                        <h3>Tag Usage Distribution</h3>
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                        </div>
                        <canvas id="usage-chart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3>Tag Trends Over Time</h3>
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                        </div>
                        <canvas id="trend-chart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h3>Tag Categories</h3>
                        <div class="loading-state">
                            <div class="loading-spinner"></div>
                        </div>
                        <canvas id="category-chart"></canvas>
                    </div>
                </div>
                
                <div class="insights-section">
                    <div class="top-tags-section">
                        <h3>Top Performing Tags</h3>
                        <div class="top-tags-list">
                            <div class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Loading top tags...</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recommendations-section">
                        <h3>Recommendations</h3>
                        <div class="recommendations-list">
                            <div class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Loading recommendations...</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="detailed-analytics">
                    <h3>Detailed Analytics</h3>
                    <div class="analytics-grid">
                        <div class="analytics-card">
                            <h4>Tag Growth</h4>
                            <div class="metric">
                                <span class="metric-value" id="tag-growth">-</span>
                                <span class="metric-label">% this month</span>
                            </div>
                        </div>
                        
                        <div class="analytics-card">
                            <h4>Usage Efficiency</h4>
                            <div class="metric">
                                <span class="metric-value" id="usage-efficiency">-</span>
                                <span class="metric-label">avg usage per tag</span>
                            </div>
                        </div>
                        
                        <div class="analytics-card">
                            <h4>Content Coverage</h4>
                            <div class="metric">
                                <span class="metric-value" id="content-coverage">-</span>
                                <span class="metric-label">% articles tagged</span>
                            </div>
                        </div>
                        
                        <div class="analytics-card">
                            <h4>Tag Diversity</h4>
                            <div class="metric">
                                <span class="metric-value" id="tag-diversity">-</span>
                                <span class="metric-label">unique tags per article</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    bindEvents() {
        this.refreshBtn.addEventListener("click", () =>
            this.refreshAnalytics(),
        );
        this.dateRangeSelect.addEventListener("change", (e) =>
            this.handleDateRangeChange(e.target.value),
        );
        this.exportBtn.addEventListener("click", () => this.exportAnalytics());
    }

    async loadAnalytics() {
        this.showLoading();

        try {
            const [tagsData, articlesData] = await Promise.all([
                this.tagService.getTags({
                    per_page: 100,
                    sort_by: "usage_count",
                    sort_order: "desc",
                }),
                this.loadArticlesData(),
            ]);

            this.processAnalyticsData(tagsData, articlesData);
            this.renderAnalytics();
            this.hideLoading();
        } catch (error) {
            console.error("Failed to load analytics:", error);
            this.showError("Failed to load analytics data");
            this.hideLoading();
        }
    }

    async loadArticlesData() {
        // Load articles with tags for analysis
        try {
            const response = await fetch(
                "/articles?per_page=100&with_tags=true",
                {
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.getCsrfToken(),
                    },
                },
            );

            if (response.ok) {
                const data = await response.json();
                return data.articles?.data || [];
            }
            return [];
        } catch (error) {
            console.error("Failed to load articles:", error);
            return [];
        }
    }

    processAnalyticsData(tagsData, articlesData) {
        const tags = tagsData.tags?.data || [];
        const articles = articlesData || [];

        this.analyticsData = {
            tags: tags,
            articles: articles,
            totalTags: tags.length,
            totalUsage: tags.reduce(
                (sum, tag) => sum + (tag.usage_count || 0),
                0,
            ),
            articlesWithTags: articles.filter(
                (article) => article.tags && article.tags.length > 0,
            ).length,
            averageUsage:
                tags.length > 0
                    ? Math.round(
                          tags.reduce(
                              (sum, tag) => sum + (tag.usage_count || 0),
                              0,
                          ) / tags.length,
                      )
                    : 0,
            topTags: tags.slice(0, 10),
            tagTypes: this.analyzeTagTypes(tags),
            usageDistribution: this.analyzeUsageDistribution(tags),
            trends: this.analyzeTrends(tags),
            recommendations: this.generateRecommendations(tags, articles),
        };
    }

    analyzeTagTypes(tags) {
        const types = { custom: 0, auto: 0, system: 0 };
        tags.forEach((tag) => {
            if (tag.type === "auto" || tag.is_auto_generated) {
                types.auto++;
            } else if (!tag.user_id) {
                types.system++;
            } else {
                types.custom++;
            }
        });
        return types;
    }

    analyzeUsageDistribution(tags) {
        const distribution = {
            high: tags.filter((tag) => tag.usage_count >= 10).length,
            medium: tags.filter(
                (tag) => tag.usage_count >= 3 && tag.usage_count < 10,
            ).length,
            low: tags.filter((tag) => tag.usage_count < 3).length,
            unused: tags.filter(
                (tag) => !tag.usage_count || tag.usage_count === 0,
            ).length,
        };
        return distribution;
    }

    analyzeTrends(tags) {
        // Simple trend analysis based on creation dates
        const now = new Date();
        const thirtyDaysAgo = new Date(
            now.getTime() - 30 * 24 * 60 * 60 * 1000,
        );

        const recentTags = tags.filter((tag) => {
            const createdAt = new Date(tag.created_at);
            return createdAt >= thirtyDaysAgo;
        });

        return {
            recentCount: recentTags.length,
            growthRate:
                tags.length > 0
                    ? Math.round((recentTags.length / tags.length) * 100)
                    : 0,
        };
    }

    generateRecommendations(tags, articles) {
        const recommendations = [];

        // Recommendation 1: Underutilized tags
        const underutilized = tags.filter(
            (tag) => tag.usage_count === 0 || tag.usage_count === 1,
        );
        if (underutilized.length > 0) {
            recommendations.push({
                type: "warning",
                title: "Underutilized Tags",
                message: `${underutilized.length} tags are barely used. Consider applying them to relevant content or removing unused ones.`,
                action: "Review underutilized tags",
                data: underutilized,
            });
        }

        // Recommendation 2: Popular tags
        const popularTags = tags.filter((tag) => tag.usage_count >= 10);
        if (popularTags.length > 0) {
            recommendations.push({
                type: "success",
                title: "Popular Tags",
                message: `${popularTags.length} tags are frequently used. Consider creating variations or related tags.`,
                action: "Explore tag variations",
                data: popularTags,
            });
        }

        // Recommendation 3: Content coverage
        const articlesWithoutTags = articles.filter(
            (article) => !article.tags || article.tags.length === 0,
        );
        if (articlesWithoutTags.length > 0) {
            recommendations.push({
                type: "info",
                title: "Untagged Content",
                message: `${articlesWithoutTags.length} articles have no tags. Consider adding relevant tags for better organization.`,
                action: "Tag untagged articles",
                data: articlesWithoutTags,
            });
        }

        // Recommendation 4: Tag diversity
        const avgTagsPerArticle =
            articles.length > 0
                ? articles.reduce(
                      (sum, article) =>
                          sum + (article.tags ? article.tags.length : 0),
                      0,
                  ) / articles.length
                : 0;

        if (avgTagsPerArticle < 2) {
            recommendations.push({
                type: "info",
                title: "Tag Diversity",
                message: `Average of ${avgTagsPerArticle.toFixed(1)} tags per article. Consider adding more descriptive tags.`,
                action: "Improve tag coverage",
                data: null,
            });
        }

        return recommendations;
    }

    renderAnalytics() {
        this.renderOverviewStats();
        this.renderCharts();
        this.renderTopTags();
        this.renderRecommendations();
        this.renderDetailedMetrics();
    }

    renderOverviewStats() {
        const stats = this.analyticsData;

        const overviewHtml = `
            <div class="stats-overview-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">${stats.totalTags}</div>
                        <div class="stat-label">Total Tags</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">${stats.totalUsage}</div>
                        <div class="stat-label">Total Usage</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">${stats.articlesWithTags}</div>
                        <div class="stat-label">Tagged Articles</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">${stats.averageUsage}</div>
                        <div class="stat-label">Avg Usage/Tag</div>
                    </div>
                </div>
            </div>
        `;

        this.overviewStats.innerHTML = overviewHtml;
    }

    renderCharts() {
        if (!this.options.showCharts) return;

        // Usage distribution chart
        this.renderUsageChart();

        // Trends chart
        this.renderTrendsChart();

        // Category chart
        this.renderCategoryChart();
    }

    renderUsageChart() {
        const ctx = this.usageChartContainer.getContext("2d");
        const distribution = this.analyticsData.usageDistribution;

        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: [
                    "High Usage (10+)",
                    "Medium Usage (3-9)",
                    "Low Usage (1-2)",
                    "Unused",
                ],
                datasets: [
                    {
                        data: [
                            distribution.high,
                            distribution.medium,
                            distribution.low,
                            distribution.unused,
                        ],
                        backgroundColor: [
                            "#10b981",
                            "#f59e0b",
                            "#ef4444",
                            "#6b7280",
                        ],
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: "bottom",
                    },
                    title: {
                        display: true,
                        text: "Tag Usage Distribution",
                    },
                },
            },
        });
    }

    renderTrendsChart() {
        const ctx = this.trendChartContainer.getContext("2d");
        const trends = this.analyticsData.trends;

        // Mock trend data - in real implementation, this would come from time-series data
        const trendData = {
            labels: ["Week 1", "Week 2", "Week 3", "Week 4"],
            datasets: [
                {
                    label: "Tag Usage",
                    data: [12, 19, 15, 25],
                    borderColor: "#3b82f6",
                    backgroundColor: "rgba(59, 130, 246, 0.1)",
                    fill: true,
                },
            ],
        };

        new Chart(ctx, {
            type: "line",
            data: trendData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: "Tag Usage Trends (Last 30 Days)",
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    renderCategoryChart() {
        const ctx = this.categoryChartContainer.getContext("2d");
        const categories = this.analyticsData.tagTypes;

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: ["Custom Tags", "Auto Generated", "System Tags"],
                datasets: [
                    {
                        label: "Count",
                        data: [
                            categories.custom,
                            categories.auto,
                            categories.system,
                        ],
                        backgroundColor: ["#3b82f6", "#f59e0b", "#6b7280"],
                    },
                ],
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: "Tag Types Distribution",
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }

    renderTopTags() {
        const topTags = this.analyticsData.topTags;

        if (topTags.length === 0) {
            this.topTagsContainer.innerHTML =
                '<div class="no-data">No tag data available</div>';
            return;
        }

        const topTagsHtml = topTags
            .map(
                (tag, index) => `
            <div class="top-tag-item">
                <div class="tag-rank">#${index + 1}</div>
                <div class="tag-color" style="background-color: ${tag.color}"></div>
                <div class="tag-info">
                    <div class="tag-name">${tag.name}</div>
                    <div class="tag-usage">Used ${tag.usage_count} times</div>
                </div>
                <div class="tag-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${this.calculateProgress(tag.usage_count, topTags[0].usage_count)}%"></div>
                    </div>
                </div>
            </div>
        `,
            )
            .join("");

        this.topTagsContainer.innerHTML = topTagsHtml;
    }

    renderRecommendations() {
        const recommendations = this.analyticsData.recommendations;

        if (recommendations.length === 0) {
            this.recommendationsContainer.innerHTML =
                '<div class="no-recommendations">No recommendations available</div>';
            return;
        }

        const recommendationsHtml = recommendations
            .map(
                (rec) => `
            <div class="recommendation-item ${rec.type}">
                <div class="recommendation-icon">
                    <i class="fas fa-${this.getRecommendationIcon(rec.type)}"></i>
                </div>
                <div class="recommendation-content">
                    <h4>${rec.title}</h4>
                    <p>${rec.message}</p>
                    <button class="recommendation-action btn-sm" data-action="${rec.action}">
                        ${rec.action}
                    </button>
                </div>
            </div>
        `,
            )
            .join("");

        this.recommendationsContainer.innerHTML = recommendationsHtml;

        // Bind recommendation actions
        this.container
            .querySelectorAll(".recommendation-action")
            .forEach((btn) => {
                btn.addEventListener("click", (e) =>
                    this.handleRecommendationAction(e.target.dataset.action),
                );
            });
    }

    renderDetailedMetrics() {
        const stats = this.analyticsData;

        // Tag growth
        const growthRate = stats.trends?.growthRate || 0;
        document.getElementById("tag-growth").textContent = `${growthRate}%`;

        // Usage efficiency
        document.getElementById("usage-efficiency").textContent =
            stats.averageUsage;

        // Content coverage
        const coverage =
            stats.articles.length > 0
                ? Math.round(
                      (stats.articlesWithTags / stats.articles.length) * 100,
                  )
                : 0;
        document.getElementById("content-coverage").textContent =
            `${coverage}%`;

        // Tag diversity
        const avgTagsPerArticle =
            stats.articles.length > 0
                ? stats.articles.reduce(
                      (sum, article) =>
                          sum + (article.tags ? article.tags.length : 0),
                      0,
                  ) / stats.articles.length
                : 0;
        document.getElementById("tag-diversity").textContent =
            avgTagsPerArticle.toFixed(1);
    }

    calculateProgress(value, max) {
        return max > 0 ? Math.round((value / max) * 100) : 0;
    }

    getRecommendationIcon(type) {
        const icons = {
            success: "check-circle",
            warning: "exclamation-triangle",
            info: "info-circle",
            error: "times-circle",
        };
        return icons[type] || "info-circle";
    }

    handleRecommendationAction(action) {
        // Dispatch custom events for different recommendation actions
        const event = new CustomEvent("tagRecommendationAction", {
            detail: { action: action, analytics: this.analyticsData },
        });
        document.dispatchEvent(event);
    }

    handleDateRangeChange(days) {
        this.loadAnalytics();
    }

    refreshAnalytics() {
        this.loadAnalytics();
    }

    startAutoRefresh() {
        if (this.options.refreshInterval > 0) {
            this.refreshTimer = setInterval(() => {
                this.loadAnalytics();
            }, this.options.refreshInterval);
        }
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    exportAnalytics() {
        const data = {
            exportDate: new Date().toISOString(),
            analytics: this.analyticsData,
            summary: {
                totalTags: this.analyticsData.totalTags,
                totalUsage: this.analyticsData.totalUsage,
                averageUsage: this.analyticsData.averageUsage,
                articlesWithTags: this.analyticsData.articlesWithTags,
            },
        };

        const blob = new Blob([JSON.stringify(data, null, 2)], {
            type: "application/json",
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `tag-analytics-${new Date().toISOString().split("T")[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    showLoading() {
        this.loadingStates.forEach((state) => {
            state.style.display = "block";
        });
        this.errorStates.forEach((state) => {
            state.style.display = "none";
        });
    }

    hideLoading() {
        this.loadingStates.forEach((state) => {
            state.style.display = "none";
        });
    }

    showError(message) {
        this.errorStates.forEach((state) => {
            state.innerHTML = `<div class="error-message">${message}</div>`;
            state.style.display = "block";
        });
        this.hideLoading();
    }

    getCsrfToken() {
        return (
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || ""
        );
    }

    // Public API methods
    refresh() {
        this.loadAnalytics();
    }

    getAnalyticsData() {
        return this.analyticsData;
    }

    destroy() {
        this.stopAutoRefresh();

        // Destroy charts
        Object.values(this.charts).forEach((chart) => {
            if (chart && typeof chart.destroy === "function") {
                chart.destroy();
            }
        });

        // Remove event listeners
        this.refreshBtn.removeEventListener("click", this.refreshAnalytics);
        this.dateRangeSelect.removeEventListener(
            "change",
            this.handleDateRangeChange,
        );
        this.exportBtn.removeEventListener("click", this.exportAnalytics);

        // Clear container
        this.container.innerHTML = "";
    }
}

// CSS styles for analytics dashboard
const tagAnalyticsDashboardStyles = `
.tag-analytics-dashboard {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.dashboard-header h2 {
    margin: 0;
    color: #1e293b;
    font-size: 28px;
    font-weight: 600;
}

.dashboard-controls {
    display: flex;
    gap: 12px;
    align-items: center;
}

.date-range-select {
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 14px;
    background: white;
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

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.analytics-overview {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
}

.analytics-overview h3 {
    margin: 0 0 16px 0;
    color: #1e293b;
    font-size: 20px;
    font-weight: 600;
}

.stats-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.stat-card {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    background: #3b82f6;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #64748b;
}

.charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 24px;
}

.chart-container {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.chart-container h3 {
    margin: 0 0 16px 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
}

.chart-container canvas {
    max-height: 300px;
}

.insights-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.top-tags-section,
.recommendations-section {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.top-tags-section h3,
.recommendations-section h3 {
    margin: 0 0 16px 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
}

.top-tags-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.top-tag-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 6px;
}

.tag-rank {
    font-weight: 700;
    color: #3b82f6;
    font-size: 16px;
    min-width: 30px;
}

.tag-color {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    flex-shrink: 0;
}

.tag-info {
    flex: 1;
}

.tag-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
}

.tag-usage {
    color: #64748b;
    font-size: 12px;
}

.tag-progress {
    width: 80px;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: #3b82f6;
    transition: width 0.3s ease;
}

.recommendations-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.recommendation-item {
    display: flex;
    gap: 12px;
    padding: 16px;
    border-radius: 6px;
    border: 1px solid;
}

.recommendation-item.success {
    background: #f0fdf4;
    border-color: #16a34a;
}

.recommendation-item.warning {
    background: #fffbeb;
    border-color: #d97706;
}

.recommendation-item.info {
    background: #f0f9ff;
    border-color: #0284c7;
}

.recommendation-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.recommendation-item.success .recommendation-icon {
    background: #16a34a;
    color: white;
}

.recommendation-item.warning .recommendation-icon {
    background: #d97706;
    color: white;
}

.recommendation-item.info .recommendation-icon {
    background: #0284c7;
    color: white;
}

.recommendation-content h4 {
    margin: 0 0 8px 0;
    color: #1e293b;
    font-size: 16px;
    font-weight: 600;
}

.recommendation-content p {
    margin: 0 0 12px 0;
    color: #64748b;
    font-size: 14px;
    line-height: 1.5;
}

.recommendation-action {
    margin-top: 8px;
}

.detailed-analytics {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 20px;
}

.detailed-analytics h3 {
    margin: 0 0 16px 0;
    color: #1e293b;
    font-size: 20px;
    font-weight: 600;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.analytics-card {
    background: #f8fafc;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.analytics-card h4 {
    margin: 0 0 12px 0;
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.metric-value {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.metric-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.loading-state {
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

.error-state {
    text-align: center;
    padding: 40px;
    color: #dc2626;
}

.error-message {
    background: #fee2e2;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 6px;
    border: 1px solid #fecaca;
}

.no-data,
.no-recommendations {
    text-align: center;
    padding: 40px;
    color: #64748b;
    font-style: italic;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .tag-analytics-dashboard {
        padding: 16px;
    }
    
    .dashboard-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .dashboard-controls {
        width: 100%;
        justify-content: space-between;
    }
    
    .charts-section {
        grid-template-columns: 1fr;
    }
    
    .insights-section {
        grid-template-columns: 1fr;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-overview-grid {
        grid-template-columns: 1fr;
    }
}
`;

// Inject Chart.js if not already loaded
if (typeof Chart === "undefined") {
    const script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/chart.js";
    document.head.appendChild(script);
}

// Inject styles if not already present
if (!document.querySelector("#tag-analytics-dashboard-styles")) {
    const styleSheet = document.createElement("style");
    styleSheet.id = "tag-analytics-dashboard-styles";
    styleSheet.textContent = tagAnalyticsDashboardStyles;
    document.head.appendChild(styleSheet);
}

// Export for use in other modules
window.TagAnalyticsDashboard = TagAnalyticsDashboard;
