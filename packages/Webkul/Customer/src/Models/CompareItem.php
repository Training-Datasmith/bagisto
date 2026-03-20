<?php

declare (strict_types=1);
namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\Compare_Item as CompareItemContract;
use Webkul\Customer\Database\Factories\Compare_Item_Factory;
use Webkul\Product\Models\Product_Proxy;
class Compare_Item extends Model implements Compare_Item_Contract
{
    use Has_Factory;
    /**
     * Guarded
     *
     * @var array
     */
    protected $guarded = [];
    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'compare_items';
    /**
     * The customer that belong to the compare product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongs_to(Customer_Proxy::model_class(), 'customer_id');
    }
    /**
     * The product that belong to the compare product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongs_to(Product_Proxy::model_class(), 'product_id');
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Compare_Item_Factory::new();
    }
}