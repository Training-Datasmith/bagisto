<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers\Customer;

use Illuminate\Http\Resources\Json\Json_Resource;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\Cart_Item_Resource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\Cart_Item_Repository;
use Webkul\Customer\Repositories\Customer_Repository;
class Cart_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Customer_Repository $customer_repository, protected Cart_Item_Repository $cart_item_repository)
    {
    }
    /**
     * Create cart
     */
    public function store(int $id)
    {
        $customer = $this->customer_repository->find_or_fail($id);
        try {
            $cart = Cart::create_cart(['customer' => $customer, 'is_active' => false]);
            return redirect()->route('admin.sales.orders.create', $cart->id);
        } catch (\Exception $exception) {
            session()->flash('error', $exception->get_message());
            return redirect()->back();
        }
    }
    /**
     * Returns the compare items of the customer.
     */
    public function items(int $id): Json_Resource
    {
        $cart_items = $this->cart_item_repository->with('product')->select('cart_items.*')->left_join('cart', 'cart_items.cart_id', 'cart.id')->where_null('cart_items.parent_id')->where('cart.customer_id', $id)->where('cart.is_active', 1)->get();
        return Cart_Item_Resource::collection($cart_items);
    }
    /**
     * Removes the item from the cart if it exists.
     */
    public function destroy(int $id): Json_Resource
    {
        $this->validate(request(), ['item_id' => 'required|exists:cart_items,id']);
        $cart_item = $this->cart_item_repository->find_or_fail(request()->input('item_id'));
        Cart::set_cart($cart_item->cart);
        Cart::remove_item($cart_item->id);
        Cart::collect_totals();
        return new Json_Resource(['message' => trans('admin::app.customers.customers.view.cart.delete-success')]);
    }
}