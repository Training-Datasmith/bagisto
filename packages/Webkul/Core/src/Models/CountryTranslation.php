<?php

declare(strict_types=1);

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\CountryTranslation as CountryTranslationContract;

class CountryTranslation extends Model implements CountryTranslationContract
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
