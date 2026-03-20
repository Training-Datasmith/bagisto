<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\Attribute_Family_Repository;
use Webkul\Attribute\Repositories\Attribute_Repository;
use Webkul\Category\Repositories\Category_Repository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Repositories\Country_Repository;
use Webkul\Core\Repositories\Country_State_Repository;
use Webkul\Tax\Repositories\Tax_Category_Repository;
class Cart_Rule_Repository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(protected Attribute_Family_Repository $attribute_family_repository, protected Attribute_Repository $attribute_repository, protected Category_Repository $category_repository, protected Cart_Rule_Coupon_Repository $cart_rule_coupon_repository, protected Tax_Category_Repository $tax_category_repository, protected Country_Repository $country_repository, protected Country_State_Repository $country_state_repository, Container $container)
    {
        parent::__construct($container);
    }
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\CartRule\Contracts\CartRule';
    }
    /**
     * @return \Webkul\CartRule\Contracts\CartRule
     */
    public function create(array $data)
    {
        $data['starts_from'] = $data['starts_from'] ?: null;
        $data['ends_till'] = $data['ends_till'] ?: null;
        $data['status'] = isset($data['status']);
        $cart_rule = parent::create($data);
        $cart_rule->channels()->sync($data['channels']);
        $cart_rule->customer_groups()->sync($data['customer_groups']);
        if ($data['coupon_type'] && !$data['use_auto_generation']) {
            $this->cart_rule_coupon_repository->create(['cart_rule_id' => $cart_rule->id, 'code' => $data['coupon_code'], 'usage_limit' => $data['uses_per_coupon'] ?? 0, 'usage_per_customer' => $data['usage_per_customer'] ?? 0, 'is_primary' => 1, 'expired_at' => $data['ends_till'] ?? null]);
        }
        return $cart_rule;
    }
    /**
     * @param  int  $id
     * @return \Webkul\CartRule\Contracts\CartRule
     */
    public function update(array $data, $id)
    {
        $data = array_merge($data, ['starts_from' => $data['starts_from'] ?: null, 'ends_till' => $data['ends_till'] ?: null, 'status' => isset($data['status']), 'conditions' => $data['conditions'] ?? []]);
        $cart_rule = $this->find($id);
        parent::update($data, $id);
        $cart_rule->channels()->sync($data['channels']);
        $cart_rule->customer_groups()->sync($data['customer_groups']);
        if (!$data['coupon_type']) {
            $cart_rule_coupon = $this->cart_rule_coupon_repository->delete_where(['is_primary' => 1, 'cart_rule_id' => $cart_rule->id]);
        } else if (!$data['use_auto_generation']) {
            $cart_rule_coupon = $this->cart_rule_coupon_repository->find_one_where(['is_primary' => 1, 'cart_rule_id' => $cart_rule->id]);
            if ($cart_rule_coupon) {
                $this->cart_rule_coupon_repository->update(['code' => $data['coupon_code'], 'usage_limit' => $data['uses_per_coupon'] ?? 0, 'usage_per_customer' => $data['usage_per_customer'] ?? 0, 'expired_at' => $data['ends_till'] ?? null], $cart_rule_coupon->id);
            } else {
                $this->cart_rule_coupon_repository->create(['cart_rule_id' => $cart_rule->id, 'code' => $data['coupon_code'], 'usage_limit' => $data['uses_per_coupon'] ?? 0, 'usage_per_customer' => $data['usage_per_customer'] ?? 0, 'is_primary' => 1, 'expired_at' => $data['ends_till'] ?? null]);
            }
        } else {
            $this->cart_rule_coupon_repository->delete_where(['is_primary' => 1, 'cart_rule_id' => $cart_rule->id]);
            $this->cart_rule_coupon_repository->where('cart_rule_id', $cart_rule->id)->update(['usage_limit' => $data['uses_per_coupon'] ?? 0, 'usage_per_customer' => $data['usage_per_customer'] ?? 0, 'expired_at' => $data['ends_till'] ?? null]);
        }
        return $cart_rule;
    }
    /**
     * Returns attributes for cart rule conditions.
     *
     * @return array
     */
    public function get_condition_attributes()
    {
        $attributes = [['key' => 'cart', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.cart-attribute'), 'children' => [['key' => 'cart|base_sub_total', 'type' => 'price', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.subtotal')], ['key' => 'cart|items_qty', 'type' => 'integer', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.total-items-qty')], ['key' => 'cart|payment_method', 'type' => 'select', 'options' => $this->get_payment_methods(), 'label' => trans('admin::app.marketing.promotions.cart-rules.create.payment-method')], ['key' => 'cart|shipping_method', 'type' => 'select', 'options' => $this->get_shipping_methods(), 'label' => trans('admin::app.marketing.promotions.cart-rules.create.shipping-method')], ['key' => 'cart|postcode', 'type' => 'text', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.shipping-postcode')], ['key' => 'cart|state', 'type' => 'select', 'options' => $this->grouped_states_by_countries(), 'label' => trans('admin::app.marketing.promotions.cart-rules.create.shipping-state')], ['key' => 'cart|country', 'type' => 'select', 'options' => $this->get_countries(), 'label' => trans('admin::app.marketing.promotions.cart-rules.create.shipping-country')]]], ['key' => 'cart_item', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.cart-item-attribute'), 'children' => [['key' => 'cart_item|base_price', 'type' => 'price', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.price-in-cart')], ['key' => 'cart_item|quantity', 'type' => 'integer', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.qty-in-cart')], ['key' => 'cart_item|base_total_weight', 'type' => 'decimal', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.total-weight')], ['key' => 'cart_item|base_total', 'type' => 'price', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.subtotal')], ['key' => 'cart_item|additional', 'type' => 'text', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.additional')]]], ['key' => 'product', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.product-attribute'), 'children' => [['key' => 'product|category_ids', 'type' => 'multiselect', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.categories'), 'options' => $categories = $this->category_repository->get_category_tree()], ['key' => 'product|children::category_ids', 'type' => 'multiselect', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.children-categories'), 'options' => $categories], ['key' => 'product|parent::category_ids', 'type' => 'multiselect', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.parent-categories'), 'options' => $categories], ['key' => 'product|attribute_family_id', 'type' => 'select', 'label' => trans('admin::app.marketing.promotions.cart-rules.create.attribute-family'), 'options' => $this->get_attribute_families()]]]];
        $temp_attributes = $this->attribute_repository->with(['translations', 'options', 'options.translations'])->find_where_not_in('type', ['textarea', 'image', 'file']);
        foreach ($temp_attributes as $attribute) {
            $attribute_type = $attribute->type;
            if ($attribute->code == 'tax_category_id') {
                $options = $this->get_tax_categories();
            } else {
                $options = $attribute->options;
            }
            if ($attribute->validation == 'decimal') {
                $attribute_type = 'decimal';
            } elseif ($attribute->validation == 'numeric') {
                $attribute_type = 'integer';
            }
            $attributes[2]['children'][] = ['key' => 'product|' . $attribute->code, 'type' => $attribute->type, 'label' => $attribute->name, 'options' => $options];
            $attributes[2]['children'][] = ['key' => 'product|children::' . $attribute->code, 'type' => $attribute->type, 'label' => trans('admin::app.marketing.promotions.cart-rules.create.attribute-name-children-only', ['attribute_name' => $attribute->name]), 'options' => $options];
            $attributes[2]['children'][] = ['key' => 'product|parent::' . $attribute->code, 'type' => $attribute->type, 'label' => trans('admin::app.marketing.promotions.cart-rules.create.attribute-name-parent-only', ['attribute_name' => $attribute->name]), 'options' => $options];
        }
        return $attributes;
    }
    /**
     * Returns all payment methods.
     *
     * @return array
     */
    public function get_payment_methods()
    {
        $methods = [];
        foreach (config('payment_methods') as $payment_method) {
            $object = app($payment_method['class']);
            $methods[] = ['id' => $object->get_code(), 'admin_name' => $object->get_title()];
        }
        return $methods;
    }
    /**
     * Returns all shipping methods.
     *
     * @return array
     */
    public function get_shipping_methods()
    {
        $methods = [];
        foreach (config('carriers') as $shipping_method) {
            $object = app($shipping_method['class']);
            $methods[] = ['id' => $object->get_code(), 'admin_name' => $object->get_title()];
        }
        return $methods;
    }
    /**
     * Returns all countries.
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
    /**
     * Returns all countries.
     *
     * @return array
     */
    public function get_countries()
    {
        $countries = [];
        foreach (DB::table('countries')->get() as $country) {
            $countries[] = ['id' => $country->code, 'admin_name' => $country->name];
        }
        return $countries;
    }
    /**
     * Retrieve all grouped states by country code.
     *
     * @return array
     */
    public function grouped_states_by_countries()
    {
        $collection = [];
        $countries = DB::table('countries')->get();
        $countries_states = DB::table('country_states')->get();
        foreach ($countries as $country) {
            $states = $countries_states->where('country_id', $country->id);
            if (!count($states)) {
                continue;
            }
            $collection[] = ['id' => $country->code, 'admin_name' => $country->name, 'states' => $states];
        }
        return $collection;
    }
}