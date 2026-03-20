<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Webkul\Core\Contracts\Subscribers_List as SubscribersListContract;
use Webkul\Core\Database\Factories\Subscriber_List_Factory;
use Webkul\Customer\Models\Customer_Proxy;
class Subscribers_List extends Model implements Subscribers_List_Contract
{
    use Has_Factory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'subscribers_list';
    /**
     * Fillable properties of the model.
     *
     * @var array
     */
    protected $fillable = ['email', 'is_subscribed', 'token', 'customer_id', 'channel_id'];
    /**
     * Hide the token attribute to the model.
     *
     * @var array
     */
    protected $hidden = ['token'];
    /**
     * Get the customer associated with the subscription.
     */
    public function customer(): Belongs_To
    {
        return $this->belongs_to(Customer_Proxy::model_class());
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Subscriber_List_Factory::new();
    }
}