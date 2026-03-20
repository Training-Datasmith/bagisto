<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Webkul\Core\Contracts\Address as AddressContract;
use Webkul\Customer\Models\Customer;
abstract class Address extends Model implements Address_Contract
{
    /**
     * Table.
     *
     * @var string
     */
    protected $table = 'addresses';
    /**
     * Guarded.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];
    /**
     * Castable.
     *
     * @var array
     */
    protected $casts = ['use_for_shipping' => 'boolean', 'default_address' => 'boolean'];
    /**
     * Get all the attributes for the attribute groups.
     */
    public function get_name_attribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    /**
     * Get the customer record associated with the address.
     */
    public function customer(): Belongs_To
    {
        return $this->belongs_to(Customer::class);
    }
}