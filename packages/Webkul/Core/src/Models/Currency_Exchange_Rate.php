<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Webkul\Admin\Database\Factories\Currency_Exchange_Rate_Factory;
use Webkul\Core\Contracts\Currency_Exchange_Rate as CurrencyExchangeRateContract;
class Currency_Exchange_Rate extends Model implements Currency_Exchange_Rate_Contract
{
    use Has_Factory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['target_currency', 'rate'];
    /**
     * Get the exchange rate associated with the currency.
     */
    public function currency(): Belongs_To
    {
        return $this->belongs_to(Currency_Proxy::model_class(), 'target_currency');
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Currency_Exchange_Rate_Factory::new();
    }
}