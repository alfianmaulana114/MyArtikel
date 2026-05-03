@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h1>{{ $article->title }}</h1>
                    <div class="article-meta">
                        <small class="text-muted">
                            Created: {{ $article->created_at->format('d M Y') }}
                            | Updated: {{ $article->updated_at->format('d M Y') }}
                            | Author: {{ $article->user->name }}
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    <div class="article-content">
                        {!! $article->content !!}
                    </div>
                    
                    @if($article->tags->count() > 0)
                        <div class="article-tags mt-3">
                            <strong>Tags:</strong>
                            @foreach($article->tags as $tag)
                                <span class="badge badge-secondary">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Summary Generator Component -->
            @include('components.summary-generator', ['article' => $article])
            
            <!-- Article Actions -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Actions</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group-vertical w-100" role="group">
                        <a href="{{ route('articles.edit', $article) }}" class="btn btn-outline-primary mb-2">
                            <i class="fas fa-edit"></i> Edit Article
                        </a>
                        
                        <form action="{{ route('articles.destroy', $article) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100" 
                                    onclick="return confirm('Are you sure you want to delete this article?')">
                                <i class="fas fa-trash"></i> Delete Article
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Existing Summaries -->
            @if($article->summaries->count() > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Existing Summaries</h5>
                    </div>
                    <div class="card-body">
                        @foreach($article->summaries as $summary)
                            <div class="existing-summary mb-3 p-2 border rounded">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">
                                            {{ ucfirst($summary->source) }} • 
                                            {{ $summary->word_count }} words • 
                                            {{ $summary->created_at->diffForHumans() }}
                                        </small>
                                        <p class="mb-1 mt-1">
                                            {{ Str::limit($summary->content, 100) }}
                                        </p>
                                    </div>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                onclick="viewSummary({{ $summary->id }})">
                                            View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
@endpush