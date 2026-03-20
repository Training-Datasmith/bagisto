<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers\Customer;

use Illuminate\Http\Resources\Json\Json_Resource;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\Compare_Item_Resource;
use Webkul\Customer\Repositories\Compare_Item_Repository;
class Compare_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Compare_Item_Repository $compare_item_repository)
    {
    }
    /**
     * Returns the compare items of the customer.
     */
    public function items(int $id): Json_Resource
    {
        $compare_items = $this->compare_item_repository->with('product')->where('customer_id', $id)->get();
        return Compare_Item_Resource::collection($compare_items);
    }
    /**
     * Removes the item from the cart if it exists.
     */
    public function destroy(int $id): Json_Resource
    {
        $this->validate(request(), ['item_id' => 'required|exists:compare_items,id']);
        $this->compare_item_repository->delete(request()->input('item_id'));
        return new Json_Resource(['message' => trans('admin::app.customers.customers.view.compare.delete-success')]);
    }
}