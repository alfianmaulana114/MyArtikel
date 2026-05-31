<?php

namespace App\View\Components;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use Illuminate\View\Component;
use Illuminate\View\View;

class BookmarkButton extends Component
{
    public $article;

    public $bookmark;

    public $isBookmarked;

    public $categories;

    public $showCategories;

    public $size;

    public $variant;

    /**
     * Create a new component instance.
     *
     * @param  bool  $showCategories
     * @param  string  $size
     * @param  string  $variant
     */
    public function __construct(
        Article $article,
        $showCategories = true,
        $size = 'md',
        $variant = 'default'
    ) {
        $this->article = $article;
        $this->showCategories = $showCategories;
        $this->size = $size;
        $this->variant = $variant;

        // Check if article is bookmarked by current user
        if (auth()->check()) {
            $this->bookmark = Bookmark::with(['category'])
                ->where('user_id', auth()->id())
                ->where('article_id', $article->id)
                ->first();

            $this->isBookmarked = ! is_null($this->bookmark);
            $this->categories = $showCategories ? $this->getUserCategories() : collect();
        } else {
            $this->isBookmarked = false;
            $this->bookmark = null;
            $this->categories = collect();
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        return view('components.bookmark-button');
    }

    /**
     * Get user categories for dropdown.
     */
    private function getUserCategories()
    {
        return BookmarkCategory::forUser(auth()->id())
            ->orderBy('position')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get button classes based on size and variant.
     */
    public function getButtonClasses()
    {
        $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';

        $sizeClasses = [
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-sm',
            'lg' => 'px-6 py-3 text-base',
        ];

        $variantClasses = $this->isBookmarked
            ? $this->getBookmarkedVariantClasses()
            : $this->getUnbookmarkedVariantClasses();

        return $baseClasses.' '.($sizeClasses[$this->size] ?? $sizeClasses['md']).' '.$variantClasses;
    }

    /**
     * Get classes for bookmarked state.
     */
    private function getBookmarkedVariantClasses()
    {
        $variants = [
            'default' => 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 focus:ring-yellow-500',
            'outline' => 'border-2 border-yellow-500 text-yellow-700 hover:bg-yellow-50 focus:ring-yellow-500',
            'ghost' => 'text-yellow-600 hover:bg-yellow-50 focus:ring-yellow-500',
            'solid' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500',
        ];

        return $variants[$this->variant] ?? $variants['default'];
    }

    /**
     * Get classes for unbookmarked state.
     */
    private function getUnbookmarkedVariantClasses()
    {
        $variants = [
            'default' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-500',
            'outline' => 'border-2 border-gray-300 text-gray-600 hover:bg-gray-50 focus:ring-gray-500',
            'ghost' => 'text-gray-500 hover:bg-gray-50 focus:ring-gray-500',
            'solid' => 'bg-gray-500 text-white hover:bg-gray-600 focus:ring-gray-500',
        ];

        return $variants[$this->variant] ?? $variants['default'];
    }

    /**
     * Get icon classes.
     */
    public function getIconClasses()
    {
        $sizeClasses = [
            'sm' => 'w-4 h-4',
            'md' => 'w-5 h-5',
            'lg' => 'w-6 h-6',
        ];

        return $sizeClasses[$this->size] ?? $sizeClasses['md'];
    }

    /**
     * Get button text.
     */
    public function getButtonText()
    {
        if (! $this->showCategories) {
            return '';
        }

        return $this->isBookmarked ? 'Saved' : 'Save';
    }

    /**
     * Get tooltip text.
     */
    public function getTooltipText()
    {
        if ($this->isBookmarked) {
            return $this->bookmark && $this->bookmark->category
                ? "Saved in {$this->bookmark->category->name}"
                : 'Saved to bookmarks';
        }

        return 'Save to bookmarks';
    }

    /**
     * Get data attributes for JavaScript.
     */
    public function getDataAttributes()
    {
        return [
            'article-id' => $this->article->id,
            'is-bookmarked' => $this->isBookmarked ? 'true' : 'false',
            'bookmark-id' => $this->bookmark ? $this->bookmark->id : null,
            'category-id' => $this->bookmark && $this->bookmark->category ? $this->bookmark->category->id : null,
        ];
    }
}
