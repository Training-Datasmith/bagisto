<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\Country_State_Translation as CountryStateTranslationContract;
class Country_State_Translation extends Model implements Country_State_Translation_Contract
{
    public $timestamps = false;
    protected $fillable = ['default_name'];
}