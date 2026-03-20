<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\Country_Translation as CountryTranslationContract;
class Country_Translation extends Model implements Country_Translation_Contract
{
    public $timestamps = false;
    protected $fillable = ['name'];
}