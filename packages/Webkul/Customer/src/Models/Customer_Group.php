<?php

declare (strict_types=1);
namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Has_Many;
use Webkul\Customer\Contracts\Customer_Group as CustomerGroupContract;
use Webkul\Customer\Database\Factories\Customer_Group_Factory;
class Customer_Group extends Model implements Customer_Group_Contract
{
    use Has_Factory;
    /**
     * Deinfine model table name.
     *
     * @var string
     */
    protected $table = 'customer_groups';
    /**
     * Fillable property for the model.
     *
     * @var array
     */
    protected $fillable = ['name', 'code', 'is_user_defined'];
    /**
     * Get the customers for this group.
     */
    public function customers(): Has_Many
    {
        return $this->has_many(Customer_Proxy::model_class());
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Customer_Group_Factory::new();
    }
}