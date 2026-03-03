<?php

namespace Ddk\Recipe\Components;

use Cms\Classes\ComponentBase;
use Ddk\Recipe\Models\Category;
use Ddk\Recipe\Models\Recipe;
use Illuminate\Pagination\LengthAwarePaginator;

class RecipeList extends ComponentBase
{
    /**
     * Number of recipes per page
     */
    public const PER_PAGE = 25;

    /**
     * The recipes collection (paginated)
     */
    public $recipes;

    /**
     * Total recipe count
     */
    public $totalCount;

    /**
     * The categories tree
     */
    public $categories;

    /**
     * Active filters
     */
    public $activeFilters = [];

    /**
     * Current sort option
     */
    public $currentSort = 'neueste';

    /**
     * Current difficulty filter
     */
    public $currentDifficulty = '';

    /**
     * Current max duration filter
     */
    public $currentDuration = '';

    /**
     * Selected category IDs
     */
    public $selectedCategories = [];

    /**
     * Current page
     */
    public $currentPage = 1;

    /**
     * Current search term
     */
    public $searchTerm = '';

    public function componentDetails()
    {
        return [
            'name' => 'Recipe List',
            'description' => 'Displays filterable, sortable, paginated recipe list'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->loadFiltersFromRequest();
        $this->categories = $this->page['categories'] = $this->loadCategories();
        $this->recipes = $this->page['recipes'] = $this->loadRecipes();
        $this->totalCount = $this->page['totalCount'] = $this->recipes->total();
        
        // Pass filter state to page
        $this->page['activeFilters'] = $this->activeFilters;
        $this->page['currentSort'] = $this->currentSort;
        $this->page['currentDifficulty'] = $this->currentDifficulty;
        $this->page['currentDuration'] = $this->currentDuration;
        $this->page['selectedCategories'] = $this->selectedCategories;
        $this->page['currentPage'] = $this->currentPage;
        $this->page['searchTerm'] = $this->searchTerm;
    }

    /**
     * Load filters from GET parameters
     */
    protected function loadFiltersFromRequest()
    {
        $this->currentSort = request()->get('sort', 'neueste');
        $this->currentDifficulty = request()->get('difficulty', '');
        $this->currentDuration = request()->get('duration', '');
        $this->currentPage = (int) request()->get('page', 1);
        $this->searchTerm = trim(request()->get('q', ''));
        
        // Categories can be comma-separated IDs
        $categoryParam = request()->get('categories', '');
        if ($categoryParam) {
            $this->selectedCategories = array_map('intval', explode(',', $categoryParam));
        }

        // Build active filters array for display
        $this->buildActiveFilters();
    }

    /**
     * Build array of active filters for display
     */
    protected function buildActiveFilters()
    {
        $this->activeFilters = [];

        // Search filter
        if ($this->searchTerm) {
            $this->activeFilters[] = [
                'type' => 'search',
                'value' => $this->searchTerm,
                'label' => 'Suche: "' . $this->searchTerm . '"',
            ];
        }

        // Difficulty filter
        if ($this->currentDifficulty) {
            $labels = [
                'easy' => 'Einfach',
                'medium' => 'Mittel',
                'hard' => 'Schwer',
            ];
            $this->activeFilters[] = [
                'type' => 'difficulty',
                'value' => $this->currentDifficulty,
                'label' => $labels[$this->currentDifficulty] ?? $this->currentDifficulty,
            ];
        }

        // Duration filter
        if ($this->currentDuration) {
            $durationLabels = [
                '15' => 'Max. 15 Min',
                '30' => 'Max. 30 Min',
                '60' => 'Max. 60 Min',
                '120+' => 'Über 120 Min',
            ];
            $this->activeFilters[] = [
                'type' => 'duration',
                'value' => $this->currentDuration,
                'label' => $durationLabels[$this->currentDuration] ?? $this->currentDuration,
            ];
        }

        // Category filters
        if (!empty($this->selectedCategories)) {
            $categoryNames = Category::whereIn('id', $this->selectedCategories)
                ->pluck('title', 'id')
                ->toArray();
            
            foreach ($this->selectedCategories as $catId) {
                if (isset($categoryNames[$catId])) {
                    $this->activeFilters[] = [
                        'type' => 'category',
                        'value' => $catId,
                        'label' => $categoryNames[$catId],
                    ];
                }
            }
        }
    }

    /**
     * Load recipes with filters, sorting, and pagination
     */
    protected function loadRecipes()
    {
        $query = Recipe::isPublished()
            ->with(['categories', 'images']);

        // Apply search filter
        if ($this->searchTerm) {
            $query->search($this->searchTerm);
        }

        // Apply difficulty filter
        if ($this->currentDifficulty) {
            $query->where('difficulty', $this->currentDifficulty);
        }

        // Apply duration filter (total time = working_time_minutes + cooking_time_minutes)
        if ($this->currentDuration) {
            if ($this->currentDuration === '120+') {
                // More than 120 minutes
                $query->whereRaw('(COALESCE(working_time_minutes, 0) + COALESCE(cooking_time_minutes, 0)) >= 120');
            } elseif (is_numeric($this->currentDuration)) {
                $maxDuration = (int) $this->currentDuration;
                $query->whereRaw('(COALESCE(working_time_minutes, 0) + COALESCE(cooking_time_minutes, 0)) <= ?', [$maxDuration]);
            }
        }

        // Apply category filter (AND logic - recipe must have ALL selected categories)
        if (!empty($this->selectedCategories)) {
            foreach ($this->selectedCategories as $categoryId) {
                $query->whereHas('categories', function ($q) use ($categoryId) {
                    $q->where('ddk_recipe_categories.id', $categoryId);
                });
            }
        }

        // Apply sorting
        switch ($this->currentSort) {
            case 'schnell':
                // Fastest first (total time ascending)
                $query->orderByRaw('(COALESCE(working_time_minutes, 0) + COALESCE(cooking_time_minutes, 0)) ASC');
                break;
            case 'neueste':
            case 'beliebt':
            default:
                // For now, both use newest ID
                $query->orderBy('id', 'desc');
                break;
        }

        return $query->paginate(self::PER_PAGE, ['*'], 'page', $this->currentPage);
    }

    /**
     * Load categories as a nested tree structure
     */
    protected function loadCategories()
    {
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
            'selected' => in_array($category->id, $this->selectedCategories),
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

    /**
     * Get difficulty label in German
     */
    public static function getDifficultyLabel($difficulty)
    {
        $labels = [
            'easy' => 'Einfach',
            'medium' => 'Mittel',
            'hard' => 'Schwer',
        ];

        return $labels[$difficulty] ?? $difficulty;
    }

    /**
     * Get difficulty CSS class
     */
    public static function getDifficultyClass($difficulty)
    {
        $classes = [
            'easy' => 'bg-secondary/10 text-secondary',
            'medium' => 'bg-accent/20 text-accent',
            'hard' => 'bg-red-100 text-red-600',
        ];

        return $classes[$difficulty] ?? 'bg-gray-100 text-gray-600';
    }
}
