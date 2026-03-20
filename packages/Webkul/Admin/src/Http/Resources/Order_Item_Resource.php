<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\Json_Resource;
class Order_Item_Resource extends Json_Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function to_array($request)
    {
        return ['id' => $this->id, 'order_id' => $this->order_id, 'additional' => (object) $this->resource->additional ?? [], 'product' => new Product_Resource($this->product)];
    }
}