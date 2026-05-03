<div class="summary-generator">
    <div class="summary-controls">
        <button 
            id="generate-summary-btn" 
            class="btn btn-primary"
            data-article-id="{{ $article->id ?? '' }}"
            onclick="generateSummary()"
        >
            <span class="btn-text">Generate Summary</span>
            <span class="btn-loading" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i> Processing...
            </span>
        </button>
        
        <div class="summary-options">
            <div class="form-group">
                <label for="max-words">Max Words:</label>
                <select id="max-words" class="form-control">
                    <option value="100">100 words</option>
                    <option value="150" selected>150 words</option>
                    <option value="200">200 words</option>
                    <option value="300">300 words</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="prefer-ai" checked> Use AI (Gemini)
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="async-mode"> Async Processing
                </label>
            </div>
        </div>
    </div>

    <div id="summary-result" class="summary-result" style="display: none;">
        <div class="summary-header">
            <h4>Summary</h4>
            <div class="summary-meta">
                <span class="word-count"></span>
                <span class="source"></span>
                <span class="processing-time"></span>
            </div>
        </div>
        
        <div class="summary-content">
            <div class="summary-paragraph"></div>
            <div class="summary-key-points">
                <h5>Key Points:</h5>
                <ul class="key-points-list"></ul>
            </div>
        </div>
        
        <div class="summary-actions">
            <button class="btn btn-secondary" onclick="regenerateSummary()">
                <i class="fas fa-refresh"></i> Regenerate
            </button>
            <button class="btn btn-outline" onclick="editSummary()">
                <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-success" onclick="saveSummary()">
                <i class="fas fa-save"></i> Save
            </button>
        </div>
    </div>

    <div id="summary-quota" class="summary-quota">
        <div class="quota-status">
            <h5>Quota Status</h5>
            <div class="quota-items">
                <div class="quota-item">
                    <span class="quota-label">AI (Gemini):</span>
                    <span class="quota-value" id="gemini-quota">Loading...</span>
                </div>
                <div class="quota-item">
                    <span class="quota-label">Local:</span>
                    <span class="quota-value" id="local-quota">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.summary-generator {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.summary-controls {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.summary-options {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.summary-options .form-group {
    margin: 0;
}

.summary-options label {
    margin: 0;
    font-weight: normal;
}

.summary-options select {
    width: auto;
    min-width: 120px;
}

.summary-result {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid #dee2e6;
}

.summary-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.summary-header h4 {
    margin: 0;
    color: #333;
}

.summary-meta {
    display: flex;
    gap: 15px;
    font-size: 0.9em;
    color: #666;
}

.summary-meta span {
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
}

.summary-paragraph {
    margin-bottom: 20px;
    line-height: 1.6;
    color: #333;
}

.summary-key-points h5 {
    margin-bottom: 10px;
    color: #333;
}

.key-points-list {
    margin: 0;
    padding-left: 20px;
}

.key-points-list li {
    margin-bottom: 5px;
    color: #555;
}

.summary-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.summary-quota {
    background: white;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
    border: 1px solid #dee2e6;
}

.quota-status h5 {
    margin-bottom: 10px;
    color: #333;
}

.quota-items {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.quota-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.quota-label {
    font-weight: 500;
    color: #666;
}

.quota-value {
    font-weight: bold;
}

.quota-value.low {
    color: #dc3545;
}

.quota-value.medium {
    color: #ffc107;
}

.quota-value.high {
    color: #28a745;
}

.btn-loading {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.alert {
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .summary-controls {
        flex-direction: column;
        align-items: stretch;
    }
    
    .summary-options {
        justify-content: space-between;
    }
    
    .summary-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .summary-meta {
        flex-wrap: wrap;
    }
    
    .summary-actions {
        flex-wrap: wrap;
    }
}
</style>

<script>
let currentSummary = null;
let currentArticleId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadQuotaStatus();
    
    // Set current article ID if available
    const articleElement = document.querySelector('[data-article-id]');
    if (articleElement) {
        currentArticleId = articleElement.dataset.articleId;
    }
});

/**
 * Generate summary for article
 */
async function generateSummary() {
    if (!currentArticleId) {
        showAlert('No article selected', 'error');
        return;
    }
    
    const btn = document.getElementById('generate-summary-btn');
    const btnText = btn.querySelector('.btn-text');
    const btnLoading = btn.querySelector('.btn-loading');
    
    // Get options
    const maxWords = document.getElementById('max-words').value;
    const preferAi = document.getElementById('prefer-ai').checked;
    const asyncMode = document.getElementById('async-mode').checked;
    
    try {
        // Show loading state
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline-flex';
        hideSummaryResult();
        
        const response = await fetch('/summaries/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                article_id: currentArticleId,
                max_words: parseInt(maxWords),
                prefer_ai: preferAi,
                async: asyncMode
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to generate summary');
        }
        
        if (asyncMode && result.data.status === 'pending') {
            showAlert('Summary generation queued. Check status for updates.', 'info');
            pollSummaryStatus(result.data.summary_id);
        } else {
            displaySummary(result.data);
            showAlert('Summary generated successfully!', 'success');
        }
        
        // Refresh quota status
        loadQuotaStatus();
        
    } catch (error) {
        console.error('Summary generation error:', error);
        showAlert('Failed to generate summary: ' + error.message, 'error');
    } finally {
        // Hide loading state
        btn.disabled = false;
        btnText.style.display = 'inline';
        btnLoading.style.display = 'none';
    }
}

/**
 * Display summary result
 */
function displaySummary(summary) {
    currentSummary = summary;
    
    const resultDiv = document.getElementById('summary-result');
    const paragraphDiv = resultDiv.querySelector('.summary-paragraph');
    const keyPointsList = resultDiv.querySelector('.key-points-list');
    const wordCountSpan = resultDiv.querySelector('.word-count');
    const sourceSpan = resultDiv.querySelector('.source');
    const processingTimeSpan = resultDiv.querySelector('.processing-time');
    
    // Display content
    paragraphDiv.textContent = summary.content || summary.summary || 'No summary content available';
    
    // Display key points
    keyPointsList.innerHTML = '';
    const keyPoints = summary.key_points || [];
    if (keyPoints.length > 0) {
        keyPoints.forEach(point => {
            const li = document.createElement('li');
            li.textContent = point;
            keyPointsList.appendChild(li);
        });
    } else {
        keyPointsList.innerHTML = '<li>No key points available</li>';
    }
    
    // Display metadata
    wordCountSpan.textContent = `${summary.word_count || 0} words`;
    sourceSpan.textContent = `Source: ${summary.source || 'Unknown'}`;
    processingTimeSpan.textContent = `Time: ${summary.processing_time || 'N/A'}`;
    
    // Show result
    resultDiv.style.display = 'block';
}

/**
 * Poll summary status for async processing
 */
async function pollSummaryStatus(summaryId) {
    const maxAttempts = 30; // 5 minutes with 10-second intervals
    let attempts = 0;
    
    const poll = async () => {
        try {
            const response = await fetch(`/summaries/${summaryId}/status`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error('Failed to check status');
            }
            
            const status = result.data.status;
            
            if (status === 'completed') {
                // Fetch the completed summary
                const summaryResponse = await fetch(`/summaries/${summaryId}`);
                const summaryResult = await summaryResponse.json();
                
                if (summaryResult.success) {
                    displaySummary(summaryResult.data);
                    showAlert('Summary completed!', 'success');
                    loadQuotaStatus();
                }
                return;
            } else if (status === 'failed') {
                throw new Error(result.data.error_message || 'Summary generation failed');
            } else if (attempts >= maxAttempts) {
                throw new Error('Summary generation timed out');
            }
            
            // Continue polling
            attempts++;
            setTimeout(poll, 10000); // Poll every 10 seconds
            
        } catch (error) {
            console.error('Status polling error:', error);
            showAlert('Summary generation failed: ' + error.message, 'error');
        }
    };
    
    poll();
}

/**
 * Load quota status
 */
async function loadQuotaStatus() {
    try {
        const response = await fetch('/summaries/quota');
        const result = await response.json();
        
        if (result.success) {
            updateQuotaDisplay(result.data.quota_status);
        }
    } catch (error) {
        console.error('Quota status error:', error);
    }
}

/**
 * Update quota display
 */
function updateQuotaDisplay(quotaStatus) {
    const geminiQuota = document.getElementById('gemini-quota');
    const localQuota = document.getElementById('local-quota');
    
    // Update Gemini quota
    if (geminiQuota && quotaStatus.gemini) {
        const gemini = quotaStatus.gemini;
        geminiQuota.textContent = `${gemini.used_today}/${gemini.limit} (${gemini.remaining} remaining)`;
        geminiQuota.className = `quota-value ${getQuotaLevel(gemini.remaining, gemini.limit)}`;
    }
    
    // Update Local quota
    if (localQuota && quotaStatus.local) {
        const local = quotaStatus.local;
        localQuota.textContent = `${local.used_today}/${local.limit} (${local.remaining} remaining)`;
        localQuota.className = `quota-value ${getQuotaLevel(local.remaining, local.limit)}`;
    }
}

/**
 * Get quota level (low/medium/high)
 */
function getQuotaLevel(remaining, limit) {
    const percentage = (remaining / limit) * 100;
    if (percentage <= 20) return 'low';
    if (percentage <= 50) return 'medium';
    return 'high';
}

/**
 * Regenerate summary
 */
async function regenerateSummary() {
    if (!currentArticleId) return;
    
    const maxWords = document.getElementById('max-words').value;
    const preferAi = document.getElementById('prefer-ai').checked;
    
    try {
        const response = await fetch(`/summaries/${currentArticleId}/regenerate`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                max_words: parseInt(maxWords),
                prefer_ai: preferAi
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to regenerate summary');
        }
        
        displaySummary(result.data);
        showAlert('Summary regenerated successfully!', 'success');
        loadQuotaStatus();
        
    } catch (error) {
        console.error('Summary regeneration error:', error);
        showAlert('Failed to regenerate summary: ' + error.message, 'error');
    }
}

/**
 * Edit summary (placeholder)
 */
function editSummary() {
    if (!currentSummary) return;
    
    showAlert('Edit functionality coming soon!', 'info');
}

/**
 * Save summary (placeholder)
 */
function saveSummary() {
    if (!currentSummary) return;
    
    showAlert('Summary saved successfully!', 'success');
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertClass = `alert-${type}`;
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    // Insert alert at the top of summary generator
    const summaryGenerator = document.querySelector('.summary-generator');
    const existingAlert = summaryGenerator.querySelector('.alert');
    
    if (existingAlert) {
        existingAlert.remove();
    }
    
    summaryGenerator.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = summaryGenerator.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}

/**
 * Hide summary result
 */
function hideSummaryResult() {
    document.getElementById('summary-result').style.display = 'none';
}
</script>
