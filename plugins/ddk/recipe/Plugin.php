<?php

namespace Ddk\Recipe;

use Backend\Facades\Backend;
use Backend\Models\UserRole;
use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'ddk.recipe::lang.plugin.name',
            'description' => 'ddk.recipe::lang.plugin.description',
            'author'      => 'Ddk',
            'icon'        => 'icon-cutlery',
        ];
    }

    /**
     * Registers the components provided by this plugin.
     */
    public function registerComponents(): array
    {
        return [
            \Ddk\Recipe\Components\CategoryFilter::class => 'categoryFilter',
            \Ddk\Recipe\Components\RecipeDetail::class => 'recipeDetail',
            \Ddk\Recipe\Components\RecipeList::class => 'recipeList',
            \Ddk\Recipe\Components\FeaturedRecipes::class => 'featuredRecipes',
        ];
    }

    /**
     * Registers the permissions provided by this plugin.
     */
    public function registerPermissions(): array
    {
        return [
            'ddk.recipe.access_recipes' => [
                'tab'   => 'ddk.recipe::lang.recipe.tab',
                'label' => 'ddk.recipe::lang.recipe.access_recipes',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'ddk.recipe.access_ingredients' => [
                'tab'   => 'ddk.recipe::lang.recipe.tab',
                'label' => 'ddk.recipe::lang.recipe.access_ingredients',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'ddk.recipe.access_units' => [
                'tab'   => 'ddk.recipe::lang.recipe.tab',
                'label' => 'ddk.recipe::lang.recipe.access_units',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
            'ddk.recipe.access_categories' => [
                'tab'   => 'ddk.recipe::lang.recipe.tab',
                'label' => 'ddk.recipe::lang.recipe.access_categories',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
        ];
    }

    /**
     * Registers the backend navigation items provided by this plugin.
     */
    public function registerNavigation(): array
    {
        return [
            'recipe' => [
                'label'       => 'ddk.recipe::lang.recipe.menu_label',
                'url'         => Backend::url('ddk/recipe/recipes'),
                'icon'        => 'icon-cutlery',
                'permissions' => ['ddk.recipe.*'],
                'order'       => 400,

                'sideMenu' => [
                    'recipes' => [
                        'label'       => 'ddk.recipe::lang.recipe.recipes',
                        'icon'        => 'icon-book',
                        'url'         => Backend::url('ddk/recipe/recipes'),
                        'permissions' => ['ddk.recipe.access_recipes'],
                    ],
                    'ingredients' => [
                        'label'       => 'ddk.recipe::lang.recipe.ingredients',
                        'icon'        => 'icon-lemon-o',
                        'url'         => Backend::url('ddk/recipe/ingredients'),
                        'permissions' => ['ddk.recipe.access_ingredients'],
                    ],
                    'units' => [
                        'label'       => 'ddk.recipe::lang.recipe.units',
                        'icon'        => 'icon-balance-scale',
                        'url'         => Backend::url('ddk/recipe/units'),
                        'permissions' => ['ddk.recipe.access_units'],
                    ],
                    'categories' => [
                        'label'       => 'ddk.recipe::lang.recipe.categories',
                        'icon'        => 'icon-list-ul',
                        'url'         => Backend::url('ddk/recipe/categories'),
                        'permissions' => ['ddk.recipe.access_categories'],
                    ],
                ],
            ],
        ];
    }
}
