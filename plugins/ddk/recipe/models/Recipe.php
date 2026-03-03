<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;
use Winter\Storm\Support\Str;

class Recipe extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'ddk_recipes';

    public $rules = [
        'title' => 'required|max:255',
        'slug' => 'required|max:255|unique:ddk_recipes',
        'portions' => 'nullable|integer|min:1',
        'difficulty' => 'required|in:easy,medium,hard',
        'working_time_minutes' => 'nullable|integer|min:0',
        'cooking_time_minutes' => 'nullable|integer|min:0',
    ];

    protected $guarded = ['id'];

    protected $fillable = [
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
        if (!$this->exists && !$this->slug) {
            $this->slug = Str::slug($this->title);
        }
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
}
