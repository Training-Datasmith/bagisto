<?php

declare(strict_types=1);

namespace Webkul\BookingProduct\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\BookingProduct\Contracts\BookingProductEventTicketTranslation as BookingProductEventTicketTranslationContract;

class BookingProductEventTicketTranslation extends Model implements BookingProductEventTicketTranslationContract
{
    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'description',
    ];
}
