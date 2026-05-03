<div class="bookmark-button-wrapper" data-controller="bookmark">
    <button type="button"
            class="{{ $getButtonClasses() }}"
            data-action="click->bookmark#toggle"
            data-bookmark-target="button"
            title="{{ $getTooltipText() }}"
            @foreach($getDataAttributes() as $key => $value)
                data-{{ $key }}="{{ $value }}"
            @endforeach>
        
        <svg class="{{ $getIconClasses() }} mr-1.5" 
             data-bookmark-target="icon"
             fill="{{ $isBookmarked ? 'currentColor' : 'none' }}" 
             stroke="currentColor" 
             viewBox="0 0 24 24">
            <path stroke-linecap="round" 
                  stroke-linejoin="round" 
                  stroke-width="2" 
                  d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z">
            </path>
        </svg>
        
        <span data-bookmark-target="text">{{ $getButtonText() }}</span>
    </button>

    @if($showCategories && auth()->check() && $categories->isNotEmpty())
        <div class="bookmark-categories-dropdown absolute z-50 mt-2 w-64 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none hidden"
             data-bookmark-target="dropdown"
             role="menu"
             aria-orientation="vertical"
             tabindex="-1">
            
            <div class="p-2">
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Save to category
                    </label>
                    <select data-bookmark-target="categorySelect"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">No category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                    {{ $bookmark && $bookmark->category_id === $category->id ? 'selected' : '' }}
                                    style="color: {{ $category->formatted_color }}">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($bookmark)
                    <div class="space-y-2 pt-2 border-t border-gray-200">
                        <button type="button"
                                data-action="click->bookmark#editNotes"
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit notes
                        </button>

                        <button type="button"
                                data-action="click->bookmark#toggleFavorite"
                                class="w-full text-left px-3 py-2 text-sm {{ $bookmark->is_favorite ? 'text-yellow-600' : 'text-gray-700' }} hover:bg-gray-100 rounded-md">
                            <svg class="w-4 h-4 inline mr-2" fill="{{ $bookmark->is_favorite ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            {{ $bookmark->is_favorite ? 'Remove from favorites' : 'Add to favorites' }}
                        </button>

                        <button type="button"
                                data-action="click->bookmark#archive"
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                            {{ $bookmark->is_archived ? 'Unarchive' : 'Archive' }}
                        </button>

                        <button type="button"
                                data-action="click->bookmark#delete"
                                class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete bookmark
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
// Stimulus controller for bookmark functionality
class BookmarkController {
    constructor(element) {
        this.element = element;
        this.targets = {
            button: element.querySelector('[data-bookmark-target="button"]'),
            icon: element.querySelector('[data-bookmark-target="icon"]'),
            text: element.querySelector('[data-bookmark-target="text"]'),
            dropdown: element.querySelector('[data-bookmark-target="dropdown"]'),
            categorySelect: element.querySelector('[data-bookmark-target="categorySelect"]')
        };
        
        this.articleId = element.dataset.articleId;
        this.isBookmarked = element.dataset.isBookmarked === 'true';
        this.bookmarkId = element.dataset.bookmarkId;
        this.categoryId = element.dataset.categoryId;
        
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.element.contains(e.target) && this.targets.dropdown) {
                this.hideDropdown();
            }
        });

        // Handle category change
        if (this.targets.categorySelect) {
            this.targets.categorySelect.addEventListener('change', (e) => {
                this.updateCategory(e.target.value);
            });
        }
    }

    toggle(e) {
        e.preventDefault();
        
        if (this.isBookmarked && this.targets.dropdown) {
            this.toggleDropdown();
        } else {
            this.toggleBookmark();
        }
    }

    async toggleBookmark() {
        try {
            const url = this.isBookmarked 
                ? `/api/bookmarks/${this.bookmarkId}`
                : '/api/bookmarks';
            
            const method = this.isBookmarked ? 'DELETE' : 'POST';
            
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: this.isBookmarked ? null : JSON.stringify({
                    article_id: this.articleId,
                    category_id: this.categoryId
                })
            });

            const data = await response.json();

            if (data.success) {
                this.isBookmarked = !this.isBookmarked;
                
                if (this.isBookmarked) {
                    this.bookmarkId = data.data.id;
                    this.showBookmarkedState();
                } else {
                    this.showUnbookmarkedState();
                }
                
                // Show success message
                this.showNotification(data.message, 'success');
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Bookmark operation failed:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        }
    }

    showBookmarkedState() {
        this.element.dataset.isBookmarked = 'true';
        this.element.dataset.bookmarkId = this.bookmarkId;
        
        if (this.targets.icon) {
            this.targets.icon.setAttribute('fill', 'currentColor');
        }
        
        if (this.targets.text) {
            this.targets.text.textContent = 'Saved';
        }
        
        if (this.targets.button) {
            this.targets.button.className = this.targets.button.className
                .replace(/bg-gray-\d+|text-gray-\d+/g, '')
                .replace(/hover:bg-gray-\d+/g, '')
                .trim() + ' bg-yellow-100 text-yellow-800 hover:bg-yellow-200';
        }
    }

    showUnbookmarkedState() {
        this.element.dataset.isBookmarked = 'false';
        this.element.dataset.bookmarkId = '';
        
        if (this.targets.icon) {
            this.targets.icon.setAttribute('fill', 'none');
        }
        
        if (this.targets.text) {
            this.targets.text.textContent = 'Save';
        }
        
        if (this.targets.button) {
            this.targets.button.className = this.targets.button.className
                .replace(/bg-yellow-\d+|text-yellow-\d+/g, '')
                .replace(/hover:bg-yellow-\d+/g, '')
                .trim() + ' bg-gray-100 text-gray-700 hover:bg-gray-200';
        }
        
        this.hideDropdown();
    }

    toggleDropdown() {
        if (!this.targets.dropdown) return;
        
        const isHidden = this.targets.dropdown.classList.contains('hidden');
        
        if (isHidden) {
            this.showDropdown();
        } else {
            this.hideDropdown();
        }
    }

    showDropdown() {
        if (this.targets.dropdown) {
            this.targets.dropdown.classList.remove('hidden');
        }
    }

    hideDropdown() {
        if (this.targets.dropdown) {
            this.targets.dropdown.classList.add('hidden');
        }
    }

    async updateCategory(categoryId) {
        if (!this.bookmarkId) return;
        
        try {
            const response = await fetch(`/api/bookmarks/${this.bookmarkId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ category_id: categoryId })
            });

            const data = await response.json();

            if (data.success) {
                this.element.dataset.categoryId = categoryId;
                this.showNotification('Category updated successfully', 'success');
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Category update failed:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        }
    }

    editNotes() {
        // Implement notes editing functionality
        const currentNotes = prompt('Edit bookmark notes:');
        if (currentNotes !== null) {
            this.updateNotes(currentNotes);
        }
    }

    async updateNotes(notes) {
        if (!this.bookmarkId) return;
        
        try {
            const response = await fetch(`/api/bookmarks/${this.bookmarkId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ notes: notes })
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Notes updated successfully', 'success');
            } else {
                this.showNotification(data.message, 'error');
            }
        } catch (error) {
            console.error('Notes update failed:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        }
    }

    toggleFavorite() {
        if (!this.bookmarkId) return;
        
        fetch(`/api/bookmarks/${this.bookmarkId}/favorite`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showNotification(data.message, 'success');
            } else {
                this.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Favorite toggle failed:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        });
    }

    archive() {
        if (!this.bookmarkId) return;
        
        const action = confirm('Are you sure you want to archive this bookmark?');
        if (action) {
            fetch(`/api/bookmarks/${this.bookmarkId}/archive`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showNotification(data.message, 'success');
                    this.toggleBookmark(); // Remove from current view
                } else {
                    this.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Archive failed:', error);
                this.showNotification('An error occurred. Please try again.', 'error');
            });
        }
    }

    delete() {
        if (!this.bookmarkId) return;
        
        const action = confirm('Are you sure you want to delete this bookmark?');
        if (action) {
            this.toggleBookmark(); // This will delete the bookmark
        }
    }

    showNotification(message, type) {
        // Implement notification system
        console.log(`${type}: ${message}`);
        
        // You can replace this with your preferred notification library
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
}

// Initialize controllers
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-controller="bookmark"]').forEach(element => {
        new BookmarkController(element);
    });
});
</script>
@endpush