<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Has_One;
use Webkul\Core\Contracts\Currency as CurrencyContract;
use Webkul\Core\Database\Factories\Currency_Factory;
class Currency extends Model implements Currency_Contract
{
    use Has_Factory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'symbol', 'decimal', 'group_separator', 'decimal_separator', 'currency_position'];
    /**
     * Set currency code in capital letter.
     */
    public function set_code_attribute($code): void
    {
        $this->attributes['code'] = strtoupper($code);
    }
    /**
     * Get the exchange rate associated with the currency.
     */
    public function exchange_rate(): Has_One
    {
        return $this->has_one(Currency_Exchange_Rate_Proxy::model_class(), 'target_currency');
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Currency_Factory::new();
    }
}