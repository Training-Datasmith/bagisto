<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\Json_Response;
use Illuminate\Http\Resources\Json\Json_Resource;
use Illuminate\Http\Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Cart_Address_Request;
use Webkul\Admin\Http\Resources\Cart_Resource;
use Webkul\Cart_Rule\Repositories\Cart_Rule_Coupon_Repository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\Cart_Repository;
use Webkul\Customer\Repositories\Customer_Repository;
use Webkul\Payment\Facades\Payment;
use Webkul\Product\Repositories\Product_Repository;
use Webkul\Shipping\Facades\Shipping;
class Cart_Controller extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Cart_Repository $cart_repository, protected Customer_Repository $customer_repository, protected Product_Repository $product_repository, protected Cart_Rule_Coupon_Repository $cart_rule_coupon_repository)
    {
    }
    /**
     * Cart.
     */
    public function index(int $id): Json_Resource
    {
        $cart = $this->cart_repository->find_or_fail($id);
        $response = ['data' => new Cart_Resource($cart)];
        if (session()->has('info')) {
            $response['message'] = session()->get('info');
        }
        return new Json_Resource($response);
    }
    /**
     * Create cart
     */
    public function store(): Json_Resource
    {
        $customer = $this->customer_repository->find_or_fail(request()->input('customer_id'));
        try {
            $cart = Cart::create_cart(['customer' => $customer, 'is_active' => false]);
            return new Json_Resource(['data' => new Cart_Resource($cart), 'redirect_url' => route('admin.sales.orders.create', $cart->id)]);
        } catch (\Exception $exception) {
            return new Json_Resource(['message' => $exception->get_message()]);
        }
    }
    /**
     * Store items in cart.
     */
    public function store_item(int $cart_id): Json_Resource
    {
        $this->validate(request(), ['product_id' => 'required|integer|exists:products,id']);
        $cart = $this->cart_repository->find_or_fail($cart_id);
        Cart::set_cart($cart);
        try {
            $params = request()->all();
            $product = $this->product_repository->find_or_fail($params['product_id']);
            Cart::add_product($product, $params);
            return new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.cart.success-add-to-cart')]);
        } catch (\Exception $exception) {
            return new Json_Resource(['message' => $exception->get_message()]);
        }
    }
    /**
     * Removes the item from the cart if it exists.
     */
    public function destroy_item(int $cart_id): Json_Resource
    {
        $this->validate(request(), ['cart_item_id' => 'required|exists:cart_items,id']);
        $cart = $this->cart_repository->find_or_fail($cart_id);
        Cart::set_cart($cart);
        Cart::remove_item(request()->input('cart_item_id'));
        Cart::collect_totals();
        return new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.cart.success-remove')]);
    }
    /**
     * Updates the quantity of the items present in the cart.
     */
    public function update_item(int $cart_id): Json_Resource
    {
        $cart = $this->cart_repository->find_or_fail($cart_id);
        Cart::set_cart($cart);
        try {
            Cart::update_items(request()->input());
            return new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.cart.success-update')]);
        } catch (\Exception $exception) {
            return new Json_Resource(['message' => $exception->get_message()]);
        }
    }
    /**
     * Store address.
     */
    public function store_address(Cart_Address_Request $cart_address_request, int $id): Json_Resource|Json_Response
    {
        $cart = $this->cart_repository->find_or_fail($id);
        $params = $cart_address_request->all();
        Cart::set_cart($cart);
        if (Cart::has_error()) {
            return new Json_Response(['message' => implode(': ', Cart::get_errors()) ?: 'Something went wrong'], Response::HTTP_BAD_REQUEST);
        }
        Cart::save_addresses($params);
        Cart::collect_totals();
        if ($cart->have_stockable_items()) {
            if (!$rates = Shipping::collect_rates()) {
                return new Json_Resource(['redirect' => true, 'redirect_url' => route('shop.checkout.cart.index')]);
            }
            return new Json_Resource(['redirect' => false, 'data' => $rates]);
        }
        return new Json_Resource(['redirect' => false, 'data' => Payment::get_supported_payment_methods()]);
    }
    /**
     * Store shipping method.
     *
     * @return \Illuminate\Http\Response
     */
    public function store_shipping_method(int $id)
    {
        $validated_data = $this->validate(request(), ['shipping_method' => 'required']);
        $cart = $this->cart_repository->find_or_fail($id);
        Cart::set_cart($cart);
        if (Cart::has_error() || !$validated_data['shipping_method'] || !Cart::save_shipping_method($validated_data['shipping_method'])) {
            return response()->json(['redirect_url' => route('shop.checkout.cart.index')], Response::HTTP_FORBIDDEN);
        }
        Cart::collect_totals();
        return response()->json(Payment::get_supported_payment_methods());
    }
    /**
     * Store payment method.
     *
     * @return array
     */
    public function store_payment_method(int $id)
    {
        $validated_data = $this->validate(request(), ['payment' => 'required']);
        $cart = $this->cart_repository->find_or_fail($id);
        Cart::set_cart($cart);
        if (Cart::has_error() || !$validated_data['payment'] || !Cart::save_payment_method($validated_data['payment'])) {
            return response()->json(['redirect_url' => route('shop.checkout.cart.index')], Response::HTTP_FORBIDDEN);
        }
        Cart::collect_totals();
        $cart = Cart::get_cart();
        return ['cart' => new Cart_Resource($cart)];
    }
    /**
     * Apply coupon to the cart.
     */
    public function store_coupon(int $id)
    {
        $params = $this->validate(request(), ['code' => 'required']);
        $cart = $this->cart_repository->find_or_fail($id);
        Cart::set_cart($cart);
        try {
            $coupon = $this->cart_rule_coupon_repository->find_one_by_field('code', $params['code']);
            if (!$coupon) {
                return (new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.coupon-not-found')]))->response()->set_status_code(Response::HTTP_NOT_FOUND);
            }
            if ($coupon->cart_rule->status) {
                if (Cart::get_cart()->coupon_code == $coupon->code) {
                    return (new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.coupon-already-applied')]))->response()->set_status_code(Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                Cart::set_coupon_code($coupon->code)->collect_totals();
                if (Cart::get_cart()->coupon_code == $coupon->code) {
                    return new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.coupon-applied')]);
                }
            }
            return (new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.coupon-not-found')]))->response()->set_status_code(Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return (new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.coupon-error')]))->response()->set_status_code(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Remove applied coupon from the cart.
     */
    public function destroy_coupon(int $id): Json_Resource
    {
        $cart = $this->cart_repository->find_or_fail($id);
        Cart::set_cart($cart);
        Cart::remove_coupon_code()->collect_totals();
        return new Json_Resource(['data' => new Cart_Resource(Cart::get_cart()), 'message' => trans('admin::app.sales.orders.create.coupon-remove')]);
    }
}