@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Job Monitoring Dashboard</h1>
            
            <!-- Status Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Pending Jobs</h6>
                                    <h3 id="pending-count">-</h3>
                                </div>
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Processing</h6>
                                    <h3 id="processing-count">-</h3>
                                </div>
                                <i class="fas fa-spinner fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Completed (24h)</h6>
                                    <h3 id="completed-count">-</h3>
                                </div>
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Failed</h6>
                                    <h3 id="failed-count">-</h3>
                                </div>
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Health Status -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">System Health</h5>
                        </div>
                        <div class="card-body">
                            <div id="health-status">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Type Statistics -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Job Type Statistics</h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="job-type-stats">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Management Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Queue Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-primary" onclick="restartWorkers()">
                                    <i class="fas fa-redo"></i> Restart Workers
                                </button>
                                <button class="btn btn-outline-warning" onclick="retryFailedJobs()">
                                    <i class="fas fa-redo-alt"></i> Retry Failed Jobs
                                </button>
                                <button class="btn btn-outline-danger" onclick="showFailedJobs()">
                                    <i class="fas fa-exclamation-triangle"></i> View Failed Jobs
                                </button>
                                <button class="btn btn-outline-info" onclick="clearCache()">
                                    <i class="fas fa-broom"></i> Clear Cache
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Failed Jobs Modal -->
            <div class="modal fade" id="failedJobsModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Failed Jobs</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="failed-jobs-content">
                                <div class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-warning" onclick="retryAllFailedJobs()">
                                <i class="fas fa-redo-alt"></i> Retry All
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh interval (in milliseconds)
const REFRESH_INTERVAL = 30000; // 30 seconds
let refreshTimer;

/**
 * Load initial data
 */
document.addEventListener('DOMContentLoaded', function() {
    loadQueueStats();
    loadJobTypeStats();
    loadHealthStatus();
    
    // Set up auto-refresh
    refreshTimer = setInterval(function() {
        loadQueueStats();
        loadJobTypeStats();
        loadHealthStatus();
    }, REFRESH_INTERVAL);
});

/**
 * Load queue statistics
 */
function loadQueueStats() {
    fetch('/jobs/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateQueueStats(data.data);
            }
        })
        .catch(error => {
            console.error('Failed to load queue stats:', error);
        });
}

/**
 * Load job type statistics
 */
function loadJobTypeStats() {
    fetch('/jobs/job-types')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateJobTypeStats(data.data);
            }
        })
        .catch(error => {
            console.error('Failed to load job type stats:', error);
        });
}

/**
 * Load health status
 */
function loadHealthStatus() {
    fetch('/jobs/health')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateHealthStatus(data.data);
            }
        })
        .catch(error => {
            console.error('Failed to load health status:', error);
        });
}

/**
 * Update queue statistics display
 */
function updateQueueStats(stats) {
    document.getElementById('pending-count').textContent = stats.pending.toLocaleString();
    document.getElementById('processing-count').textContent = stats.processing.toLocaleString();
    document.getElementById('completed-count').textContent = stats.completed.toLocaleString();
    document.getElementById('failed-count').textContent = stats.failed.toLocaleString();
}

/**
 * Update job type statistics display
 */
function updateJobTypeStats(jobTypes) {
    const container = document.getElementById('job-type-stats');
    
    if (Object.keys(jobTypes).length === 0) {
        container.innerHTML = '<p class="text-muted">No job activity in the last 24 hours</p>';
        return;
    }
    
    let html = '<div class="row">';
    
    for (const [jobType, stats] of Object.entries(jobTypes)) {
        const successRate = stats.success_rate || 0;
        const avgTime = stats.avg_processing_time || 0;
        
        html += `
            <div class="col-md-6 mb-3">
                <div class="card border-light">
                    <div class="card-body">
                        <h6 class="card-title">${jobType}</h6>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Pending</small>
                                <div class="h5">${stats.pending}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Failed</small>
                                <div class="h5 text-danger">${stats.failed}</div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <small class="text-muted">Success Rate</small>
                                <div class="h5 ${successRate >= 90 ? 'text-success' : successRate >= 70 ? 'text-warning' : 'text-danger'}">
                                    ${successRate.toFixed(1)}%
                                </div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Avg Time</small>
                                <div class="h6">${avgTime.toFixed(2)}s</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Update health status display
 */
function updateHealthStatus(health) {
    const container = document.getElementById('health-status');
    
    if (health.healthy) {
        container.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> System is healthy
            </div>
        `;
    } else {
        let html = '<div class="alert alert-warning">';
        html += '<i class="fas fa-exclamation-triangle"></i> System health issues detected:';
        html += '<ul class="mt-2">';
        
        health.issues.forEach(issue => {
            html += `<li><strong>${issue.type}:</strong> ${issue.message}</li>`;
        });
        
        html += '</ul>';
        
        if (health.recommendations.length > 0) {
            html += '<div class="mt-3"><strong>Recommendations:</strong></div>';
            html += '<ul>';
            health.recommendations.forEach(rec => {
                html += `<li>${rec}</li>`;
            });
            html += '</ul>';
        }
        
        html += '</div>';
        container.innerHTML = html;
    }
}

