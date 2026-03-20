<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers\Customer;

use Illuminate\Http\Resources\Json\Json_Resource;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\Wishlist_Item_Resource;
use Webkul\Customer\Repositories\Wishlist_Repository;
class Wishlist_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Wishlist_Repository $wishlist_repository)
    {
    }
    /**
     * Returns the compare items of the customer.
     */
    public function items(int $id): Json_Resource
    {
        $wishlist_items = $this->wishlist_repository->with('product')->where('customer_id', $id)->get();
        return Wishlist_Item_Resource::collection($wishlist_items);
    }
    /**
     * Removes the item from the cart if it exists.
     */
    public function destroy(int $id): Json_Resource
    {
        $this->validate(request(), ['item_id' => 'required|exists:wishlist_items,id']);
        $item_id = request()->input('item_id');
        Event::dispatch('customer.wishlist.delete.before', $item_id);
        $this->wishlist_repository->delete(request()->input('item_id'));
        Event::dispatch('customer.wishlist.delete.after', $item_id);
        return new Json_Resource(['message' => trans('admin::app.customers.customers.view.wishlist.delete-success')]);
    }
}