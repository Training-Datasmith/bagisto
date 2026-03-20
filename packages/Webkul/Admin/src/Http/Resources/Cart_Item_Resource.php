<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\Json_Resource;
class Cart_Item_Resource extends Json_Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function to_array($request)
    {
        return ['id' => $this->id, 'cart_id' => $this->cart_id, 'product_id' => $this->product_id, 'sku' => $this->sku, 'quantity' => $this->quantity, 'type' => $this->type, 'name' => $this->name, 'price' => $this->base_price, 'formatted_price' => core()->format_price($this->base_price), 'price_incl_tax' => $this->base_price_incl_tax, 'formatted_price_incl_tax' => core()->format_price($this->base_price_incl_tax), 'total' => $this->base_total, 'formatted_total' => core()->format_price($this->base_total), 'total_incl_tax' => $this->base_total_incl_tax, 'formatted_total_incl_tax' => core()->format_price($this->base_total_incl_tax), 'options' => array_values($this->resource->additional['attributes'] ?? []), 'additional' => (object) $this->resource->additional, 'product' => new Product_Resource($this->product)];
    }
}