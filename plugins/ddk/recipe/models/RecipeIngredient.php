<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;

class RecipeIngredient extends Model
{
    public $table = 'ddk_recipe_ingredients_map';

    protected $guarded = ['id'];

    protected $fillable = [
        'recipe_id',
        'ingredient_id',
        'unit_id',
        'quantity',
        'sort_order',
        'note',
    ];

    public $belongsTo = [
        'recipe' => [Recipe::class, 'key' => 'recipe_id'],
        'ingredient' => [Ingredient::class, 'key' => 'ingredient_id'],
        'unit' => [Unit::class, 'key' => 'unit_id'],
    ];

    /**
     * Get ingredient options for dropdown
     */
    public function getIngredientIdOptions()
    {
        return Ingredient::pluck('title', 'id')->toArray();
    }

    /**
     * Get unit options for dropdown
     */
    public function getUnitIdOptions()
    {
        return ['' => '-- None --'] + Unit::pluck('title', 'id')->toArray();
    }
}
