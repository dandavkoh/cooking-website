<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;
use Winter\Storm\Support\Str;

class Category extends Model
{
    use \Winter\Storm\Database\Traits\NestedTree;
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    public $table = 'ddk_recipe_categories';

    public $rules = [
        'title' => 'required|max:255',
        'slug' => 'required|max:255|unique:ddk_recipe_categories',
    ];

    protected $guarded = ['id'];

    protected $fillable = ['title', 'slug', 'parent_id', 'sort_order'];

    public $belongsToMany = [
        'recipes' => [
            Recipe::class,
            'table' => 'ddk_recipe_categories_map',
            'key' => 'category_id',
            'otherKey' => 'recipe_id',
        ],
    ];

    public function beforeValidate()
    {
        if (!$this->exists && !$this->slug) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function beforeSave()
    {
        // Convert empty string to null for parent_id
        if ($this->parent_id === '' || $this->parent_id === 0) {
            $this->parent_id = null;
        }
    }

    /**
     * Get options for parent category dropdown
     */
    public function getParentIdOptions()
    {
        $options = ['' => trans('ddk.recipe::lang.recipe.no_parent')];

        $categories = static::where('id', '!=', $this->id ?: 0)
            ->orderBy('nest_left')
            ->get();

        foreach ($categories as $category) {
            $prefix = str_repeat('— ', $category->nest_depth);
            $options[$category->id] = $prefix . $category->title;
        }

        return $options;
    }
}
