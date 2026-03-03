<?php

namespace Ddk\Recipe\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

class Ingredients extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $requiredPermissions = ['ddk.recipe.access_ingredients'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Ddk.Recipe', 'recipe', 'ingredients');
    }
}
