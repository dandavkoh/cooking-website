<?php

namespace Ddk\Recipe\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

class Categories extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\ReorderController::class,
    ];

    public $requiredPermissions = ['ddk.recipe.access_categories'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Ddk.Recipe', 'recipe', 'categories');
    }
}
