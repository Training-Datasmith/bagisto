<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Repositories;

use Illuminate\Container\Container;
use Webkul\Attribute\Repositories\Attribute_Family_Repository;
use Webkul\Attribute\Repositories\Attribute_Repository;
use Webkul\Category\Repositories\Category_Repository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Tax\Repositories\Tax_Category_Repository;
class Catalog_Rule_Repository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Family_Repository $attribute_family_repository, protected Attribute_Repository $attribute_repository, protected Category_Repository $category_repository, protected Tax_Category_Repository $tax_category_repository, Container $container)
    {
        parent::__construct($container);
    }
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\CatalogRule\Contracts\CatalogRule';
    }
    /**
     * Create.
     *
     * @return \Webkul\CatalogRule\Contracts\CatalogRule
     */
    public function create(array $data)
    {
        $data = $this->transform_form_data($data);
        $catalog_rule = parent::create($data);
        $catalog_rule->channels()->sync($data['channels']);
        $catalog_rule->customer_groups()->sync($data['customer_groups']);
        return $catalog_rule;
    }
    /**
     * Update.
     *
     * @param  int  $id
     * @return \Webkul\CatalogRule\Contracts\CatalogRule
     */
    public function update(array $data, $id)
    {
        $data = $this->transform_form_data($data);
        $catalog_rule = $this->find($id);
        parent::update($data, $id);
        $catalog_rule->channels()->sync($data['channels']);
        $catalog_rule->customer_groups()->sync($data['customer_groups']);
        return $catalog_rule;
    }
    /**
     * Transform form data.
     */
    public function transform_form_data(array $data): array
    {
        return [...$data, 'starts_from' => !empty($data['starts_from']) ? $data['starts_from'] : null, 'ends_till' => !empty($data['ends_till']) ? $data['ends_till'] : null, 'status' => isset($data['status']), 'conditions' => $data['conditions'] ?? []];
    }
    /**
     * Returns attributes for catalog rule conditions.
     *
     * @return array
     */
    public function get_condition_attributes()
    {
        $attributes = [['key' => 'product', 'label' => trans('admin::app.marketing.promotions.catalog-rules.create.product-attribute'), 'children' => [['key' => 'product|category_ids', 'type' => 'multiselect', 'label' => trans('admin::app.marketing.promotions.catalog-rules.create.categories'), 'options' => $this->category_repository->get_category_tree()], ['key' => 'product|attribute_family_id', 'type' => 'select', 'label' => trans('admin::app.marketing.promotions.catalog-rules.create.attribute-family'), 'options' => $this->get_attribute_families()]]]];
        foreach ($this->attribute_repository->find_where_not_in('type', ['textarea', 'image', 'file']) as $attribute) {
            $attribute_type = $attribute->type;
            if ($attribute->code == 'tax_category_id') {
                $options = $this->get_tax_categories();
            } else if ($attribute->type === 'select') {
                $options = $attribute->options()->order_by('sort_order')->get();
            } else {
                $options = $attribute->options;
            }
            if ($attribute->validation == 'decimal') {
                $attribute_type = 'decimal';
            }
            if ($attribute->validation == 'numeric') {
                $attribute_type = 'integer';
            }
            $attributes[0]['children'][] = ['key' => 'product|' . $attribute->code, 'type' => $attribute->type, 'label' => $attribute->name, 'options' => $options];
        }
        return $attributes;
    }
    /**
     * Returns all tax categories.
     *
     * @return array
     */
    public function get_tax_categories()
    {
        $tax_categories = [];
        foreach ($this->tax_category_repository->all() as $tax_category) {
            $tax_categories[] = ['id' => $tax_category->id, 'admin_name' => $tax_category->name];
        }
        return $tax_categories;
    }
    /**
     * Returns all attribute families.
     *
     * @return array
     */
    public function get_attribute_families()
    {
        $attribute_families = [];
        foreach ($this->attribute_family_repository->all() as $attribute_family) {
            $attribute_families[] = ['id' => $attribute_family->id, 'admin_name' => $attribute_family->name];
        }
        return $attribute_families;
    }
}