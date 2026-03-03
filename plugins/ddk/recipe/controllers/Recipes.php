<?php

namespace Ddk\Recipe\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Ddk\Recipe\Models\Recipe;

class Recipes extends Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
        \Backend\Behaviors\RelationController::class,
    ];

    public $requiredPermissions = ['ddk.recipe.access_recipes'];

    public $relationConfig = 'config_relation.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('Ddk.Recipe', 'recipe', 'recipes');
    }

    public function index()
    {
        $this->vars['recipesTotal'] = Recipe::count();
        $this->vars['recipesPublished'] = Recipe::isPublished()->count();
        $this->vars['recipesDrafts'] = $this->vars['recipesTotal'] - $this->vars['recipesPublished'];

        $this->asExtension('ListController')->index();
    }

    public function create()
    {
        $this->bodyClass = 'compact-container';
        return $this->asExtension('FormController')->create();
    }

    public function update($recordId = null)
    {
        $this->bodyClass = 'compact-container';
        return $this->asExtension('FormController')->update($recordId);
    }

    /**
     * Extend the form to handle repeater data for ingredients and steps
     */
    public function formAfterSave($model)
    {
        // Handle recipe ingredients from repeater
        $ingredientsData = post('Recipe._ingredients') ?? post('Recipe')['_ingredients'] ?? [];
        if (!empty($ingredientsData)) {
            $model->recipe_ingredients()->delete();
            $sortOrder = 0;
            foreach ($ingredientsData as $data) {
                if (!empty($data['ingredient_id'])) {
                    $model->recipe_ingredients()->create([
                        'ingredient_id' => $data['ingredient_id'],
                        'unit_id' => !empty($data['unit_id']) ? $data['unit_id'] : null,
                        'quantity' => !empty($data['quantity']) ? $data['quantity'] : null,
                        'note' => $data['note'] ?? null,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }
        }

        // Handle steps from repeater
        $stepsData = post('Recipe._steps') ?? post('Recipe')['_steps'] ?? [];
        if (!empty($stepsData)) {
            $model->steps()->delete();
            $stepNumber = 1;
            foreach ($stepsData as $data) {
                if (!empty($data['description'])) {
                    $model->steps()->create([
                        'step_number' => !empty($data['step_number']) ? $data['step_number'] : $stepNumber,
                        'title' => $data['title'] ?? null,
                        'description' => $data['description'],
                    ]);
                    $stepNumber++;
                }
            }
        }
    }

    /**
     * Extend the form to load existing repeater data
     */
    public function formExtendFields($form)
    {
        if (!$form->model->exists) {
            return;
        }

        // Load existing recipe ingredients
        $ingredients = $form->model->recipe_ingredients()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($item) {
                return [
                    'ingredient_id' => $item->ingredient_id,
                    'unit_id' => $item->unit_id,
                    'quantity' => $item->quantity,
                    'note' => $item->note,
                ];
            })
            ->toArray();

        if (!empty($ingredients)) {
            $form->model->_ingredients = $ingredients;
        }

        // Load existing steps
        $steps = $form->model->steps()
            ->orderBy('step_number')
            ->get()
            ->map(function ($item) {
                return [
                    'step_number' => $item->step_number,
                    'title' => $item->title,
                    'description' => $item->description,
                ];
            })
            ->toArray();

        if (!empty($steps)) {
            $form->model->_steps = $steps;
        }
    }
}
