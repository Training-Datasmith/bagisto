<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Helpers\Bundle_Option;
use Webkul\Product\Repositories\Product_Repository;
class Bundle_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Product_Repository $product_repository, protected Bundle_Option $bundle_option_helper)
    {
    }
    /**
     * Returns the compare items of the customer.
     */
    public function options(int $id): Json_Response
    {
        $product = $this->product_repository->find_or_fail($id);
        return new Json_Response(['data' => $this->bundle_option_helper->get_bundle_config($product)]);
    }
}