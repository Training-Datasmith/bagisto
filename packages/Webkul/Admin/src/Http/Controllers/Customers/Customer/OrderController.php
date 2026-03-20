<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Customers\Customer;

use Illuminate\Http\Resources\Json\Json_Resource;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Resources\Order_Item_Resource;
use Webkul\Sales\Repositories\Order_Item_Repository;
class Order_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Order_Item_Repository $order_item_repository)
    {
    }
    /**
     * Returns the compare items of the customer.
     */
    public function recent_items(int $id): Json_Resource
    {
        $order_items = $this->order_item_repository->distinct('order_items.product_id')->left_join('orders', 'order_items.order_id', 'orders.id')->where_null('order_items.parent_id')->where('orders.customer_id', $id)->order_by('orders.created_at', 'desc')->limit(5)->get();
        return Order_Item_Resource::collection($order_items);
    }
}