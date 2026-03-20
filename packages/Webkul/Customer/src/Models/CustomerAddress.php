<?php

declare (strict_types=1);
namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Webkul\Core\Models\Address;
use Webkul\Customer\Contracts\Customer_Address as CustomerAddressContract;
use Webkul\Customer\Database\Factories\Customer_Address_Factory;
class Customer_Address extends Address implements Customer_Address_Contract
{
    use Has_Factory;
    /**
     * Define the customer address type.
     */
    public const ADDRESS_TYPE = 'customer';
    /**
     * Define the attributes of the customer address model.
     *
     * @var array default values
     */
    protected $attributes = ['address_type' => self::ADDRESS_TYPE];
    /**
     * The "booted" method of the model.
     */
    protected static function boot(): void
    {
        static::add_global_scope('address_type', static function (Builder $builder) {
            $builder->where('address_type', self::ADDRESS_TYPE);
        });
        parent::boot();
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Customer_Address_Factory::new();
    }
}