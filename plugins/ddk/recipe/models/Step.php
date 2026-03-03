<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;

class Step extends Model
{
    use \Winter\Storm\Database\Traits\Validation;

    public $table = 'ddk_recipe_steps';

    public $rules = [
        'step_number' => 'required|integer|min:1',
        'description' => 'required',
    ];

    protected $guarded = ['id'];

    protected $fillable = [
        'recipe_id',
        'step_number',
        'title',
        'description',
    ];

    public $belongsTo = [
        'recipe' => [Recipe::class, 'key' => 'recipe_id'],
    ];
}
