<?php

namespace Ddk\Recipe\Models;

use Winter\Storm\Database\Model;
use Winter\Storm\Support\Str;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use Winter\Storm\Support\Facades\Url;

class Category extends Model
{
    use \Winter\Storm\Database\Traits\NestedTree;
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Sortable;

    public $table = 'ddk_recipe_categories';

    public $rules = [
        'title' => 'required|max:255',
        'slug' => 'required|max:255|unique:ddk_recipe_categories',
    ];

    protected $guarded = ['id'];

    protected $fillable = ['title', 'slug', 'parent_id', 'sort_order'];

    public $belongsToMany = [
        'recipes' => [
            Recipe::class,
            'table' => 'ddk_recipe_categories_map',
            'key' => 'category_id',
            'otherKey' => 'recipe_id',
        ],
    ];

    public function beforeValidate()
    {
        if (!$this->exists && !$this->slug) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function beforeSave()
    {
        // Convert empty string to null for parent_id
        if ($this->parent_id === '' || $this->parent_id === 0) {
            $this->parent_id = null;
        }
    }

    /**
     * Get options for parent category dropdown
     */
    public function getParentIdOptions()
    {
        $options = ['' => trans('ddk.recipe::lang.recipe.no_parent')];

        $categories = static::where('id', '!=', $this->id ?: 0)
            ->orderBy('nest_left')
            ->get();

        foreach ($categories as $category) {
            $prefix = str_repeat('— ', $category->nest_depth);
            $options[$category->id] = $prefix . $category->title;
        }

            return $options;
        }

        /**
         * Returns a list of menu item types compatible with the Sitemap menu
         * Returns null if the type is not supported
         */
        public static function getMenuTypeInfo($type): array
        {
            $result = [];

            if ($type == 'recipe-category') {
                $result = [
                    'references' => self::getNestedOptions(),
                    'nesting' => true,
                    'dynamicItems' => true,
                ];
            }

            if ($type == 'all-recipe-categories') {
                $result = [
                    'dynamicItems' => true,
                ];
            }

            if ($result) {
                $result['cmsPages'] = self::getCmsPageOptions();
            }

            return $result;
        }

        /**
         * Fetches the dynamic menu items (like all categories or subcategories)
         * from the models and returns them as a structured array for the Sitemap
         */
        public static function resolveMenuItem($item, $url, $theme, ?int $referenceKey = null): ?array
        {
            $result = null;

            // Only resolve if Cms classes are available
            if (!class_exists(CmsPage::class) || !class_exists(Theme::class)) {
                return $result;
            }

            if ($item->type == 'recipe-category') {
                if (!$item->reference) {
                    return null;
                }

                $category = self::find($item->reference);
                if (!$category) {
                    return null;
                }

                // Get the category URL from getUrl()
                $pageUrl = $category->getUrl($item->cmsPage);
                if (!$pageUrl) {
                    return null;
                }

                $pageUrl = Url::to($pageUrl);
                $result = [
                    'url' => $pageUrl,
                    'isActive' => $pageUrl === $url,
                    'mtime' => $category->updated_at,
                ];

                // Include subcategories recursively
                $result['items'] = self::getChildrenAsMenuItems($category, $url, $item->cmsPage);
            } elseif ($item->type == 'all-recipe-categories') {
                $result = [
                    'items' => [],
                ];

                // Get top-level categories
                $categories = self::whereNull('parent_id')
                    ->orderBy('title')
                    ->get();

                foreach ($categories as $category) {
                    $categoryItem = [
                        'title' => $category->title,
                        'url' => Url::to($category->getUrl($item->cmsPage)),
                        'mtime' => $category->updated_at,
                    ];

                    $categoryItem['isActive'] = $categoryItem['url'] === $url;

                    // Recursively get children
                    $categoryItem['items'] = self::getChildrenAsMenuItems($category, $url, $item->cmsPage);
                    
                    $result['items'][] = $categoryItem;
                }
            }

            return $result;
        }

        /**
         * Helper method to recursively build menu items for category children
         */
        protected static function getChildrenAsMenuItems($category, $currentUrl, $cmsPage): array
        {
            $items = [];

            $children = $category->children()->orderBy('title')->get();
            foreach ($children as $child) {
                $item = [
                    'title' => $child->title,
                    'url' => Url::to($child->getUrl($cmsPage)),
                    'mtime' => $child->updated_at,
                ];

                $item['isActive'] = $item['url'] === $currentUrl;
                
                // Recursively get grandchildren
                $item['items'] = self::getChildrenAsMenuItems($child, $currentUrl, $cmsPage);

                $items[] = $item;
            }

            return $items;
        }

        /**
         * Generates URL for a category using the CMS page
         */
        public function getUrl($cmsPage = null): string
        {
            if (!$cmsPage) {
                return '/rezepte/kategorie/' . $this->slug;
            }

            // Check if the page is a recipe list page with category filter
            $page = CmsPage::loadCached(Theme::getActiveTheme(), $cmsPage);
            if (!$page) {
                return '/rezepte/kategorie/' . $this->slug;
            }

            $properties = $page->getComponentProperties(\Ddk\Recipe\Components\RecipeList::class);
            if (!isset($properties['categoryFilter']) || $properties['categoryFilter'] !== '{{ :slug }}') {
                return '/rezepte/kategorie/' . $this->slug;
            }

            // Extract the routing parameter name
            $matches = [];
            $pattern = '|^.*{{\s*:([^|]+)\|slug\s*}}.*$|';
            if (!preg_match($pattern, $properties['categoryFilter'], $matches)) {
                return '/rezepte/kategorie/' . $this->slug;
            }

            $paramName = trim($matches[1], "'");
            
            $url = CmsPage::url($page->getFileName(), [$paramName => $this->slug]);
            
            return $url ?: '/rezepte/kategorie/' . $this->slug;
        }

        /**
         * Helper method to find CMS pages that display categories
         */
        protected static function getCmsPageOptions(): array
        {
            if (!class_exists(CmsPage::class) || !class_exists(Theme::class)) {
                return [];
            }

            $theme = Theme::getActiveTheme();
            $pages = CmsPage::listInTheme($theme, true);
            
            $cmsPages = [];
            foreach ($pages as $page) {
                if (!$page->hasComponent('recipeList')) {
                    continue;
                }

                $properties = $page->getComponentProperties(\Ddk\Recipe\Components\RecipeList::class);
                if (!isset($properties['categoryFilter']) || $properties['categoryFilter'] !== '{{ :slug }}') {
                    continue;
                }

                $cmsPages[] = $page;
            }

            return $cmsPages;
        }
    }
