<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Webkul\Core\Contracts\Country_State as CountryStateContract;
use Webkul\Core\Eloquent\Translatable_Model;
class Country_State extends Translatable_Model implements Country_State_Contract
{
    public $timestamps = false;
    public $translated_attributes = ['default_name'];
    protected $with = ['translations'];
    /**
     * @return array
     */
    public function to_array()
    {
        $array = parent::to_array();
        $array['default_name'] = $this->default_name;
        return $array;
    }
}