/**
 * Refresh all statistics
 */
function refreshStats() {
    loadQueueStats();
    loadJobTypeStats();
    loadHealthStatus();
    
    // Show feedback
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    button.disabled = true;
    
    setTimeout(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    }, 1000);
}

/**
 * Restart queue workers
 */
function restartWorkers() {
    if (!confirm('Are you sure you want to restart all queue workers? This will temporarily stop job processing.')) {
        return;
    }
    
    fetch('/jobs/restart-workers', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshStats();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Failed to restart workers:', error);
        alert('Failed to restart workers');
    });
}

/**
 * Retry failed jobs
 */
function retryFailedJobs() {
    if (!confirm('Are you sure you want to retry all failed jobs?')) {
        return;
    }
    
    fetch('/jobs/retry', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ all: true })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshStats();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Failed to retry jobs:', error);
        alert('Failed to retry jobs');
    });
}

/**
 * Show failed jobs
 */
function showFailedJobs() {
    const modal = document.getElementById('failedJobsModal');
    const content = document.getElementById('failed-jobs-content');
    
    content.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    
    fetch('/jobs/failed')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayFailedJobs(data.data);
            } else {
                content.innerHTML = '<div class="alert alert-danger">Failed to load failed jobs</div>';
            }
        })
        .catch(error => {
            console.error('Failed to load failed jobs:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load failed jobs</div>';
        });
    
    $(modal).modal('show');
}

/**
 * Display failed jobs
 */
function displayFailedJobs(data) {
    const content = document.getElementById('failed-jobs-content');
    
    if (data.jobs.length === 0) {
        content.innerHTML = '<div class="alert alert-info">No failed jobs found</div>';
        return;
    }
    
    let html = '<div class="table-responsive">';
    html += '<table class="table table-sm">';
    html += '<thead><tr><th>ID</th><th>Queue</th><th>Failed At</th><th>Exception</th><th>Actions</th></tr></thead>';
    html += '<tbody>';
    
    data.jobs.forEach(job => {
        html += `<tr>
            <td>${job.id}</td>
            <td>${job.queue}</td>
            <td>${new Date(job.failed_at).toLocaleString()}</td>
            <td><small>${job.exception.split('\n')[0]}</small></td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="retryJob(${job.id})">
                    <i class="fas fa-redo"></i>
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    html += '</div>';
    
    if (data.total > data.jobs.length) {
        html += `<div class="text-muted mt-2">Showing ${data.jobs.length} of ${data.total} failed jobs</div>`;
    }
    
    content.innerHTML = html;
}

/**
 * Retry specific job
 */
function retryJob(jobId) {
    fetch('/jobs/retry', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ job_id: jobId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            showFailedJobs(); // Refresh the list
            refreshStats();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Failed to retry job:', error);
        alert('Failed to retry job');
    });
}

/**
 * Retry all failed jobs
 */
function retryAllFailedJobs() {
    if (!confirm('Are you sure you want to retry ALL failed jobs?')) {
        return;
    }
    
    retryFailedJobs();
    $(document.getElementById('failedJobsModal')).modal('hide');
}

/**
 * Clear monitoring cache
 */
function clearCache() {
    fetch('/jobs/clear-cache', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            refreshStats();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Failed to clear cache:', error);
        alert('Failed to clear cache');
    });
}

/**
 * Clean up on page unload
 */
window.addEventListener('beforeunload', function() {
    if (refreshTimer) {
        clearInterval(refreshTimer);
    }
});
</script>
@endpush