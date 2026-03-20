<?php

declare (strict_types=1);
namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Models\Channel_Proxy;
use Webkul\Customer\Contracts\Wishlist as WishlistContract;
use Webkul\Customer\Database\Factories\Customer_Wishlist_Factory;
use Webkul\Product\Models\Product_Proxy;
class Wishlist extends Model implements Wishlist_Contract
{
    use Has_Factory;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wishlist_items';
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['additional' => 'array'];
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];
    /**
     * The product that belong to the wishlist.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this->belongs_to(Product_Proxy::model_class());
    }
    /**
     * The Channel that belong to the wishlist.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function channel()
    {
        return $this->has_one(Channel_Proxy::model_class(), 'id', 'channel_id');
    }
    /**
     * The Customer that belong to the wishlist.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function customer()
    {
        return $this->belongs_to(Customer_Proxy::model_class(), 'customer_id');
    }
    /**
     * Create a new factory instance for the model
     *
     * @return Factory
     */
    protected static function new_factory()
    {
        return Customer_Wishlist_Factory::new();
    }
}