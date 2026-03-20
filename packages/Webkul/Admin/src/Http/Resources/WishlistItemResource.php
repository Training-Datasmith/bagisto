<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\Json_Resource;
class Wishlist_Item_Resource extends Json_Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function to_array($request)
    {
        return ['id' => $this->id, 'additional' => (object) $this->resource->additional ?? [], 'product' => new Product_Resource($this->product)];
    }
}