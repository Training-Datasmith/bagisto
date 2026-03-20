<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\Json_Resource;
class Attribute_Option_Resource extends Json_Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function to_array($request)
    {
        return ['id' => $this->id, 'name' => $this->label ?? $this->admin_name];
    }
}