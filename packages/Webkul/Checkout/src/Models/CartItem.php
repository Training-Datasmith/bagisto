<?php

declare (strict_types=1);
namespace Webkul\Checkout\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Illuminate\Database\Eloquent\Relations\Has_Many;
use Illuminate\Database\Eloquent\Relations\Has_One;
use Webkul\Checkout\Contracts\Cart_Item as CartItemContract;
use Webkul\Checkout\Database\Factories\Cart_Item_Factory;
use Webkul\Product\Models\Product_Proxy;
use Webkul\Product\Type\Abstract_Type;
class Cart_Item extends Model implements Cart_Item_Contract
{
    use Has_Factory;
    /**
     * Cast the additional attribute to an array.
     */
    protected $casts = ['additional' => 'array'];
    /**
     * Guarded attributes.
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];
    /**
     * Type instance.
     */
    protected $type_instance;
    /**
     * Retrieve type instance.
     */
    public function get_type_instance(): Abstract_Type
    {
        if ($this->type_instance) {
            return $this->type_instance;
        }
        $this->type_instance = app(config('product_types.' . $this->type . '.class'));
        if ($this->product) {
            $this->type_instance->set_product($this->product);
        }
        return $this->type_instance;
    }
    /**
     * Get the product record associated with the cart item.
     */
    public function product(): Has_One
    {
        return $this->has_one(Product_Proxy::model_class(), 'id', 'product_id');
    }
    /**
     * Get the cart record associated with the cart item.
     */
    public function cart(): Has_One
    {
        return $this->has_one(Cart_Proxy::model_class(), 'id', 'cart_id');
    }
    /**
     * Get the parent item record associated with the cart item.
     */
    public function parent(): Belongs_To
    {
        return $this->belongs_to(self::class, 'parent_id');
    }
    /**
     * Get the child item, this is for configurable products.
     */
    public function child(): Belongs_To
    {
        return $this->belongs_to(static::class, 'id', 'parent_id');
    }
    /**
     * Get the children items, this is for bundle products.
     */
    public function children(): Has_Many
    {
        return $this->has_many(self::class, 'parent_id');
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Cart_Item_Factory::new();
    }
}