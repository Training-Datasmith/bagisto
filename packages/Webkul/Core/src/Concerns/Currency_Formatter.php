<?php

declare (strict_types=1);
namespace Webkul\Core\Concerns;

use Webkul\Core\Contracts\Currency;
use Webkul\Core\Enums\Currency_Position_Enum;
trait Currency_Formatter
{
    /**
     * Format currency.
     */
    public function format_currency(?float $price, Currency $currency): string
    {
        if ($currency->currency_position) {
            return $this->use_custom_currency_formatter($price, $currency);
        }
        return $this->use_default_currency_formatter($price, $currency);
    }
    /**
     * Use default formatter.
     */
    public function use_default_currency_formatter(?float $price, Currency $currency): string
    {
        $formatter = new \Number_Formatter(app()->get_locale(), \Number_Formatter::CURRENCY);
        if ($currency->symbol) {
            /**
             * If, somehow, the currency symbol mentioned matches with the user-defined symbol,
             * then we can simply use the 'formatCurrency' method.
             */
            if ($this->currency_symbol($currency) == $currency->symbol) {
                return $formatter->format_currency($price, $currency->code);
            }
            $formatter->set_symbol(\Number_Formatter::CURRENCY_SYMBOL, $currency->symbol);
            return $formatter->format($price);
        }
        return $formatter->format_currency($price, $currency->code);
    }
    /**
     * Use custom formatter.
     */
    public function use_custom_currency_formatter(?float $price, Currency $currency): string
    {
        $formatter = new \Number_Formatter(app()->get_locale(), \Number_Formatter::CURRENCY);
        $formatter->set_symbol(\Number_Formatter::CURRENCY_SYMBOL, '');
        $formatter->set_attribute(\Number_Formatter::FRACTION_DIGITS, $currency->decimal ?? 2);
        $formatted_currency = preg_replace('/^\s+|\s+$/u', '', $formatter->format($price));
        if (!empty($currency->group_separator)) {
            $formatted_currency = str_replace($formatter->get_symbol(\Number_Formatter::GROUPING_SEPARATOR_SYMBOL), $currency->group_separator, $formatted_currency);
        }
        if ($currency->decimal > 0 && !empty($currency->decimal_separator)) {
            $formatted_currency = str_replace($formatter->get_symbol(\Number_Formatter::DECIMAL_SEPARATOR_SYMBOL), $currency->decimal_separator, $formatted_currency);
        }
        $symbol = !empty($currency->symbol) ? $currency->symbol : $currency->code;
        return match ($currency->currency_position) {
            Currency_Position_Enum::LEFT->value => $symbol . $formatted_currency,
            Currency_Position_Enum::LEFT_WITH_SPACE->value => $symbol . ' ' . $formatted_currency,
            Currency_Position_Enum::RIGHT->value => $formatted_currency . $symbol,
            Currency_Position_Enum::RIGHT_WITH_SPACE->value => $formatted_currency . ' ' . $symbol,
        };
    }
    /**
     * Return currency symbol from currency code.
     *
     * @param  string|\Webkul\Core\Contracts\Currency  $currency
     */
    public function currency_symbol($currency): string
    {
        $code = $currency instanceof \Webkul\Core\Contracts\Currency ? $currency->code : $currency;
        $formatter = new \Number_Formatter(app()->get_locale() . '@currency=' . $code, \Number_Formatter::CURRENCY);
        return $formatter->get_symbol(\Number_Formatter::CURRENCY_SYMBOL);
    }
}