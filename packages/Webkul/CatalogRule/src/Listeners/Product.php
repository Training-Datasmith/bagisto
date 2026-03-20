<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Listeners;

use Webkul\Catalog_Rule\Jobs\Update_Create_Product_Index as UpdateCreateProductIndexJob;
class Product
{
    /**
     * @param  \Webkul\Product\Contracts\Product  $product
     * @return void
     */
    public function after_update($product)
    {
        Update_Create_Product_Index_Job::dispatch($product);
    }
}