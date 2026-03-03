<?php

namespace Ddk\Recipe\Components;

use Cms\Classes\ComponentBase;
use Ddk\Recipe\Models\Category;

class CategoryFilter extends ComponentBase
{
    /**
     * The categories tree
     */
    public $categories;

    public function componentDetails()
    {
        return [
            'name' => 'Category Filter',
            'description' => 'Displays a hierarchical category filter for recipes'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->categories = $this->page['categories'] = $this->loadCategories();
    }

    /**
     * Load categories as a nested tree structure
     */
    protected function loadCategories()
    {
        // Get all root categories (level 1) with their children
        return Category::whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(function ($category) {
                return $this->formatCategory($category, 1);
            });
    }

    /**
     * Format a category with its children recursively
     */
    protected function formatCategory($category, $level)
    {
        $children = $category->children()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(function ($child) use ($level) {
                return $this->formatCategory($child, $level + 1);
            });

        return [
            'id' => $category->id,
            'title' => $category->title,
            'slug' => $category->slug,
            'level' => $level,
            'children' => $children,
            'has_children' => $children->isNotEmpty(),
        ];
    }

    /**
     * Get emoji for top-level categories based on slug/title
     */
    public static function getCategoryEmoji($slug)
    {
        $emojiMap = [
            'backen-suessspeisen' => '🍰',
            'backen' => '🍰',
            'zubereitungsarten' => '👨‍🍳',
            'saisonal' => '🌸',
            'spezielles' => '⭐',
            'regional' => '🌍',
            'getraenke' => '🍹',
            'menueart' => '🍽️',
            'menu' => '🍽️',
        ];

        return $emojiMap[$slug] ?? '📂';
    }
}
