<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\Json_Resource;
class Product_Resource extends Json_Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function to_array($request)
    {
        return ['id' => $this->id, 'type' => $this->type, 'sku' => $this->sku, 'name' => $this->name, 'price' => $this->price, 'formatted_price' => core()->format_price($this->price), 'images' => $this->images, 'inventories' => $this->inventories, 'is_options_required' => !$this->get_type_instance()->can_be_added_to_cart_without_options(), 'is_saleable' => $this->get_type_instance()->is_saleable()];
    }
}