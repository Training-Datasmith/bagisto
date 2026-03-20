<?php

declare (strict_types=1);
namespace Webkul\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Customer\Contracts\Customer_Note as CustomerNoteContract;
class Customer_Note extends Model implements Customer_Note_Contract
{
    protected $fillable = ['note', 'customer_id', 'customer_notified'];
    /**
     * Get the order record associated with the order comment.
     */
    public function customer()
    {
        return $this->belongs_to(Customer_Proxy::model_class());
    }
}