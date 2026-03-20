<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Repositories\Product_Repository;
class Simple_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Product_Repository $product_repository)
    {
    }
    /**
     * Returns the customizable options of the product.
     */
    public function customizable_options(int $id): Json_Response
    {
        $product = $this->product_repository->find_or_fail($id);
        return new Json_Response(['data' => $product->customizable_options()->with(['product', 'customizable_option_prices'])->get(), 'meta' => ['initial_price' => $product->get_type_instance()->get_minimal_price()]]);
    }
}