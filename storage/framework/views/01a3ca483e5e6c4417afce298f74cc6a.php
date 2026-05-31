<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl theme-text-primary leading-tight">
                Bookmarks
            </h2>
            <p class="text-sm theme-text-muted">
                Simpan artikel penting dan kelola dalam kategori.
            </p>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Total</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary" id="total-bookmarks"><?php echo e(auth()->user()->bookmarks_count ?? 0); ?></div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Read</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary" id="read-bookmarks">0</div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Favorites</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary" id="favorite-bookmarks">0</div>
                        </div>
                        <div class="rounded-xl border theme-border-primary bg-[color:var(--surface-primary)] p-4">
                            <div class="text-xs theme-text-muted">Categories</div>
                            <div class="mt-1 text-lg font-semibold theme-text-primary" id="total-categories"><?php echo e(auth()->user()->bookmark_categories_count ?? 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col lg:flex-row gap-3 lg:items-end">
                        <div class="flex-1">
                            <label for="search-input" class="block font-medium text-sm theme-text-secondary">Cari</label>
                            <input
                                type="text"
                                id="search-input"
                                placeholder="Cari bookmark…"
                                class="mt-1 w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] placeholder:text-[color:var(--text-muted)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]"
                            >
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div>
                                <label for="category-filter" class="block font-medium text-sm theme-text-secondary">Kategori</label>
                                <select id="category-filter" class="mt-1 w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                    <option value="">Semua</option>
                                </select>
                            </div>
                            <div>
                                <label for="status-filter" class="block font-medium text-sm theme-text-secondary">Status</label>
                                <select id="status-filter" class="mt-1 w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                    <option value="">Semua</option>
                                    <option value="unread">Unread</option>
                                    <option value="read">Read</option>
                                    <option value="favorite">Favorites</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <div>
                                <label for="sort-by" class="block font-medium text-sm theme-text-secondary">Urutkan</label>
                                <select id="sort-by" class="mt-1 w-full rounded-lg shadow-sm border theme-border-primary bg-[color:var(--surface-primary)] text-[color:var(--text-primary)] focus:border-[#AA5F3C] focus:ring-[#AA5F3C]">
                                    <option value="created_at">Date Added</option>
                                    <option value="read_at">Date Read</option>
                                    <option value="title">Title</option>
                                    <option value="priority">Priority</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div id="bookmarks-list" class="space-y-4"></div>

                    <div id="loading-indicator" class="hidden text-center py-8">
                        <div class="text-sm theme-text-muted">Memuat…</div>
                    </div>

                    <div id="no-bookmarks" class="hidden text-center py-10">
                        <div class="text-sm font-medium theme-text-primary">Belum ada bookmark</div>
                        <div class="mt-1 text-sm theme-text-muted">Mulai bookmark artikel dari dashboard.</div>
                    </div>
                </div>
            </div>

            <div id="pagination" class="flex flex-wrap gap-2"></div>
        </div>

        <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24 self-start">
            <div class="card overflow-hidden sm:rounded-lg">
                <div class="p-6">
                    <div class="font-semibold theme-text-primary">Tips</div>
                    <ul class="mt-3 text-sm theme-text-secondary space-y-2">
                        <li>Gunakan Favorites untuk daftar bacaan prioritas.</li>
                        <li>Archive untuk menyimpan tanpa mengotori daftar utama.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let currentFilters = {
        search: '',
        category_id: '',
        status: '',
        sort_by: 'created_at',
        sort_order: 'desc'
    };

    // Load initial data
    loadBookmarks();
    loadCategories();
    loadStats();

    // Event listeners
    document.getElementById('search-input').addEventListener('input', debounce(function() {
        currentFilters.search = this.value;
        currentPage = 1;
        loadBookmarks();
    }, 300));

    document.getElementById('category-filter').addEventListener('change', function() {
        currentFilters.category_id = this.value;
        currentPage = 1;
        loadBookmarks();
    });

    document.getElementById('status-filter').addEventListener('change', function() {
        currentFilters.status = this.value;
        currentPage = 1;
        loadBookmarks();
    });

    document.getElementById('sort-by').addEventListener('change', function() {
        currentFilters.sort_by = this.value;
        currentPage = 1;
        loadBookmarks();
    });

    function loadBookmarks() {
        showLoading();
        
        const params = new URLSearchParams({
            page: currentPage,
            ...currentFilters
        });

        fetch(`/bookmarks/data?${params}`, {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBookmarks(data.data.bookmarks);
                displayPagination(data.data.pagination);
                updateStats(data.data.stats);
            } else {
                showError('Failed to load bookmarks');
            }
        })
        .catch(error => {
            console.error('Error loading bookmarks:', error);
            showError('Failed to load bookmarks');
        })
        .finally(() => {
            hideLoading();
        });
    }

    function loadCategories() {
        fetch('/bookmark-categories', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayCategories(data.data.categories);
            }
        })
        .catch(error => {
            console.error('Error loading categories:', error);
        });
    }

    function loadStats() {
        fetch('/bookmark-analytics/overview', {
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStats(data.data.overview);
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
    }

    function displayBookmarks(bookmarks) {
        const container = document.getElementById('bookmarks-list');
        const noBookmarks = document.getElementById('no-bookmarks');
        
        if (bookmarks.length === 0) {
            container.innerHTML = '';
            noBookmarks.classList.remove('hidden');
            return;
        }

        noBookmarks.classList.add('hidden');
        container.innerHTML = bookmarks.map(bookmark => createBookmarkHTML(bookmark)).join('');
    }

    function createBookmarkHTML(bookmark) {
        const categoryColor = bookmark.category?.color || '#6B7280';
        const isRead = bookmark.is_read;
        const isFavorite = bookmark.is_favorite;
        const isArchived = bookmark.is_archived;
        
        return `
            <div class="card p-4 ${isRead ? 'opacity-90' : ''}" 
                 data-bookmark-id="${bookmark.id}">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-2">
                            <h3 class="text-lg font-semibold theme-text-primary truncate">
                                <a href="/articles/${bookmark.article.id}" class="hover:text-[color:var(--text-link-hover)]">
                                    ${bookmark.article.title}
                                </a>
                            </h3>
                            ${isFavorite ? '<span class="text-yellow-500">★</span>' : ''}
                            ${isRead ? '<span class="text-green-500 text-sm">✓</span>' : ''}
                        </div>
                        
                        <p class="theme-text-secondary text-sm mb-2 line-clamp-2">
                            ${bookmark.article.excerpt || 'No excerpt available'}
                        </p>
                        
                        <div class="flex flex-wrap items-center gap-3 text-sm theme-text-muted">
                            ${bookmark.category ? `
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs" 
                                      style="background-color: ${categoryColor}20; color: ${categoryColor}">
                                    ${bookmark.category.name}
                                </span>
                            ` : ''}
                            <span>Added ${bookmark.time_since_bookmarked}</span>
                            ${bookmark.read_at ? `<span>Read ${new Date(bookmark.read_at).toLocaleDateString()}</span>` : ''}
                            ${bookmark.reading_time ? `<span>${bookmark.reading_time} min read</span>` : ''}
                        </div>
                        
                        ${bookmark.notes ? `
                            <div class="mt-3 text-sm bg-[color:var(--warning-bg)] text-[color:var(--warning)] border border-[color:var(--warning-border)] rounded-lg px-3 py-2">
                                <strong>Notes:</strong> ${bookmark.notes}
                            </div>
                        ` : ''}
                        
                        ${bookmark.tags && bookmark.tags.length > 0 ? `
                            <div class="mt-2 flex flex-wrap gap-1">
                                ${bookmark.tags.map(tag => `
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-[color:var(--bg-tertiary)] theme-text-secondary">
                                        #${tag}
                                    </span>
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="flex items-center gap-2 ml-4">
                        <button onclick="toggleFavorite('${bookmark.id}')" 
                                class="p-2 rounded-lg hover:bg-[color:var(--hover-bg)] ${isFavorite ? 'text-yellow-500' : 'theme-text-muted'}"
                                title="${isFavorite ? 'Remove from favorites' : 'Add to favorites'}">
                            <svg class="w-5 h-5" fill="${isFavorite ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        </button>
                        
                        <button onclick="markAsRead('${bookmark.id}')" 
                                class="p-2 rounded-lg hover:bg-[color:var(--hover-bg)] ${isRead ? 'text-green-500' : 'theme-text-muted'}"
                                title="${isRead ? 'Mark as unread' : 'Mark as read'}"
                                ${isRead ? 'style="display: none;"' : ''}>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </button>
                        
                        <button onclick="archiveBookmark('${bookmark.id}')" 
                                class="p-2 rounded-lg hover:bg-[color:var(--hover-bg)] theme-text-muted"
                                title="Archive bookmark">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </button>
                        
                        <button onclick="deleteBookmark('${bookmark.id}')" 
                                class="p-2 rounded-lg hover:bg-[color:var(--error-bg)] text-[color:var(--error)]"
                                title="Delete bookmark">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    function displayPagination(pagination) {
        const container = document.getElementById('pagination');
        
        if (pagination.total === 0) {
            container.innerHTML = '';
            return;
        }

        let html = '';
        
        // Previous button
        if (pagination.current_page > 1) {
            html += `
                <button onclick="goToPage(${pagination.current_page - 1})" 
                        class="btn btn-secondary">
                    Prev
                </button>
            `;
        }

        // Page numbers
        for (let i = 1; i <= pagination.last_page; i++) {
            if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                html += `
                    <button onclick="goToPage(${i})" 
                            class="btn btn-secondary ${i === pagination.current_page ? 'opacity-100' : 'opacity-90'}">
                        ${i}
                    </button>
                `;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                html += '<span class="px-2 py-2 text-sm theme-text-muted">…</span>';
            }
        }

        // Next button
        if (pagination.current_page < pagination.last_page) {
            html += `
                <button onclick="goToPage(${pagination.current_page + 1})" 
                        class="btn btn-secondary">
                    Next
                </button>
            `;
        }

        container.innerHTML = html;
    }

    function displayCategories(categories) {
        const select = document.getElementById('category-filter');
        const currentValue = select.value;
        
        select.innerHTML = '<option value="">Semua</option>';
        
        categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            option.style.color = category.color;
            
            if (currentValue == category.id) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
    }

    function updateStats(stats) {
        document.getElementById('read-bookmarks').textContent = stats.read_count || 0;
        document.getElementById('favorite-bookmarks').textContent = stats.favorite_count || 0;
    }

    function goToPage(page) {
        currentPage = page;
        loadBookmarks();
    }

    function toggleFavorite(bookmarkId) {
        fetch(`/bookmarks/${bookmarkId}/favorite`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadBookmarks();
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error toggling favorite:', error);
            showNotification('Failed to update favorite status', 'error');
        });
    }

    function markAsRead(bookmarkId) {
        fetch(`/bookmarks/${bookmarkId}/read`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadBookmarks();
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error marking as read:', error);
            showNotification('Failed to mark as read', 'error');
        });
    }

    function archiveBookmark(bookmarkId) {
        if (!confirm('Are you sure you want to archive this bookmark?')) {
            return;
        }

        fetch(`/bookmarks/${bookmarkId}/archive`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadBookmarks();
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error archiving bookmark:', error);
            showNotification('Failed to archive bookmark', 'error');
        });
    }

    function deleteBookmark(bookmarkId) {
        if (!confirm('Are you sure you want to delete this bookmark?')) {
            return;
        }

        fetch(`/bookmarks/${bookmarkId}`, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadBookmarks();
                showNotification(data.message, 'success');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting bookmark:', error);
            showNotification('Failed to delete bookmark', 'error');
        });
    }

    function showLoading() {
        document.getElementById('loading-indicator').classList.remove('hidden');
    }

    function hideLoading() {
        document.getElementById('loading-indicator').classList.add('hidden');
    }

    function showError(message) {
        const container = document.getElementById('bookmarks-list');
        container.innerHTML = `
            <div class="text-center py-8">
                <div class="mb-2 text-[color:var(--error)]">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold theme-text-primary mb-1">Error</h3>
                <p class="text-sm theme-text-muted">${message}</p>
            </div>
        `;
    }

    function showNotification(message, type) {
        // Simple notification implementation
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>
<?php $__env->stopPush(); ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\myartikel\resources\views\bookmarks\index.blade.php ENDPATH**/ ?>