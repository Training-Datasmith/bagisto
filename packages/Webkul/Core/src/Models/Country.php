<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Webkul\Core\Contracts\Country as CountryContract;
use Webkul\Core\Eloquent\Translatable_Model;
class Country extends Translatable_Model implements Country_Contract
{
    public $timestamps = false;
    public $translated_attributes = ['name'];
    protected $with = ['translations'];
    /**
     * Get the States.
     */
    public function states()
    {
        return $this->has_many(Country_State_Proxy::model_class());
    }
}