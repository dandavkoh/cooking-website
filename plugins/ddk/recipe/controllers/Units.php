<?php

namespace Ddk\Recipe\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;

class Units extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $requiredPermissions = ['ddk.recipe.access_units'];

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Ddk.Recipe', 'recipe', 'units');
    }
}
