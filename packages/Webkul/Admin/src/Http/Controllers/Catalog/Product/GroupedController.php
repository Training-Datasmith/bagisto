<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Repositories\Product_Repository;
class Grouped_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Product_Repository $product_repository)
    {
    }
    /**
     * Returns the compare items of the customer.
     */
    public function options(int $id): Json_Response
    {
        $product = $this->product_repository->find_or_fail($id);
        $options = $product->grouped_products()->order_by('sort_order')->get();
        $products = [];
        foreach ($options as $option) {
            if (!$option->associated_product->get_type_instance()->is_saleable()) {
                continue;
            }
            $products[] = ['id' => $option->associated_product->id, 'name' => $option->associated_product->name, 'qty' => $option->qty, 'price' => $price = $option->associated_product->get_type_instance()->get_final_price(), 'formatted_price' => core()->format_price($price)];
        }
        return new Json_Response(['data' => $products]);
    }
}