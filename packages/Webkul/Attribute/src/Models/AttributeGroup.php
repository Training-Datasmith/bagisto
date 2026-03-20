<?php

declare (strict_types=1);
namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Contracts\Attribute_Group as AttributeGroupContract;
class Attribute_Group extends Model implements Attribute_Group_Contract
{
    public $timestamps = false;
    protected $fillable = ['code', 'name', 'column', 'position', 'is_user_defined'];
    /**
     * Get the attributes that owns the attribute group.
     */
    public function custom_attributes()
    {
        return $this->belongs_to_many(Attribute_Proxy::model_class(), 'attribute_group_mappings')->with_pivot('position')->order_by('pivot_position', 'asc');
    }
}