<?php

declare (strict_types=1);
namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\Json_Resource;
class Category_Tree_Resource extends Json_Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function to_array($request)
    {
        return ['id' => $this->id, 'parent_id' => $this->parent_id, 'name' => $this->name, 'slug' => $this->slug, 'url' => $this->url, 'status' => $this->status, 'children' => self::collection($this->children)];
    }
}