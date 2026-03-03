<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;

class Unit extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'ddk_recipe_units';

    public $rules = [
        'title' => 'required|max:255',
    ];

    protected $guarded = ['id'];

    protected $fillable = ['title'];

    public $hasMany = [
        'recipe_ingredients' => [
            RecipeIngredient::class,
            'key' => 'unit_id',
        ],
    ];
}
