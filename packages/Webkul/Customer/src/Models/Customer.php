<?php

declare (strict_types=1);
namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Has_Api_Tokens;
use Shetabit\Visitor\Traits\Visitor;
use Webkul\Checkout\Models\Cart_Proxy;
use Webkul\Core\Models\Channel_Proxy;
use Webkul\Core\Models\Subscribers_List_Proxy;
use Webkul\Customer\Contracts\Customer as CustomerContract;
use Webkul\Customer\Database\Factories\Customer_Factory;
use Webkul\Product\Models\Product_Review_Proxy;
use Webkul\Sales\Models\Invoice_Proxy;
use Webkul\Sales\Models\Order_Proxy;
use Webkul\Shop\Mail\Customer\Reset_Password_Notification;
class Customer extends Authenticatable implements Customer_Contract
{
    use Has_Api_Tokens;
    use Has_Factory;
    use Notifiable;
    use Visitor;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['subscribed_to_news_letter' => 'boolean'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['first_name', 'last_name', 'gender', 'date_of_birth', 'email', 'phone', 'password', 'api_token', 'token', 'customer_group_id', 'channel_id', 'subscribed_to_news_letter', 'status', 'is_verified', 'is_suspended'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ['password', 'api_token', 'remember_token'];
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['image_url'];
    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     */
    public function send_password_reset_notification($token): void
    {
        $this->notify(new Reset_Password_Notification($token));
    }
    /**
     * Get image url for the customer profile.
     *
     * @return string|null
     */
    public function get_image_url_attribute()
    {
        return $this->image_url();
    }
    /**
     * Get the customer full name.
     */
    public function get_name_attribute(): string
    {
        return ucfirst($this->first_name) . ' ' . ucfirst($this->last_name);
    }
    /**
     * Get image url for the customer image.
     *
     * @return string|null
     */
    public function image_url()
    {
        if (!$this->image) {
            return;
        }
        return Storage::url($this->image);
    }
    /**
     * Is email exists or not.
     *
     * @param  string  $email
     */
    public function email_exists($email): bool
    {
        $results = $this->where('email', $email);
        if ($results->count() === 0) {
            return false;
        }
        return true;
    }
    /**
     * Get the customer group that owns the customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongs_to(Customer_Group_Proxy::model_class(), 'customer_group_id');
    }
    /**
     * Get the customer address that owns the customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addresses()
    {
        return $this->has_many(Customer_Address_Proxy::model_class(), 'customer_id');
    }
    /**
     * Get default customer address that owns the customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function default_address()
    {
        return $this->has_one(Customer_Address_Proxy::model_class(), 'customer_id')->where('default_address', 1);
    }
    /**
     * Customer's relation with invoice .
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasManyThrough
     */
    public function invoices()
    {
        return $this->has_many_through(Invoice_Proxy::model_class(), Order_Proxy::model_class());
    }
    /**
     * Customer's relation with wishlist items.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function wishlist_items()
    {
        return $this->has_many(Wishlist_Proxy::model_class(), 'customer_id');
    }
    /**
     * Is wishlist shared by the customer.
     */
    public function is_wishlist_shared(): bool
    {
        return (bool) $this->wishlist_items()->where('shared', 1)->first();
    }
    /**
     * Get wishlist shared link.
     *
     * @return string|null
     */
    public function get_wishlist_shared_link()
    {
        return $this->is_wishlist_shared() ? URL::signed_route('shop.customer.wishlist.shared', ['id' => $this->id]) : null;
    }
    /**
     * Get all cart inactive cart instance of a customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function all_carts()
    {
        return $this->has_many(Cart_Proxy::model_class(), 'customer_id');
    }
    /**
     * Get inactive cart instance of a customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inactive_carts()
    {
        return $this->has_many(Cart_Proxy::model_class(), 'customer_id')->where('is_active', 0);
    }
    /**
     * Get active cart instance of a customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function active_carts()
    {
        return $this->has_many(Cart_Proxy::model_class(), 'customer_id')->where('is_active', 1);
    }
    /**
     * Get all orders of a customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->has_many(Order_Proxy::model_class(), 'customer_id');
    }
    /**
     * Get all reviews of a customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->has_many(Product_Review_Proxy::model_class(), 'customer_id');
    }
    /**
     * Get all notes of a customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notes()
    {
        return $this->has_many(Customer_Note_Proxy::model_class(), 'customer_id');
    }
    /**
     * Get the customer's subscription.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function subscription()
    {
        return $this->has_one(Subscribers_List_Proxy::model_class(), 'customer_id');
    }
    /**
     * Get the channel that owns the customer.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel()
    {
        return $this->belongs_to(Channel_Proxy::model_class(), 'channel_id');
    }
    /**
     * Create a new factory instance for the model.
     *
     * @return \Webkul\Customer\Database\Factories\CustomerFactory
     */
    protected static function new_factory()
    {
        return Customer_Factory::new();
    }
}