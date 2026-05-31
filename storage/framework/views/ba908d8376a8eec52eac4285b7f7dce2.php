

<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1><?php echo e($article->title); ?></h1>
                    <div class="article-meta">
                        <small class="text-muted">
                            Created: <?php echo e($article->created_at->format('d M Y')); ?>

                            | Updated: <?php echo e($article->updated_at->format('d M Y')); ?>

                            | Author: <?php echo e($article->user->name); ?>

                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="article-content">
                        <?php echo $article->content_sanitized ?? strip_tags($article->content); ?>

                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Summary Generator Component -->
            <?php echo $__env->make('components.summary-generator', ['article' => $article], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            
            <!-- Article Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Actions</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group-vertical w-100" role="group">
                        <a href="<?php echo e(route('articles.edit', $article)); ?>" class="btn btn-outline-primary mb-2">
                            <i class="fas fa-edit"></i> Edit Article
                        </a>
                        
                        <form action="<?php echo e(route('articles.destroy', $article)); ?>" method="POST" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-outline-danger w-100" 
                                    onclick="return confirm('Are you sure you want to delete this article?')">
                                <i class="fas fa-trash"></i> Delete Article
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Existing Summaries -->
            <?php if($article->summaries->count() > 0): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Existing Summaries</h5>
                    </div>
                    <div class="card-body">
                        <?php $__currentLoopData = $article->summaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $summary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="existing-summary mb-3 p-2 border rounded">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">
                                            <?php echo e(ucfirst($summary->source)); ?> • 
                                            <?php echo e($summary->word_count); ?> words • 
                                            <?php echo e($summary->created_at->diffForHumans()); ?>

                                        </small>
                                        <p class="mb-1 mt-1">
                                            <?php echo e(Str::limit($summary->content, 100)); ?>

                                        </p>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                onclick="viewSummary(<?php echo e($summary->id); ?>)">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
/**
 * View specific summary
 */
function viewSummary(summaryId) {
    fetch(`/summaries/${summaryId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displaySummaryInModal(result.data);
            } else {
                alert('Failed to load summary');
            }
        })
        .catch(error => {
            console.error('Error loading summary:', error);
            alert('Error loading summary');
        });
}

/**
 * Display summary in modal
 */
function displaySummaryInModal(summary) {
    const modalHtml = `
        <div class="modal fade" id="summaryModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Summary Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="summary-meta mb-3">
                            <small class="text-muted">
                                ${ucfirst(summary.source)} • 
                                ${summary.word_count} words • 
                                ${summary.processing_time || 'N/A'} processing time
                            </small>
                        </div>
                        
                        <div class="summary-content">
                            <h6>Summary:</h6>
                            <p>${summary.content}</p>
                            
                            ${summary.key_points && summary.key_points.length > 0 ? `
                                <h6>Key Points:</h6>
                                <ul>
                                    ${summary.key_points.map(point => `<li>${point}</li>`).join('')}
                                </ul>
                            ` : ''}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('summaryModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add new modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Show modal
    $('#summaryModal').modal('show');
}

/**
 * Utility function to capitalize first letter
 */
function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\myartikel\resources\views\articles\show-with-summary.blade.php ENDPATH**/ ?>