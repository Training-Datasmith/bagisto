<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\Json_Resource;
class Attribute_Resource extends Json_Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function to_array($request)
    {
        return ['id' => $this->id, 'code' => $this->code, 'type' => $this->type, 'name' => $this->admin_name, 'options' => Attribute_Option_Resource::collection($this->options)];
    }
}