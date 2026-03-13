<?php

namespace Ddk\Recipe\Components;

use Cms\Classes\ComponentBase;
use Ddk\Recipe\Models\Recipe;

class RecipeDetail extends ComponentBase
{
    /**
     * The recipe model
     */
    public $recipe;

    /**
     * Related recipes
     */
    public $relatedRecipes;

    public function componentDetails()
    {
        return [
            'name' => 'Recipe Detail',
            'description' => 'Displays a single recipe with all details'
        ];
    }

    public function defineProperties()
    {
        return [
            'slug' => [
                'title' => 'Recipe Slug',
                'description' => 'The URL slug of the recipe',
                'default' => '{{ :slug }}',
                'type' => 'string',
            ],
        ];
    }

    public function onRun()
    {
        $this->recipe = $this->page['recipe'] = $this->loadRecipe();

        if (!$this->recipe) {
            $this->setStatusCode(404);
            return $this->controller->run('404');
        }

        $this->page->title = $this->recipe->title;

        // Load related recipes
        $this->relatedRecipes = $this->page['relatedRecipes'] = $this->loadRelatedRecipes();
    }

    /**
     * Load random related recipes (max 4, excluding current recipe)
     */
    protected function loadRelatedRecipes()
    {
        if (!$this->recipe) {
            return collect([]);
        }

        return Recipe::with(['images'])
            ->isPublished()
            ->where('id', '!=', $this->recipe->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();
    }

    /**
     * Load the recipe by slug with all relations
     */
    protected function loadRecipe()
    {
        $slug = $this->property('slug');

        if (!$slug) {
            return null;
        }

        return Recipe::with([
            'categories',
            'steps',
            'recipe_ingredients.ingredient',
            'recipe_ingredients.unit',
            'images',
            'author',
        ])
            ->isPublished()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Get difficulty label in German
     */
    public static function getDifficultyLabel($difficulty)
    {
        $labels = [
            'easy' => 'Einfach',
            'medium' => 'Mittel',
            'hard' => 'Schwer',
        ];

        return $labels[$difficulty] ?? $difficulty;
    }
}
