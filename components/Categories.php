<?php
namespace Tiipiik\Catalog\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Tiipiik\Catalog\Models\Category;

class Categories extends ComponentBase
{
    /**
     * @var mixed
     */
    public $category;
    /**
     * @var mixed
     */
    public $categories;
    /**
     * @var mixed
     */
    public $productCategoryPage;
    /**
     * @var mixed
     */
    public $currentProductCategorySlug;
    /**
     * @var mixed
     */
    public $noProductCategoriesMessage;
    /**
     * @var mixed
     */
    public $renderview;
    /**
     * @var mixed
     */
    public $product_categories;
    /**
     * @var mixed
     */
    public $subCategoriesTitle;

    public function componentDetails()
    {
        return [
            'name'        => 'tiipiik.catalog::lang.component.categories.name',
            'description' => 'tiipiik.catalog::lang.component.categories.description',
        ];
    }

    public function defineProperties()
    {
        return [
            'slug'                       => [
                'title'       => 'tiipiik.catalog::lang.component.categories.param.idparam_title',
                'description' => 'tiipiik.catalog::lang.component.categories.param.idparam_desc',
                'default'     => '{{ :slug }}',
                'type'        => 'string',
            ],
            'displayEmpty'               => [
                'title'       => 'tiipiik.catalog::lang.component.categories.param.display_empty_title',
                'description' => 'tiipiik.catalog::lang.component.categories.param.display_empty_desc',
                'type'        => 'checkbox',
                'default'     => 0,
            ],
            'noProductCategoriesMessage' => [
                'title'       => 'tiipiik.catalog::lang.component.categories.param.no_products_title',
                'description' => 'tiipiik.catalog::lang.component.categories.param.no_products_desc',
                'type'        => 'string',
                'default'     => 'No category found',
            ],
            'subCategories'              => [
                'title'       => 'tiipiik.catalog::lang.component.categories.param.subcategories_title',
                'description' => 'tiipiik.catalog::lang.component.categories.param.subcategories_desc',
                'type'        => 'checkbox',
                'default'     => 0,
                'group'       => 'SubCategories',
            ],
            'subCategoriesTitle'         => [
                'title'       => 'tiipiik.catalog::lang.component.categories.param.subcategories_title_title',
                'description' => 'tiipiik.catalog::lang.component.categories.param.subcategories_title_desc',
                'default'     => '',
                'group'       => 'SubCategories',
            ],
            'renderView'                 => [
                'title'       => 'tiipiik.catalog::lang.component.categories.param.render_view_title',
                'description' => 'tiipiik.catalog::lang.component.categories.param.render_view_desc',
                'type'        => 'dropdown',
                'default'     => 'menulist',
                'group'       => 'Render',
            ],
            'categoryPage'               => [
                'title'       => 'tiipiik.catalog::lang.component.categories.param.category_page_title',
                'description' => 'tiipiik.catalog::lang.component.categories.param.category_page_desc',
                'type'        => 'dropdown',
                'default'     => 'category',
                'group'       => 'Links',
            ],
        ];
    }

    public function getRenderViewOptions()
    {
        return ['menu_list' => 'Menu list', 'image_list' => 'Image list'];
    }

    public function getCategoryPageOptions()
    {
        return ['' => '- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        $this->renderview                 = $this->property('renderView');
        $this->noProductCategoriesMessage = $this->property('noProductCategoriesMessage');
        $this->productCategoryPage        = $this->property('categoryPage');
        $this->currentProductCategorySlug = $this->property('slug');
        $this->subCategoriesTitle         = $this->property('subCategoriesTitle');
        $this->product_categories         = $this->loadCategories();
    }

    /**
     * @return mixed
     */
    protected function loadCategories()
    {
        // If param for displaying subcategories is checked
        if ($this->property('subCategories') == 1) {
            $category = Category::transWhere('slug', $this->property('slug'))->first();
            $categories = Category::whereParentId($category->id);
        } else {
            // Or select all categories of first level
            $categories = Category::whereNull('parent_id');
        }
        $categories->orderBy('name');

        // Hide empty categories
        if ($this->property('displayEmpty') == 0 || $this->property('displayEmpty') === false) {
            $categories->whereHas('products', function ($query) {
                $query->whereIsPublished(1);
            });
        }

        $categories = $categories->get();

        if (!$categories) {
            return null;
        }

        /*
         * Add a "url" helper attribute for linking to each category
         */
        $categories->each(function ($category) {
            $category->setUrl($this->productCategoryPage, $this->controller);
            $category->isActive      = $category->slug == $this->currentProductCategorySlug;
            $category->isChildActive = Category::hasActiveChild(
                $category->id,
                $this->property('slug')
            );
            $category->hasChildren = Category::hasChildren($category->slug);
        });

        // Reorder categories to tree for navigation
        // dump('Add tree order');
        // dump($categories);

        return $categories;
    }
}
