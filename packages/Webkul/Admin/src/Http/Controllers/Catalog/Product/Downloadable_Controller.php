<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Controllers\Catalog\Product;

use Illuminate\Http\Json_Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Product\Repositories\Product_Repository;
class Downloadable_Controller extends Controller
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
        $links = [];
        foreach ($product->downloadable_links as $link) {
            $links[] = ['id' => $link->id, 'title' => $link->title, 'price' => $link->price, 'formatted_price' => core()->format_price($link->price)];
        }
        return new Json_Response(['data' => $links]);
    }
}