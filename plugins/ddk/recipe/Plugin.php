<?php

namespace Ddk\Recipe;

use Backend\Facades\Backend;
use Backend\Models\UserRole;
use System\Classes\PluginBase;
use Ddk\Recipe\Models\Category;
use Ddk\Recipe\Models\Recipe;
use Winter\Storm\Support\Facades\Event;

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

    /**
     * Boot method, called when the plugin is first booted.
     */
    public function boot(): void
    {
        $this->extendWinterPagesPlugin();
    }

    /**
     * Extends the Winter.Pages plugin
     */
    protected function extendWinterPagesPlugin(): void
    {
        /*
         * Register menu items for the Winter.Pages plugin
         */
        Event::listen('pages.menuitem.listTypes', function () {
            return [
                'recipe-category' => 'ddk.recipe::lang.menuitem.recipe_category',
                'all-recipe-categories' => 'ddk.recipe::lang.menuitem.all_recipe_categories',
                'recipe-post' => 'ddk.recipe::lang.menuitem.recipe_post',
                'all-recipe-posts' => 'ddk.recipe::lang.menuitem.all_recipe_posts',
                'category-recipe-posts' => 'ddk.recipe::lang.menuitem.category_recipe_posts',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function ($type) {
            switch ($type) {
                case 'recipe-category':
                case 'all-recipe-categories':
                    return Category::getMenuTypeInfo($type);
                case 'recipe-post':
                case 'all-recipe-posts':
                case 'category-recipe-posts':
                    return Recipe::getMenuTypeInfo($type);
            }
        });

        Event::listen('pages.menuitem.resolveItem', function ($type, $item, $url, $theme) {
            switch ($type) {
                case 'recipe-category':
                case 'all-recipe-categories':
                    return Category::resolveMenuItem($item, $url, $theme);
                case 'recipe-post':
                case 'all-recipe-posts':
                case 'category-recipe-posts':
                    return Recipe::resolveMenuItem($item, $url, $theme);
            }
        });
    }
}
