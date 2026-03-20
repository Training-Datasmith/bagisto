<?php

declare (strict_types=1);
namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Checkout\Contracts\Cart as CartContract;
use Webkul\Checkout\Database\Factories\Cart_Factory;
use Webkul\Core\Models\Channel_Proxy;
use Webkul\Customer\Models\Customer_Proxy;
class Cart extends Model implements Cart_Contract
{
    use Has_Factory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cart';
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['additional' => 'json'];
    /**
     * Get the customer record associated with the address.
     */
    public function customer(): \Illuminate\Database\Eloquent\Relations\Belongs_To
    {
        return $this->belongs_to(Customer_Proxy::model_class());
    }
    /**
     * Get the channel record associated with the address.
     */
    public function channel(): \Illuminate\Database\Eloquent\Relations\Belongs_To
    {
        return $this->belongs_to(Channel_Proxy::model_class());
    }
    /**
     * To get relevant associated items with the cart instance.
     */
    public function items(): \Illuminate\Database\Eloquent\Relations\Has_Many
    {
        return $this->has_many(Cart_Item_Proxy::model_class())->where_null('parent_id')->with(['child', 'children']);
    }
    /**
     * To get all the associated items with the cart instance even the parent and child items of configurable products.
     */
    public function all_items(): \Illuminate\Database\Eloquent\Relations\Has_Many
    {
        return $this->has_many(Cart_Item_Proxy::model_class());
    }
    /**
     * Get the billing address for the cart.
     */
    public function billing_address(): \Illuminate\Database\Eloquent\Relations\Has_One
    {
        return $this->has_one(Cart_Address_Proxy::model_class())->where('address_type', Cart_Address::ADDRESS_TYPE_BILLING);
    }
    /**
     * Get the shipping address for the cart.
     */
    public function shipping_address(): \Illuminate\Database\Eloquent\Relations\Has_One
    {
        return $this->has_one(Cart_Address_Proxy::model_class())->where('address_type', Cart_Address::ADDRESS_TYPE_SHIPPING);
    }
    /**
     * Get the shipping rates for the cart.
     */
    public function shipping_rates(): \Illuminate\Database\Eloquent\Relations\Has_Many
    {
        return $this->has_many(Cart_Shipping_Rate_Proxy::model_class());
    }
    /**
     * Get all the attributes for the attribute groups.
     */
    public function selected_shipping_rate()
    {
        return $this->shipping_rates->where('method', $this->shipping_method);
    }
    /**
     * Get all the attributes for the attribute groups.
     */
    public function get_selected_shipping_rate_attribute()
    {
        return $this->selected_shipping_rate()->first();
    }
    /**
     * Get the payment associated with the cart.
     */
    public function payment(): \Illuminate\Database\Eloquent\Relations\Has_One
    {
        return $this->has_one(Cart_Payment_Proxy::model_class());
    }
    /**
     * Checks if cart have stockable items.
     */
    public function have_stockable_items(): bool
    {
        foreach ($this->items as $item) {
            if ($item->product->is_stockable()) {
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if cart has only stockable items.
     */
    public function has_only_stockable_items(): bool
    {
        foreach ($this->items as $item) {
            if (!$item->product->is_stockable()) {
                return false;
            }
        }
        return true;
    }
    /**
     * Checks if cart has downloadable items.
     */
    public function has_downloadable_items(): bool
    {
        return $this->items->pluck('type')->contains('downloadable');
    }
    /**
     * Returns true if cart contains one or many products with quantity box.
     *
     * (For Example: simple, configurable, virtual)
     */
    public function has_products_with_quantity_box(): bool
    {
        foreach ($this->items as $item) {
            if ($item->get_type_instance()->show_quantity_box()) {
                return true;
            }
        }
        return false;
    }
    /**
     * Checks if cart has items that allow guest checkout.
     */
    public function has_guest_checkout_items(): bool
    {
        foreach ($this->items as $item) {
            if (!$item->product->get_attribute('guest_checkout')) {
                return false;
            }
        }
        return true;
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Cart_Factory::new();
    }
}