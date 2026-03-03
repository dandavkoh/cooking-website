<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;

class Ingredient extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'ddk_recipe_ingredients';

    public $rules = [
        'title' => 'required|max:255',
    ];

    protected $guarded = ['id'];

    protected $fillable = ['title'];

    public $hasMany = [
        'recipe_ingredients' => [
            RecipeIngredient::class,
            'key' => 'ingredient_id',
        ],
    ];

    public $belongsToMany = [
        'recipes' => [
            Recipe::class,
            'table' => 'ddk_recipe_ingredients_map',
            'key' => 'ingredient_id',
            'otherKey' => 'recipe_id',
        ],
    ];
}
