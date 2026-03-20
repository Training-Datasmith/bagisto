<?php

declare (strict_types=1);
namespace Webkul\Checkout;

use Illuminate\Support\Facades\Event;
use Webkul\Checkout\Contracts\Cart_Address as CartAddressContract;
use Webkul\Checkout\Exceptions\Billing_Address_Not_Found_Exception;
use Webkul\Checkout\Models\Cart_Address;
use Webkul\Checkout\Models\Cart_Payment;
use Webkul\Checkout\Repositories\Cart_Address_Repository;
use Webkul\Checkout\Repositories\Cart_Item_Repository;
use Webkul\Checkout\Repositories\Cart_Repository;
use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Customer\Contracts\Wishlist as WishlistContract;
use Webkul\Customer\Repositories\Customer_Address_Repository;
use Webkul\Customer\Repositories\Wishlist_Repository;
use Webkul\Product\Contracts\Product as ProductContract;
use Webkul\Product\Repositories\Product_Repository;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Tax\Facades\Tax;
use Webkul\Tax\Repositories\Tax_Category_Repository;
class Cart
{
    /**
     * The cart instance.
     *
     * @var \Webkul\Checkout\Contracts\Cart
     */
    private $cart;
    /**
     * Constant for tax calculation based on shipping origin.
     */
    public const TAX_CALCULATION_BASED_ON_SHIPPING_ORIGIN = 'shipping_origin';
    /**
     * Constant for tax calculation based on billing address.
     */
    public const TAX_CALCULATION_BASED_ON_BILLING_ADDRESS = 'billing_address';
    /**
     * Constant for tax calculation based on shipping address.
     */
    public const TAX_CALCULATION_BASED_ON_SHIPPING_ADDRESS = 'shipping_address';
    /**
     * Create a new class instance.
     *
     * @return void
     */
    public function __construct(protected Cart_Repository $cart_repository, protected Cart_Item_Repository $cart_item_repository, protected Cart_Address_Repository $cart_address_repository, protected Product_Repository $product_repository, protected Tax_Category_Repository $tax_category_repository, protected Wishlist_Repository $wishlist_repository, protected Customer_Address_Repository $customer_address_repository)
    {
        $this->init_cart();
    }
    /**
     * Initialize cart
     */
    public function init_cart(?Customer_Contract $customer = null): void
    {
        if (!$customer) {
            $customer = auth()->guard()->user();
        }
        if ($customer) {
            $this->cart = $this->cart_repository->find_one_where(['customer_id' => $customer->id, 'is_active' => 1]);
        } elseif (session()->has('cart')) {
            $this->cart = $this->cart_repository->find(session()->get('cart')->id);
        }
    }
    /**
     * Returns cart
     */
    public function refresh_cart(): void
    {
        if (!$this->cart) {
            return;
        }
        $this->cart = $this->cart_repository->find($this->cart->id);
    }
    /**
     * Set cart
     */
    public function set_cart(Contracts\Cart $cart): void
    {
        $this->cart = $cart;
        if ($this->cart->customer) {
            return;
        }
        $cart_temp = new \stdClass();
        $cart_temp->id = $this->cart->id;
        session()->put('cart', $cart_temp);
    }
    /**
     * Returns cart.
     */
    public function get_cart(): ?Contracts\Cart
    {
        return $this->cart;
    }
    /**
     * Create new cart instance.
     */
    public function create_cart(array $data): ?Contracts\Cart
    {
        $data = array_merge(['is_guest' => 1, 'channel_id' => core()->get_current_channel()->id, 'global_currency_code' => $base_currency_code = core()->get_base_currency_code(), 'base_currency_code' => $base_currency_code, 'channel_currency_code' => core()->get_channel_base_currency_code(), 'cart_currency_code' => core()->get_current_currency_code()], $data);
        $customer = $data['customer'] ?? auth()->guard()->user();
        if ($customer) {
            $data = array_merge($data, ['is_guest' => 0, 'customer_id' => $customer->id, 'customer_first_name' => $customer->first_name, 'customer_last_name' => $customer->last_name, 'customer_email' => $customer->email]);
        }
        $cart = $this->cart_repository->create($data);
        $this->set_cart($cart);
        return $cart;
    }
    /**
     * Remove cart and destroy the session
     */
    public function remove_cart(Contracts\Cart $cart): void
    {
        $this->cart_repository->delete($cart->id);
        if (session()->has('cart')) {
            session()->forget('cart');
        }
        $this->reset_cart();
    }
    /**
     * Reset cart
     */
    public function reset_cart(): void
    {
        $this->cart = null;
    }
    /**
     * Activate the cart by id.
     */
    public function activate_cart(int $cart_id): void
    {
        $cart = $this->cart_repository->update(['is_active' => true], $cart_id);
        $this->set_cart($cart);
    }
    /**
     * Deactivates current cart.
     */
    public function de_activate_cart(): void
    {
        if (!$this->cart) {
            return;
        }
        $this->cart_repository->update(['is_active' => false], $this->cart->id);
        $this->reset_cart();
        if (session()->has('cart')) {
            session()->forget('cart');
        }
    }
    /**
     * This method handles when guest has some of cart products and then logs in.
     */
    public function merge_cart(Customer_Contract $customer): void
    {
        if (!session()->has('cart')) {
            return;
        }
        $cart = $this->cart_repository->find_one_where(['customer_id' => $customer->id, 'is_active' => 1]);
        $guest_cart = $this->cart_repository->find(session()->get('cart')->id);
        /**
         * When the logged in customer is not having any of the cart instance previously and are active.
         */
        if (!$cart) {
            $this->cart_repository->update(['customer_id' => $customer->id, 'is_guest' => 0, 'customer_first_name' => $customer->first_name, 'customer_last_name' => $customer->last_name, 'customer_email' => $customer->email], $guest_cart->id);
            session()->forget('cart');
            return;
        }
        $this->set_cart($cart);
        foreach ($guest_cart->items as $guest_cart_item) {
            try {
                $this->add_product($guest_cart_item->product, $guest_cart_item->additional);
            } catch (\Exception $e) {
                // Ignore exception
            }
        }
        $this->collect_totals();
        $this->remove_cart($guest_cart);
    }
    /**
     * Add items in a cart with some cart and item details.
     */
    public function add_product(Product_Contract $product, array $data): Contracts\Cart|\Exception
    {
        Event::dispatch('checkout.cart.add.before', $product->id);
        if (!$this->cart) {
            $this->create_cart([]);
        }
        $cart_products = $product->get_type_instance()->prepare_for_cart(array_merge(['cart_id' => $this->cart->id], $data));
        if (is_string($cart_products)) {
            if (!$this->cart->all_items->count()) {
                $this->remove_cart($this->cart);
            } else {
                $this->collect_totals();
            }
            throw new \Exception($cart_products);
        } else {
            $parent_cart_item = null;
            foreach ($cart_products as $cart_product) {
                $cart_item = $this->get_item_by_product($cart_product, $data);
                if (isset($cart_product['parent_id'])) {
                    $cart_product['parent_id'] = $parent_cart_item->id;
                }
                if (!$cart_item) {
                    $cart_item = $this->cart_item_repository->create(array_merge($cart_product, ['cart_id' => $this->cart->id]));
                } else if (isset($cart_product['parent_id']) && $cart_item->parent_id !== $parent_cart_item->id) {
                    $cart_item = $this->cart_item_repository->create(array_merge($cart_product, ['cart_id' => $this->cart->id]));
                } else {
                    $cart_item = $this->cart_item_repository->update($cart_product, $cart_item->id);
                }
                if (!$parent_cart_item) {
                    $parent_cart_item = $cart_item;
                }
            }
        }
        $this->collect_totals();
        Event::dispatch('checkout.cart.add.after', $this->cart);
        return $this->cart;
    }
    /**
     * Remove the item from the cart.
     */
    public function remove_item(int $item_id): bool
    {
        if (!$this->cart) {
            return false;
        }
        if (!$this->cart->items->pluck('id')->contains($item_id)) {
            return false;
        }
        Event::dispatch('checkout.cart.delete.before', $item_id);
        Shipping::remove_all_shipping_rates();
        $result = $this->cart_item_repository->delete($item_id);
        Event::dispatch('checkout.cart.delete.after', $item_id);
        return $result;
    }
    /**
     * Update cart items information.
     */
    public function update_items(array $data): bool|\Exception
    {
        foreach ($data['qty'] as $item_id => $quantity) {
            $item = $this->cart_item_repository->find($item_id);
            if (!$item) {
                continue;
            }
            if ($item->cart_id !== $this->cart->id) {
                continue;
            }
            if (!$item->product->status) {
                throw new \Exception(trans('shop::app.checkout.cart.inactive'));
            }
            if ($quantity <= 0) {
                $this->remove_item($item_id);
                throw new \Exception(trans('shop::app.checkout.cart.illegal'));
            }
            $item->quantity = $quantity;
            if (!$this->is_item_have_quantity($item)) {
                throw new \Exception(trans('shop::app.checkout.cart.inventory-warning'));
            }
            Event::dispatch('checkout.cart.update.before', $item);
            $this->cart_item_repository->update(['quantity' => $quantity, 'total' => core()->convert_price($item->base_price * $quantity), 'total_incl_tax' => core()->convert_price($item->base_price_incl_tax * $quantity), 'base_total' => $item->base_price * $quantity, 'base_total_incl_tax' => $item->base_price_incl_tax * $quantity, 'total_weight' => $item->weight * $quantity, 'base_total_weight' => $item->weight * $quantity, 'additional' => [...$item->additional, 'quantity' => $quantity]], $item_id);
            Event::dispatch('checkout.cart.update.after', $item);
        }
        $this->collect_totals();
        return true;
    }
    /**
     * Get cart item by product.
     */
    public function get_item_by_product(array $data, ?array $parent_data = null): ?Contracts\Cart_Item
    {
        $items = $this->cart->all_items;
        foreach ($items as $item) {
            if ($item->get_type_instance()->compare_options($item->additional, $data['additional'])) {
                if (!isset($data['additional']['parent_id']) && !$item->parent_id) {
                    return $item;
                }
                if ($item->parent?->get_type_instance()->compare_options($item->parent->additional, $parent_data ?: request()->all())) {
                    return $item;
                }
            }
        }
        return null;
    }
    /**
     * Update or create billing address.
     */
    public function save_addresses(array $params): void
    {
        $this->update_or_create_billing_address($params['billing']);
        $this->update_or_create_shipping_address($params['shipping'] ?? []);
        $this->set_customer_personnel_details();
        $this->reset_shipping_method();
    }
    /**
     * Update or create billing address.
     */
    public function update_or_create_billing_address(array $params): Cart_Address_Contract
    {
        $params = collect($params)->only(['use_for_shipping', 'default_address', 'company_name', 'first_name', 'last_name', 'vat_id', 'email', 'address', 'country', 'state', 'city', 'postcode', 'phone'])->merge(['address_type' => Cart_Address::ADDRESS_TYPE_BILLING, 'parent_address_id' => ($params['address_type'] ?? '') == 'customer' ? $params['id'] : null, 'cart_id' => $this->cart->id, 'customer_id' => $this->cart->customer_id, 'address' => implode(PHP_EOL, $params['address']), 'use_for_shipping' => (bool) ($params['use_for_shipping'] ?? false)])->to_array();
        if ($this->cart->billing_address) {
            $address = $this->cart_address_repository->update($params, $this->cart->billing_address->id);
        } else {
            $address = $this->cart_address_repository->create($params);
        }
        $this->cart->set_relation('billing_address', $address);
        return $address;
    }
    /**
     * Update or create shipping address.
     */
    public function update_or_create_shipping_address(array $params): ?Cart_Address_Contract
    {
        /**
         * If cart is not having any stockable items then no need to save shipping address.
         */
        if (!$this->cart->have_stockable_items()) {
            return null;
        }
        if (!$this->cart->billing_address) {
            throw new Billing_Address_Not_Found_Exception();
        }
        $fillable_fields = ['default_address', 'company_name', 'first_name', 'last_name', 'email', 'address', 'country', 'state', 'city', 'postcode', 'phone'];
        if ($this->cart->billing_address->use_for_shipping) {
            $params = $this->cart->billing_address->only($fillable_fields);
            $params = array_merge($params, ['address_type' => Cart_Address::ADDRESS_TYPE_SHIPPING, 'parent_address_id' => $this->cart->billing_address->parent_address_id, 'cart_id' => $this->cart->id, 'customer_id' => $this->cart->customer_id]);
        } else {
            if (empty($params)) {
                return null;
            }
            $params = collect($params)->only($fillable_fields)->merge(['address_type' => Cart_Address::ADDRESS_TYPE_SHIPPING, 'parent_address_id' => ($params['address_type'] ?? '') == 'customer' ? $params['id'] : null, 'cart_id' => $this->cart->id, 'customer_id' => $this->cart->customer_id, 'address' => implode(PHP_EOL, $params['address'])])->to_array();
        }
        if ($this->cart->shipping_address) {
            $address = $this->cart_address_repository->update($params, $this->cart->shipping_address->id);
        } else {
            $params['default_address'] = 0;
            $address = $this->cart_address_repository->create($params);
        }
        $this->cart->set_relation('shipping_address', $address);
        return $address;
    }
    /**
     * Save customer details.
     */
    public function set_customer_personnel_details(): void
    {
        $this->cart->customer_email = $this->cart->customer?->email ?? $this->cart->billing_address->email;
        $this->cart->customer_first_name = $this->cart->customer?->first_name ?? $this->cart->billing_address->first_name;
        $this->cart->customer_last_name = $this->cart->customer?->last_name ?? $this->cart->billing_address->last_name;
        $this->cart->save();
    }
    /**
     * Save shipping method for cart.
     */
    public function save_shipping_method(string $shipping_method_code): bool
    {
        if (!$this->cart) {
            return false;
        }
        if (!Shipping::is_method_code_exists($shipping_method_code)) {
            return false;
        }
        $this->cart->shipping_method = $shipping_method_code;
        $this->cart->save();
        return true;
    }
    /**
     * Save shipping method for cart.
     */
    public function reset_shipping_method(): bool
    {
        if (!$this->cart) {
            return false;
        }
        Shipping::remove_all_shipping_rates();
        $this->cart->shipping_method = null;
        $this->cart->save();
        return true;
    }
    /**
     * Save payment method for cart.
     */
    public function save_payment_method(array $params): bool|Contracts\Cart_Payment
    {
        if (!$this->cart) {
            return false;
        }
        if ($cart_payment = $this->cart->payment) {
            $cart_payment->delete();
        }
        $cart_payment = new Cart_Payment();
        $cart_payment->method = $params['method'];
        $cart_payment->method_title = core()->get_config_data('sales.payment_methods.' . $params['method'] . '.title');
        $cart_payment->cart_id = $this->cart->id;
        $cart_payment->save();
        return $cart_payment;
    }
    /**
     * Set coupon code to the cart.
     */
    public function set_coupon_code(?string $code): self
    {
        $this->cart->coupon_code = $code;
        $this->cart->save();
        return $this;
    }
    /**
     * Set coupon code to the cart.
     */
    public function remove_coupon_code(): self
    {
        return $this->set_coupon_code(null);
    }
    /**
     * Move a wishlist item to cart.
     */
    public function move_to_cart(Wishlist_Contract $wishlist_item, ?int $quantity = 1): bool
    {
        if (!$wishlist_item->product->get_type_instance()->can_be_moved_from_wishlist_to_cart($wishlist_item)) {
            return false;
        }
        if (!$wishlist_item->additional) {
            $wishlist_item->additional = ['product_id' => $wishlist_item->product_id];
        }
        $additional = [...$wishlist_item->additional, 'quantity' => $quantity];
        $result = $this->add_product($wishlist_item->product, $additional);
        if ($result) {
            Event::dispatch('customer.wishlist.delete.before', $wishlist_item->id);
            $this->wishlist_repository->delete($wishlist_item->id);
            Event::dispatch('customer.wishlist.delete.after', $wishlist_item->id);
            return true;
        }
        return false;
    }
    /**
     * Move to wishlist items.
     */
    public function move_to_wishlist(int $item_id, int $quantity = 1): bool
    {
        $cart_item = $this->cart->items()->find($item_id);
        if (!$cart_item) {
            return false;
        }
        $wishlist_items = $this->wishlist_repository->find_where(['customer_id' => $this->cart->customer_id, 'product_id' => $cart_item->product_id]);
        $found = false;
        foreach ($wishlist_items as $wishlist_item) {
            $options = $wishlist_item->item_options;
            if (!$options) {
                $options = ['product_id' => $wishlist_item->product_id];
            }
            if ($cart_item->get_type_instance()->compare_options($cart_item->additional, $options)) {
                $found = true;
            }
        }
        if (!$found) {
            Event::dispatch('customer.wishlist.create.before', $cart_item->product_id);
            $wishlist = $this->wishlist_repository->create(['channel_id' => $this->cart->channel_id, 'customer_id' => $this->cart->customer_id, 'product_id' => $cart_item->product_id, 'additional' => [...$cart_item->additional, 'quantity' => $quantity]]);
            Event::dispatch('customer.wishlist.create.after', $wishlist);
        }
        if (!$this->cart->items->count()) {
            $this->cart_repository->delete($this->cart->id);
            $this->refresh_cart();
        } else {
            $this->cart_item_repository->delete($item_id);
            $this->refresh_cart();
            $this->collect_totals();
        }
        return true;
    }
    /**
     * Checks if cart has any error.
     */
    public function has_error(): bool
    {
        return !empty($this->get_errors());
    }
    /**
     * Get Cart Errors.
     */
    public function get_errors()
    {
        if (!$this->cart) {
            return ['error_code' => 'CART_NOT_FOUND', 'message' => trans('shop::app.checkout.cart.index.empty-product')];
        }
        if (!$this->is_items_have_sufficient_quantity()) {
            return ['error_code' => 'INSUFFICIENT_QUANTITY', 'message' => trans('shop::app.checkout.cart.inventory-warning')];
        }
        if (!$this->have_minimum_order_amount()) {
            $minimum_order_description = core()->get_config_data('sales.order_settings.minimum_order.description');
            return ['error_code' => 'MINIMUM_ORDER_AMOUNT', 'message' => $minimum_order_description ?: trans('shop::app.checkout.cart.minimum-order-message'), 'amount' => core()->format_price((int) core()->get_config_data('sales.order_settings.minimum_order.minimum_order_amount') ?: $this->get_order_amount())];
        }
        return [];
    }
    /**
     * Check minimum Order Amount of cart.
     */
    public function get_order_amount(): int
    {
        $minimum_order_amount = $this->cart->sub_total;
        if (core()->get_config_data('sales.order_settings.minimum_order.include_tax_to_amount')) {
            $minimum_order_amount += $this->cart->tax_total;
        }
        if (core()->get_config_data('sales.order_settings.minimum_order.include_discount_amount')) {
            $minimum_order_amount -= $this->cart->tax_total;
            if ($this->cart->discount_amount) {
                $minimum_order_amount -= $this->cart->discount_amount;
            }
        }
        return $minimum_order_amount;
    }
    /**
     * Check minimum order.
     */
    public function have_minimum_order_amount(): bool
    {
        if (!core()->get_config_data('sales.order_settings.minimum_order.enable')) {
            return true;
        }
        return $this->get_order_amount() >= ((int) core()->get_config_data('sales.order_settings.minimum_order.minimum_order_amount') ?: 0);
    }
    /**
     * Checks if all cart items have sufficient quantity.
     */
    public function is_items_have_sufficient_quantity(): bool
    {
        if (!$this->cart) {
            return false;
        }
        foreach ($this->cart->items as $item) {
            if (!$this->is_item_have_quantity($item)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Checks if all cart items have sufficient quantity.
     */
    public function is_item_have_quantity(Contracts\Cart_Item $item): bool
    {
        return $item->get_type_instance()->is_item_have_quantity($item);
    }
    /**
     * Updates cart totals.
     */
    public function collect_totals(): self
    {
        if (!$this->validate_items()) {
            /**
             * Reset the cart so that fresh copy of cart can be created.
             */
            $this->refresh_cart();
        }
        if (!$this->cart) {
            return $this;
        }
        Event::dispatch('checkout.cart.collect.totals.before', $this->cart);
        $this->calculate_items_tax();
        $this->calculate_shipping_tax();
        $this->refresh_cart();
        $this->cart->sub_total = $this->cart->base_sub_total = 0;
        $this->cart->sub_total_incl_tax = $this->cart->base_sub_total_incl_tax = 0;
        $this->cart->grand_total = $this->cart->base_grand_total = 0;
        $this->cart->tax_total = $this->cart->base_tax_total = 0;
        $this->cart->discount_amount = $this->cart->base_discount_amount = 0;
        $this->cart->shipping_amount = $this->cart->base_shipping_amount = 0;
        $this->cart->shipping_amount_incl_tax = $this->cart->base_shipping_amount_incl_tax = 0;
        $quantities = 0;
        foreach ($this->cart->items as $item) {
            $this->cart->discount_amount += $item->discount_amount;
            $this->cart->base_discount_amount += $item->base_discount_amount;
            $this->cart->tax_total += $item->tax_amount;
            $this->cart->base_tax_total += $item->base_tax_amount;
            $this->cart->sub_total = (float) $this->cart->sub_total + $item->total;
            $this->cart->base_sub_total = (float) $this->cart->base_sub_total + $item->base_total;
            $this->cart->sub_total_incl_tax = (float) $this->cart->sub_total_incl_tax + $item->total_incl_tax;
            $this->cart->base_sub_total_incl_tax = (float) $this->cart->base_sub_total_incl_tax + $item->base_total_incl_tax;
            $quantities += $item->quantity;
        }
        $this->cart->items_qty = $quantities;
        $this->cart->items_count = $this->cart->items->count();
        $this->cart->grand_total = $this->cart->sub_total + $this->cart->tax_total - $this->cart->discount_amount;
        $this->cart->base_grand_total = $this->cart->base_sub_total + $this->cart->base_tax_total - $this->cart->base_discount_amount;
        if ($shipping = $this->cart->selected_shipping_rate) {
            $this->cart->tax_total += $shipping->tax_amount;
            $this->cart->base_tax_total += $shipping->base_tax_amount;
            $this->cart->shipping_amount = $shipping->price;
            $this->cart->base_shipping_amount = $shipping->base_price;
            $this->cart->shipping_amount_incl_tax = $shipping->price_incl_tax;
            $this->cart->base_shipping_amount_incl_tax = $shipping->base_price_incl_tax;
            $this->cart->grand_total = (float) $this->cart->grand_total + $shipping->tax_amount + $shipping->price - $shipping->discount_amount;
            $this->cart->base_grand_total = (float) $this->cart->base_grand_total + $shipping->base_tax_amount + $shipping->base_price - $shipping->base_discount_amount;
            $this->cart->discount_amount += $shipping->discount_amount;
            $this->cart->base_discount_amount += $shipping->base_discount_amount;
        }
        $this->cart->discount_amount = round($this->cart->discount_amount, 2);
        $this->cart->base_discount_amount = round($this->cart->base_discount_amount, 2);
        $this->cart->sub_total = round($this->cart->sub_total, 2);
        $this->cart->base_sub_total = round($this->cart->base_sub_total, 2);
        $this->cart->sub_total_incl_tax = round($this->cart->sub_total_incl_tax, 2);
        $this->cart->base_sub_total_incl_tax = round($this->cart->base_sub_total_incl_tax, 2);
        $this->cart->grand_total = round($this->cart->grand_total, 2);
        $this->cart->base_grand_total = round($this->cart->base_grand_total, 2);
        $this->cart->cart_currency_code = core()->get_current_currency_code();
        $this->cart->save();
        Event::dispatch('checkout.cart.collect.totals.after', $this->cart);
        return $this;
    }
    /**
     * To validate if the product information is changed by admin and the items have been added to the cart before it.
     */
    public function validate_items(): bool
    {
        if (!$this->cart) {
            return false;
        }
        if (!$this->cart->items->count()) {
            $this->remove_cart($this->cart);
            return false;
        }
        $is_invalid = false;
        foreach ($this->cart->items as $key => $item) {
            $validation_result = $item->get_type_instance()->validate_cart_item($item);
            if ($validation_result->is_item_inactive()) {
                $this->remove_item($item->id);
                $is_invalid = true;
                session()->flash('info', trans('shop::app.checkout.cart.inactive'));
            } else {
                if (Tax::is_inclusive_tax_product_prices()) {
                    $item_base_price = $item->base_price_incl_tax;
                } else {
                    $item_base_price = $item->base_price;
                }
                $base_price = !is_null($item->custom_price) ? $item->custom_price : $item_base_price;
                $price = core()->convert_price($base_price);
                /**
                 * Reset the item price every time to initial price if the inclusive price is enabled.
                 * Update the item price if exchange rates changes with exclusive price is enabled.
                 */
                if ($price != $item->price) {
                    $item = $this->cart_item_repository->update(['price' => $price, 'price_incl_tax' => $price, 'base_price' => $base_price, 'base_price_incl_tax' => $base_price, 'total' => $total = core()->convert_price($base_price * $item->quantity), 'total_incl_tax' => $total, 'base_total' => $base_total = $base_price * $item->quantity, 'base_total_incl_tax' => $base_total], $item->id);
                    $this->cart->items->put($key, $item);
                }
            }
            $is_invalid |= $validation_result->is_cart_invalid();
        }
        return !$is_invalid;
    }
    /**
     * Calculates cart items tax.
     */
    public function calculate_items_tax(): void
    {
        if (!$this->cart) {
            return;
        }
        Event::dispatch('checkout.cart.calculate.items.tax.before', $this->cart);
        $tax_categories = [];
        foreach ($this->cart->items as $key => $item) {
            $tax_category_id = $item->tax_category_id;
            if (empty($tax_category_id)) {
                $tax_category_id = $item->product->tax_category_id;
            }
            if (empty($tax_category_id)) {
                $tax_category_id = core()->get_config_data('sales.taxes.categories.product');
            }
            if (empty($tax_category_id)) {
                continue;
            }
            if (!isset($tax_categories[$tax_category_id])) {
                $tax_categories[$tax_category_id] = $this->tax_category_repository->find($tax_category_id);
            }
            if (!$tax_categories[$tax_category_id]) {
                continue;
            }
            $calculation_based_on = core()->get_config_data('sales.taxes.calculation.based_on');
            $address = null;
            if ($calculation_based_on == self::TAX_CALCULATION_BASED_ON_SHIPPING_ORIGIN) {
                $address = Tax::get_shipping_origin_address();
            } elseif ($calculation_based_on == self::TAX_CALCULATION_BASED_ON_SHIPPING_ADDRESS) {
                if ($item->get_type_instance()->is_stockable()) {
                    $address = $this->cart->shipping_address;
                } else {
                    $address = $this->cart->billing_address;
                }
            } elseif ($calculation_based_on == self::TAX_CALCULATION_BASED_ON_BILLING_ADDRESS) {
                $address = $this->cart->billing_address;
            }
            if ($address === null && $this->cart->customer) {
                $address = $this->cart->customer->addresses()->where('default_address', 1)->first();
            }
            if ($address === null) {
                $address = Tax::get_default_address();
            }
            $item->applied_tax_rate = null;
            $item->tax_percent = $item->tax_amount = $item->base_tax_amount = 0;
            Tax::is_tax_applicable_in_current_address($tax_categories[$tax_category_id], $address, function ($rate) use ($item, $tax_category_id) {
                $item->applied_tax_rate = $rate->identifier;
                $item->tax_category_id = $tax_category_id;
                $item->tax_percent = $rate->tax_rate;
                if (Tax::is_inclusive_tax_product_prices()) {
                    $item->tax_amount = round($item->total_incl_tax * $rate->tax_rate / (100 + $rate->tax_rate), 4);
                    $item->base_tax_amount = round($item->base_total_incl_tax * $rate->tax_rate / (100 + $rate->tax_rate), 4);
                    $item->total = $item->total_incl_tax - $item->tax_amount;
                    $item->base_total = $item->base_total_incl_tax - $item->base_tax_amount;
                    $item->price = $item->total / $item->quantity;
                    $item->base_price = $item->base_total / $item->quantity;
                } else {
                    $item->tax_amount = round($item->total * $rate->tax_rate / 100, 4);
                    $item->base_tax_amount = round($item->base_total * $rate->tax_rate / 100, 4);
                    $item->total_incl_tax = $item->total + $item->tax_amount;
                    $item->base_total_incl_tax = $item->base_total + $item->base_tax_amount;
                    $item->price_incl_tax = $item->price + $item->tax_amount / $item->quantity;
                    $item->base_price_incl_tax = $item->base_price + $item->base_tax_amount / $item->quantity;
                }
            });
            if (empty($item->applied_tax_rate)) {
                $item->price_incl_tax = $item->price;
                $item->base_price_incl_tax = $item->base_price;
                $item->total_incl_tax = $item->total;
                $item->base_total_incl_tax = $item->base_total;
            }
            $item->save();
            $this->cart->items->put($key, $item);
        }
        Event::dispatch('checkout.cart.calculate.items.tax.after', $this->cart);
    }
    /**
     * Calculates cart shipping tax.
     */
    public function calculate_shipping_tax(): void
    {
        if (!$this->cart) {
            return;
        }
        $shipping_rate = $this->cart->selected_shipping_rate;
        if (!$shipping_rate) {
            return;
        }
        if (!$tax_category_id = core()->get_config_data('sales.taxes.categories.shipping')) {
            return;
        }
        $tax_category = $this->tax_category_repository->find($tax_category_id);
        $calculation_based_on = core()->get_config_data('sales.taxes.calculation.based_on');
        $address = null;
        if ($calculation_based_on == self::TAX_CALCULATION_BASED_ON_SHIPPING_ORIGIN) {
            $address = Tax::get_shipping_origin_address();
        } elseif ($this->cart->have_stockable_items() && $calculation_based_on == self::TAX_CALCULATION_BASED_ON_SHIPPING_ADDRESS) {
            $address = $this->cart->shipping_address;
        } elseif ($calculation_based_on == self::TAX_CALCULATION_BASED_ON_BILLING_ADDRESS) {
            $address = $this->cart->billing_address;
        }
        if ($address === null && $this->cart->customer) {
            $address = $this->cart->customer->addresses()->where('default_address', 1)->first();
        }
        if ($address === null) {
            $address = Tax::get_default_address();
        }
        Event::dispatch('checkout.cart.calculate.shipping.tax.before', $this->cart);
        Tax::is_tax_applicable_in_current_address($tax_category, $address, function ($rate) use ($shipping_rate) {
            $shipping_rate->applied_tax_rate = $rate->identifier;
            $shipping_rate->tax_percent = $rate->tax_rate;
            if (Tax::is_inclusive_tax_shipping_prices()) {
                $shipping_rate->tax_amount = round($shipping_rate->price_incl_tax * $rate->tax_rate / (100 + $rate->tax_rate), 4);
                $shipping_rate->base_tax_amount = round($shipping_rate->base_price_incl_tax * $rate->tax_rate / (100 + $rate->tax_rate), 4);
                $shipping_rate->price = $shipping_rate->price_incl_tax - $shipping_rate->tax_amount;
                $shipping_rate->base_price = $shipping_rate->base_price_incl_tax - $shipping_rate->base_tax_amount;
            } else {
                $shipping_rate->tax_amount = round($shipping_rate->price * $rate->tax_rate / 100, 4);
                $shipping_rate->base_tax_amount = round($shipping_rate->base_price * $rate->tax_rate / 100, 4);
                $shipping_rate->price_incl_tax = $shipping_rate->price + $shipping_rate->tax_amount;
                $shipping_rate->base_price_incl_tax = $shipping_rate->base_price + $shipping_rate->base_tax_amount;
            }
        });
        if (empty($shipping_rate->applied_tax_rate)) {
            $shipping_rate->price_incl_tax = $shipping_rate->price;
            $shipping_rate->base_price_incl_tax = $shipping_rate->base_price;
        }
        $shipping_rate->save();
        Event::dispatch('checkout.cart.calculate.shipping.tax.after', $this->cart);
    }
}