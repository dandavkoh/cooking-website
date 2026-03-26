<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;
use Winter\Storm\Support\Str;
use Winter\User\Models\User;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Winter\Storm\Support\Facades\Url;

class Recipe extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'ddk_recipes';

    public $rules = [
        'title' => 'required|max:255',
        'slug' => 'required|max:255',
        'public_id' => 'required|max:16|unique:ddk_recipes',
        'portions' => 'nullable|integer|min:1',
        'difficulty' => 'required|in:easy,medium,hard',
        'working_time_minutes' => 'nullable|integer|min:0',
        'cooking_time_minutes' => 'nullable|integer|min:0',
    ];

    protected $guarded = ['id'];

    protected $fillable = [
        'public_id',
        'title',
        'slug',
        'subtitle',
        'description',
        'portions',
        'difficulty',
        'working_time_minutes',
        'cooking_time_minutes',
        'author_id',
        'is_published',
        'published_at',
        'tip',
    ];

    protected $dates = ['published_at'];

    public $belongsTo = [
        'author' => [\Winter\User\Models\User::class, 'key' => 'author_id'],
    ];

    public $hasMany = [
        'steps' => [Step::class, 'key' => 'recipe_id', 'order' => 'step_number'],
        'recipe_ingredients' => [RecipeIngredient::class, 'key' => 'recipe_id', 'order' => 'sort_order'],
    ];

    public $belongsToMany = [
        'categories' => [
            Category::class,
            'table' => 'ddk_recipe_categories_map',
            'key' => 'recipe_id',
            'otherKey' => 'category_id',
        ],
        'ingredients' => [
            Ingredient::class,
            'table' => 'ddk_recipe_ingredients_map',
            'key' => 'recipe_id',
            'otherKey' => 'ingredient_id',
            'pivot' => ['quantity', 'unit_id', 'sort_order', 'note'],
        ],
    ];

    public $attachMany = [
        'images' => [
            \System\Models\File::class,
            'order' => 'sort_order',
        ],
    ];

    public function beforeValidate()
    {
        // Auto-generate public_id (Snowflake-like: timestamp in microseconds)
        if (!$this->exists && !$this->public_id) {
            $this->public_id = $this->generatePublicId();
        }

        // Auto-generate slug from title
        if (!$this->exists && !$this->slug) {
            $this->slug = Str::slug($this->title);
        }
    }

    /**
     * Generate a unique public ID (16 digits, Snowflake-like)
     * Format: Timestamp in milliseconds (13 digits) + random (3 digits)
     */
    public static function generatePublicId(): string
    {
        return sprintf('%013d%03d', floor(microtime(true) * 1000), random_int(0, 999));
    }

    /**
     * Get the URL for this recipe
     */
    public function getUrlAttribute(): string
    {
        return '/rezept/' . $this->public_id . '/' . $this->slug;
    }

    /**
     * Get difficulty options for dropdown
     */
    public function getDifficultyOptions()
    {
        return [
            'easy' => trans('ddk.recipe::lang.recipe.difficulty_easy'),
            'medium' => trans('ddk.recipe::lang.recipe.difficulty_medium'),
            'hard' => trans('ddk.recipe::lang.recipe.difficulty_hard'),
        ];
    }

    /**
     * Scope for published recipes
     */
    public function scopeIsPublished($query)
    {
        return $query
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Search recipes with fuzzy matching
     * Uses SOUNDEX for phonetic matching and LIKE for partial matches
     */
    public function scopeSearch($query, string $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $searchTerm = trim($searchTerm);

        return $query->where(function ($q) use ($searchTerm) {
            // Exact match
            $q->where('title', 'LIKE', "%{$searchTerm}%")
                // SOUNDEX for phonetic similarity (handles spelling errors like pageti -> spaghetti)
                ->orWhereRaw('SOUNDEX(title) = SOUNDEX(?)', [$searchTerm])
                // Also try matching by removing common prefixes/suffixes
                ->orWhere('title', 'LIKE', substr($searchTerm, 0, 3) . '%');
        })
            ->orderByRaw("
                CASE
                    WHEN title LIKE ? THEN 1
                    WHEN title LIKE ? THEN 2
                    WHEN SOUNDEX(title) = SOUNDEX(?) THEN 3
                    ELSE 4
                END
            ", ["{$searchTerm}", "%{$searchTerm}%", $searchTerm]);
    }

    /**
     * Get total time in minutes
     */
    public function getTotalTimeAttribute()
    {
        return ($this->working_time_minutes ?? 0) + ($this->cooking_time_minutes ?? 0);
    }

    /**
     * Get ingredient options for repeater dropdown
     */
    public function getIngredientIdOptions()
    {
        return Ingredient::orderBy('title')->pluck('title', 'id')->toArray();
    }

    /**
     * Get unit options for repeater dropdown
     */
    public function getUnitIdOptions()
    {
        return ['' => '-- ' . trans('ddk.recipe::lang.recipe.no_entry') . ' --'] + Unit::orderBy('title')->pluck('title', 'id')->toArray();
    }

    /**
     * Get author options for dropdown - only users from 'recipe' group
     */
    public function getAuthorIdOptions()
    {
        return ['' => '-- ' . trans('ddk.recipe::lang.recipe.no_entry') . ' --'] +
            User::whereHas('groups', function ($query) {
                $query->where('code', 'recipe');
            })
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($user) {
                return [$user->id => $user->name . ' (' . $user->email . ')'];
            })
            ->toArray();
    }

    /**
     * Returns a list of menu item types compatible with the Sitemap menu
     * Returns null if the type is not supported
     */
    public static function getMenuTypeInfo($type): array
    {
        $result = [];
        
        if ($type == 'recipe-post') {
            $references = [];
            $recipes = self::isPublished()->orderBy('title')->get();
            foreach ($recipes as $recipe) {
                $references[$recipe->id] = $recipe->title;
            }
            
            $result = [
                'references' => $references,
                'nesting' => false,
                'dynamicItems' => false,
            ];
        }
        
        if ($type == 'all-recipe-posts') {
            $result = [
                'dynamicItems' => true,
            ];
        }
        
        if ($type == 'category-recipe-posts') {
            $references = [];
            $categories = Category::orderBy('title')->get();
            foreach ($categories as $category) {
                $references[$category->id] = $category->title;
            }
            
            $result = [
                'references' => $references,
                'dynamicItems' => true,
            ];
        }
        
        if ($result) {
            $result['cmsPages'] = self::getCmsPageOptions();
        }
        
        return $result;
    }

    /**
     * Fetches the dynamic menu items (like all recipes or recipes in a category)
     * from the models and returns them as a structured array for the Sitemap
     */
    public static function resolveMenuItem($item, $url, $theme, ?int $referenceKey = null): ?array
    {
        $result = null;

        if (!class_exists(CmsPage::class) || !class_exists(Theme::class)) {
            return $result;
        }

        if ($item->type == 'recipe-post') {
            if (!$item->reference) {
                return null;
            }

            $recipe = self::isPublished()->find($item->reference);
            if (!$recipe) {
                return null;
            }

            $pageUrl = self::getLocalizedUrl($recipe, $item->cmsPage);
            if (!$pageUrl) {
                return null;
            }

            $pageUrl = Url::to($pageUrl);
            $result = [
                'url' => $pageUrl,
                'isActive' => $pageUrl === $url,
                'mtime' => $recipe->updated_at,
            ];
        } elseif ($item->type == 'all-recipe-posts') {
            $result = [
                'items' => [],
            ];

            $recipes = self::isPublished()->orderBy('title')->get();
            foreach ($recipes as $recipe) {
                $recipeItem = [
                    'title' => $recipe->title,
                    'url' => Url::to($recipe->url),
                    'mtime' => $recipe->updated_at,
                ];

                $recipeItem['isActive'] = $recipeItem['url'] === $url;
                
                $result['items'][] = $recipeItem;
            }
        } elseif ($item->type == 'category-recipe-posts') {
            if (!$item->reference) {
                return null;
            }

            $category = Category::find($item->reference);
            if (!$category) {
                return null;
            }

            $result = [
                'items' => [],
            ];

            $recipes = $category->recipes()->isPublished()->orderBy('title')->get();
            foreach ($recipes as $recipe) {
                $recipeItem = [
                    'title' => $recipe->title,
                    'url' => Url::to($recipe->url),
                    'mtime' => $recipe->updated_at,
                ];

                $recipeItem['isActive'] = $recipeItem['url'] === $url;
                
                $result['items'][] = $recipeItem;
            }
        }

        return $result;
    }

    /**
     * Helper method to find CMS pages that display recipes
     * similar to how Blog plugin does it
     */
    protected static function getCmsPageOptions(): array
    {
        if (!class_exists(CmsPage::class) || !class_exists(Theme::class)) {
            return [];
        }

        $theme = Theme::getActiveTheme();
        $pages = CmsPage::listInTheme($theme, true);
        
        $cmsPages = [];
        foreach ($pages as $page) {
            if (!$page->hasComponent('recipeDetail') && !$page->hasComponent('recipeList')) {
                continue;
            }

            $cmsPages[] = $page;
        }

        return $cmsPages;
    }

    /**
     * Generates a URL for a specific recipe on a customised recipe page
     * This is similar to the blog post URL generation
     */
    protected static function getLocalizedUrl($recipe, $cmsPage): ?string
    {
        if (!class_exists(CmsPage::class) || !class_exists(Theme::class)) {
            return $recipe->url;
        }

        $page = CmsPage::loadCached(Theme::getActiveTheme(), $cmsPage);
        if (!$page) {
            return $recipe->url;
        }

        $properties = $page->getComponentProperties(\Ddk\Recipe\Components\RecipeDetail::class);
        if (!isset($properties['publicId'])) {
            return $recipe->url;
        }

        // Check if the published post page uses any routing parameters
        if (!preg_match('/^.*{{\s*publicId\s*}}.*$/', $properties['publicId'])) {
            return $recipe->url;
        }

        // Extract the routing parameter name from the filter
        // The format is "{{ :public_id }}"
        $matches = [];
        $pattern = '|^.*{{\s*:([^\}]+)\s*}}.*$|i';
        if (!preg_match($pattern, $properties['publicId'], $matches)) {
            return $recipe->url;
        }

        if (!isset($matches[1])) {
            return $recipe->url;
        }

        $paramName = trim($matches[1], "'");
        
        $url = CmsPage::url($page->getFileName(), [$paramName => $recipe->public_id]);
        
        return $url ?: $recipe->url;
    }
}
