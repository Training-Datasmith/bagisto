<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Helpers\Configurable_Option;
use Webkul\Product\Repositories\Product_Repository;
class Configurable_Controller extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected Product_Repository $product_repository, protected Configurable_Option $configurable_option_helper)
    {
    }
    /**
     * Returns the compare items of the customer.
     */
    public function options(int $id): Json_Response
    {
        $product = $this->product_repository->find_or_fail($id);
        return new Json_Response(['data' => $this->configurable_option_helper->get_configuration_config($product)]);
    }
}