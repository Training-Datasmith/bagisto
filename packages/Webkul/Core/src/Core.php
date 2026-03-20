<?php

declare (strict_types=1);
namespace Webkul\Core;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Concerns\Currency_Formatter;
use Webkul\Core\Models\Channel;
use Webkul\Core\Repositories\Channel_Repository;
use Webkul\Core\Repositories\Country_Repository;
use Webkul\Core\Repositories\Country_State_Repository;
use Webkul\Core\Repositories\Currency_Repository;
use Webkul\Core\Repositories\Exchange_Rate_Repository;
use Webkul\Core\Repositories\Locale_Repository;
use Webkul\Customer\Repositories\Customer_Group_Repository;
use Webkul\Tax\Repositories\Tax_Category_Repository;
class Core
{
    use Currency_Formatter;
    /**
     * The Bagisto version.
     *
     * @var string
     */
    public const BAGISTO_VERSION = '2.3.13';
    /**
     * Current Channel.
     *
     * @var \Webkul\Core\Models\Channel
     */
    protected $current_channel;
    /**
     * Default Channel.
     *
     * @var \Webkul\Core\Models\Channel
     */
    protected $default_channel;
    /**
     * Currency.
     *
     * @var \Webkul\Core\Models\Currency
     */
    protected $current_currency;
    /**
     * Base Currency.
     *
     * @var \Webkul\Core\Models\Currency
     */
    protected $base_currency;
    /**
     * Current Locale.
     *
     * @var \Webkul\Core\Models\Locale
     */
    protected $current_locale;
    /**
     * Guest Customer Group
     *
     * @var \Webkul\Customer\Models\CustomerGroup
     */
    protected $guest_customer_group;
    /**
     * Exchange rates
     *
     * @var array
     */
    protected $exchange_rates = [];
    /**
     * Exchange rates
     *
     * @var array
     */
    protected $tax_categories_by_id = [];
    /**
     * Stores singleton instances
     *
     * @var array
     */
    protected $singleton_instances = [];
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct(protected Channel_Repository $channel_repository, protected Currency_Repository $currency_repository, protected Exchange_Rate_Repository $exchange_rate_repository, protected Country_Repository $country_repository, protected Country_State_Repository $country_state_repository, protected Locale_Repository $locale_repository, protected Customer_Group_Repository $customer_group_repository, protected Tax_Category_Repository $tax_category_repository)
    {
    }
    /**
     * Get the version number of the Bagisto.
     *
     * @return string
     */
    public function version()
    {
        return static::BAGISTO_VERSION;
    }
    /**
     * Returns all channels.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_all_channels()
    {
        return $this->channel_repository->all();
    }
    /**
     * Returns current channel models.
     *
     * @return \Webkul\Core\Contracts\Channel
     */
    public function get_current_channel(?string $hostname = null)
    {
        if (!$hostname) {
            $hostname = request()->get_http_host();
        }
        if ($this->current_channel) {
            return $this->current_channel;
        }
        $this->current_channel = $this->channel_repository->find_where_in('hostname', [$hostname, 'http://' . $hostname, 'https://' . $hostname])->first();
        if (!$this->current_channel) {
            $this->current_channel = $this->channel_repository->first();
        }
        return $this->current_channel;
    }
    /**
     * Set the current channel.
     */
    public function set_current_channel(Channel $channel): void
    {
        $this->current_channel = $channel;
    }
    /**
     * Returns current channel code.
     */
    public function get_current_channel_code(): string
    {
        return $this->get_current_channel()?->code;
    }
    /**
     * Returns default channel models.
     *
     * @return \Webkul\Core\Contracts\Channel
     */
    public function get_default_channel(): ?Channel
    {
        if ($this->default_channel) {
            return $this->default_channel;
        }
        $this->default_channel = $this->channel_repository->find_one_by_field('code', config('app.channel'));
        if ($this->default_channel) {
            return $this->default_channel;
        }
        return $this->default_channel = $this->channel_repository->first();
    }
    /**
     * Set the default channel.
     */
    public function set_default_channel(Channel $channel): void
    {
        $this->default_channel = $channel;
    }
    /**
     * Returns the default channel code configured in `config/app.php`.
     */
    public function get_default_channel_code(): string
    {
        return $this->get_default_channel()?->code;
    }
    /**
     * Returns default locale code from default channel.
     */
    public function get_default_locale_code_from_default_channel(): string
    {
        return $this->get_default_channel()->default_locale->code;
    }
    /**
     * Get channel code from request.
     *
     * @return \Webkul\Core\Contracts\Channel
     */
    public function get_requested_channel()
    {
        $code = request()->query('channel');
        if ($code) {
            return $this->channel_repository->find_one_by_field('code', $code);
        }
        return $this->get_current_channel();
    }
    /**
     * Get channel code from request.
     *
     * @param  bool  $fallback  optional
     * @return string
     */
    public function get_requested_channel_code($fallback = true)
    {
        $channel_code = request()->get('channel');
        if (!$fallback) {
            return $channel_code;
        }
        return $channel_code ?: ($this->get_current_channel_code() ?: $this->get_default_channel_code());
    }
    /**
     * Returns the channel name.
     */
    public function get_channel_name($channel): string
    {
        return $channel->name ?? $channel->translate(app()->get_locale())->name ?? $channel->translate(config('app.fallback_locale'))->name;
    }
    /**
     * Return all locales.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_all_locales()
    {
        return $this->locale_repository->all()->sort_by('name');
    }
    /**
     * Returns current locale.
     *
     * @return \Webkul\Core\Contracts\Locale
     */
    public function get_current_locale()
    {
        if ($this->current_locale) {
            return $this->current_locale;
        }
        $this->current_locale = $this->locale_repository->find_one_by_field('code', app()->get_locale());
        if (!$this->current_locale) {
            $this->current_locale = $this->locale_repository->find_one_by_field('code', config('app.fallback_locale'));
        }
        return $this->current_locale;
    }
    /**
     * Get locale from request.
     *
     * @return string
     */
    public function get_requested_locale()
    {
        $code = request()->query('locale');
        if ($code) {
            return $this->locale_repository->find_one_by_field('code', $code);
        }
        return $this->get_current_locale();
    }
    /**
     * Get locale code from request. Here if you want to use admin locale,
     * you can pass it as an argument.
     *
     * @param  string  $localeKey  optional
     * @param  bool  $fallback  optional
     * @return string
     */
    public function get_requested_locale_code($locale_key = 'locale', $fallback = true)
    {
        $locale_code = request()->get($locale_key);
        if (!$fallback) {
            return $locale_code;
        }
        return $locale_code ?: app()->get_locale();
    }
    /**
     * Check requested locale code in requested channel. If not found,
     * then set channel default locale code.
     *
     * @return string
     */
    public function get_requested_locale_code_in_requested_channel()
    {
        $requested_locale_code = $this->get_requested_locale_code();
        $requested_channel = $this->get_requested_channel();
        if ($requested_channel->locales->contains('code', $requested_locale_code)) {
            return $requested_locale_code;
        }
        return $requested_channel->default_locale->code;
    }
    /**
     * Returns all currencies.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get_all_currencies()
    {
        return $this->currency_repository->all();
    }
    /**
     * Returns base channel's currency model.
     *
     * @return \Webkul\Core\Contracts\Currency
     */
    public function get_base_currency()
    {
        if ($this->base_currency) {
            return $this->base_currency;
        }
        $this->base_currency = $this->currency_repository->find_one_by_field('code', config('app.currency'));
        if (!$this->base_currency) {
            $this->base_currency = $this->currency_repository->first();
        }
        return $this->base_currency;
    }
    /**
     * Returns base channel's currency code.
     *
     * @return string
     */
    public function get_base_currency_code()
    {
        return $this->get_base_currency()?->code;
    }
    /**
     * Returns base channel's currency model.
     *
     * @return \Webkul\Core\Contracts\Currency
     */
    public function get_channel_base_currency()
    {
        return $this->get_current_channel()->base_currency;
    }
    /**
     * Returns base channel's currency code.
     *
     * @return string
     */
    public function get_channel_base_currency_code()
    {
        return $this->get_channel_base_currency()?->code;
    }
    /**
     * Set currency.
     *
     * @param  string  $currencyCode
     * @return void
     */
    public function set_current_currency($currency_code)
    {
        $this->current_currency = $this->currency_repository->find_one_by_field('code', $currency_code);
        if ($this->current_currency) {
            return;
        }
        $this->current_currency = $this->get_channel_base_currency();
    }
    /**
     * Returns current channel's currency model.
     *
     * Will fallback to base currency if not set.
     *
     * @return \Webkul\Core\Contracts\Currency
     */
    public function get_current_currency()
    {
        if ($this->current_currency) {
            return $this->current_currency;
        }
        return $this->current_currency = $this->get_channel_base_currency();
    }
    /**
     * Returns current channel's currency code.
     *
     * @return string
     */
    public function get_current_currency_code()
    {
        return $this->get_current_currency()?->code;
    }
    /**
     * Returns exchange rates.
     *
     * @return object
     */
    public function get_exchange_rate($target_currency_id)
    {
        if (array_key_exists($target_currency_id, $this->exchange_rates)) {
            return $this->exchange_rates[$target_currency_id];
        }
        return $this->exchange_rates[$target_currency_id] = $this->exchange_rate_repository->find_one_where(['target_currency' => $target_currency_id]);
    }
    /**
     * Converts price.
     *
     * @param  float  $amount
     * @param  string  $targetCurrencyCode
     * @return string
     */
    public function convert_price($amount, $target_currency_code = null)
    {
        $target_currency = !$target_currency_code ? $this->get_current_currency() : $this->currency_repository->find_one_by_field('code', $target_currency_code);
        if (!$target_currency) {
            return $amount;
        }
        $exchange_rate = $this->get_exchange_rate($target_currency->id);
        if (!$exchange_rate) {
            return $amount;
        }
        return (float) $amount * $exchange_rate->rate;
    }
    /**
     * Converts to base price.
     *
     * @param  float  $amount
     * @param  string  $targetCurrencyCode
     * @return string
     */
    public function convert_to_base_price($amount, $target_currency_code = null)
    {
        $target_currency = !$target_currency_code ? $this->get_current_currency() : $this->currency_repository->find_one_by_field('code', $target_currency_code);
        if (!$target_currency) {
            return $amount;
        }
        $exchange_rate = $this->exchange_rate_repository->find_one_where(['target_currency' => $target_currency->id]);
        if ($exchange_rate === null || !$exchange_rate->rate) {
            return $amount;
        }
        return (float) $amount / $exchange_rate->rate;
    }
    /**
     * Format and convert price with currency symbol.
     *
     * @param  float  $price
     * @return string
     */
    public function currency($amount = 0)
    {
        if (is_null($amount)) {
            $amount = 0;
        }
        return $this->format_price($this->convert_price($amount));
    }
    /**
     * Format price.
     */
    public function format_price(?float $price, ?string $currency_code = null): string
    {
        if (is_null($price)) {
            $price = 0;
        }
        $currency = $currency_code ? $this->get_all_currencies()->where('code', $currency_code)->first() : $this->get_current_currency();
        return $this->format_currency($price, $currency);
    }
    /**
     * Format price with base currency symbol.
     */
    public function format_base_price(?float $price): string
    {
        if (is_null($price)) {
            $price = 0;
        }
        $currency = $this->get_base_currency();
        return $this->format_currency($price, $currency);
    }
    /**
     * Checks if current date of the given channel (in the channel timezone) is within the range.
     *
     * @param  int|string|\Webkul\Core\Contracts\Channel  $channel
     * @param  string|null  $dateFrom
     * @param  string|null  $dateTo
     * @return bool
     */
    public function is_channel_date_in_interval($date_from = null, $date_to = null)
    {
        $channel = $this->get_current_channel();
        $channel_time_stamp = $this->channel_time_stamp($channel);
        $from_time_stamp = strtotime($date_from);
        $to_time_stamp = strtotime($date_to);
        if ($date_to) {
            $to_time_stamp += 86400;
        }
        if (!$this->is_empty_date($date_from) && $channel_time_stamp < $from_time_stamp) {
            $result = false;
        } elseif (!$this->is_empty_date($date_to) && $channel_time_stamp > $to_time_stamp) {
            $result = false;
        } else {
            $result = true;
        }
        return $result;
    }
    /**
     * Get channel timestamp, timestamp will be builded with channel timezone settings.
     *
     * @param  \Webkul\Core\Contracts\Channel  $channel
     * @return int
     */
    public function channel_time_stamp($channel)
    {
        $timezone = $channel->timezone;
        $current_timezone = @date_default_timezone_get();
        @date_default_timezone_set($timezone);
        $date = date('Y-m-d H:i:s');
        @date_default_timezone_set($current_timezone);
        return strtotime($date);
    }
    /**
     * Check whether sql date is empty.
     *
     * @param  string  $date
     * @return bool
     */
    public function is_empty_date($date)
    {
        return preg_replace('#[ 0:-]#', '', $date) === '';
    }
    /**
     * Format date using current channel.
     *
     * @param  \Illuminate\Support\Carbon|string|null  $date
     * @param  string  $format
     * @return string
     */
    public function format_date($date = null, $format = 'd-m-Y H:i:s')
    {
        $channel = $this->get_current_channel();
        if (is_null($date)) {
            $date = Carbon::now();
        }
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        $date->set_timezone($channel->timezone);
        return $date->translated_format($format);
    }
    /**
     * Retrieve information from payment configuration.
     */
    public function get_config_data(string $field, ?string $current_channel_code = null, ?string $current_locale_code = null): mixed
    {
        return system_config()->get_config_data($field, $current_channel_code, $current_locale_code);
    }
    /**
     * Retrieve all countries.
     *
     * @return \Illuminate\Support\Collection
     */
    public function countries()
    {
        return DB::table('countries')->get();
    }
    /**
     * Returns country name by code.
     *
     * @param  string  $code
     * @return string
     */
    public function country_name($code)
    {
        $country = $this->country_repository->find_one_by_field('code', $code);
        return $country ? $country->name : '';
    }
    /**
     * Retrieve all country states.
     *
     * @param  string  $countryCode
     * @return \Illuminate\Support\Collection
     */
    public function states($country_code)
    {
        return $this->country_state_repository->find_by_field('country_code', $country_code);
    }
    /**
     * Retrieve all grouped states by country code.
     *
     * @return \Illuminate\Support\Collection
     */
    public function grouped_states_by_countries()
    {
        $collection = [];
        foreach (DB::table('country_states')->get() as $state) {
            $collection[$state->country_code][] = $state;
        }
        return $collection;
    }
    /**
     * Retrieve all grouped states by country code.
     *
     * @return \Illuminate\Support\Collection
     */
    public function find_state_by_country_code($country_code = null, $state_code = null)
    {
        $collection = [];
        $collection = $this->country_state_repository->find_by_field(['country_code' => $country_code, 'code' => $state_code]);
        if (count($collection)) {
            return $collection->first();
        } else {
            return false;
        }
    }
    /**
     * Return guest customer group.
     *
     * @return \Webkul\Customer\Contract\CustomerGroup
     */
    public function get_guest_customer_group()
    {
        if ($this->guest_customer_group) {
            return $this->guest_customer_group;
        }
        return $this->guest_customer_group = $this->customer_group_repository->find_one_by_field('code', 'guest');
    }
    /**
     * Is country required.
     *
     * @return bool
     */
    public function is_country_required()
    {
        return (bool) $this->get_config_data('customer.address.requirements.country');
    }
    /**
     * Is state required.
     *
     * @return bool
     */
    public function is_state_required()
    {
        return (bool) $this->get_config_data('customer.address.requirements.state');
    }
    /**
     * Is postcode required.
     *
     * @return bool
     */
    public function is_post_code_required()
    {
        return (bool) $this->get_config_data('customer.address.requirements.postcode');
    }
    /**
     * Week range.
     *
     * @param  string  $date
     * @param  int  $day
     * @return string
     */
    public function x_week_range($date, $day)
    {
        $ts = strtotime($date);
        if (!$day) {
            $start = date('D', $ts) == 'Sun' ? $ts : strtotime('last sunday', $ts);
            return date('Y-m-d', $start);
        } else {
            $end = date('D', $ts) == 'Sat' ? $ts : strtotime('next saturday', $ts);
            return date('Y-m-d', $end);
        }
    }
    /**
     * Get config field.
     *
     * @param  string  $fieldName
     * @return array
     */
    public function get_config_field($field_name)
    {
        return system_config()->get_config_field($field_name);
    }
    /**
     * Convert empty strings to null.
     *
     * @param  array  $array1
     * @return array
     */
    public function convert_empty_strings_to_null($array)
    {
        foreach ($array as $key => $value) {
            if ($value == '' || $value == 'null') {
                $array[$key] = null;
            }
        }
        return $array;
    }
    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return object
     */
    public function get_singleton_instance($class_name)
    {
        if (array_key_exists($class_name, $this->singleton_instances)) {
            return $this->singleton_instances[$class_name];
        }
        return $this->singleton_instances[$class_name] = app($class_name);
    }
    /**
     * Returns a string as selector part for identifying elements in views.
     */
    public static function tax_rate_as_identifier(float $tax_rate): string
    {
        return str_replace('.', '_', (string) $tax_rate);
    }
    /**
     * Create singleton object through single facade.
     *
     * @param  string  $className
     * @return object
     */
    public function get_tax_category_by_id($id)
    {
        if (empty($id)) {
            return;
        }
        if (array_key_exists($id, $this->tax_categories_by_id)) {
            return $this->tax_categories_by_id[$id];
        }
        return $this->tax_categories_by_id[$id] = $this->tax_category_repository->find($id);
    }
    /**
     * Get sender email details.
     *
     * @return array
     */
    public function get_sender_email_details()
    {
        $sender_name = $this->get_config_data('emails.configure.email_settings.sender_name') ?: config('mail.from.name');
        $sender_email = $this->get_config_data('emails.configure.email_settings.shop_email_from') ?: config('mail.from.address');
        return ['name' => $sender_name, 'email' => $sender_email];
    }
    /**
     * Get Admin email details.
     *
     * @return array
     */
    public function get_admin_email_details()
    {
        $admin_name = $this->get_config_data('emails.configure.email_settings.admin_name') ?: (config('mail.admin.name') ?: config('mail.from.name'));
        $admin_email = $this->get_config_data('emails.configure.email_settings.admin_email') ?: config('mail.admin.address');
        return ['name' => $admin_name, 'email' => $admin_email];
    }
    /**
     * Get Contact email details.
     *
     * @return array
     */
    public function get_contact_email_details()
    {
        $contact_name = $this->get_config_data('emails.configure.email_settings.contact_name') ?: (config('mail.contact.name') ?: config('mail.from.name'));
        $contact_email = $this->get_config_data('emails.configure.email_settings.contact_email') ?: config('mail.contact.address');
        return ['name' => $contact_name, 'email' => $contact_email];
    }
    /**
     * Get max upload size from the php.ini file.
     *
     * @return string
     */
    public function get_max_upload_size()
    {
        return ini_get('upload_max_filesize');
    }
    /**
     * Get Speculation Rules.
     *
     * @return array
     */
    public function get_speculation_rules()
    {
        $config_path = 'general.content.speculation_rules.';
        $rules = [];
        /**
         * Prerender Rules
         */
        if ($this->get_config_data($config_path . 'prerender_enabled')) {
            $prerender_eagerness = $this->get_config_data($config_path . 'prerender_eagerness') ?? 'moderate';
            $prerender_ignore_urls = array_filter(explode('|', $this->get_config_data($config_path . 'prerender_ignore_urls')), fn($url) => trim($url) !== '');
            $prerender_ignore_params = array_filter(explode('|', $this->get_config_data($config_path . 'prerender_ignore_url_params')), fn($param) => trim($param) !== '');
            $conditions = [['href_matches' => '/*']];
            foreach ($prerender_ignore_urls as $url) {
                $conditions[] = ['not' => ['href_matches' => trim($url)]];
            }
            foreach ($prerender_ignore_params as $param) {
                $param = trim($param);
                $conditions[] = ['not' => ['selector_matches' => "[href*='?{$param}=']"]];
            }
            $rules['prerender'][] = ['source' => 'document', 'where' => ['and' => $conditions], 'eagerness' => $prerender_eagerness];
        }
        /**
         * Prefetch Rules
         */
        if ($this->get_config_data($config_path . 'prefetch_enabled')) {
            $prefetch_eagerness = $this->get_config_data($config_path . 'prefetch_eagerness') ?? 'moderate';
            $prefetch_ignore_urls = array_filter(explode('|', $this->get_config_data($config_path . 'prefetch_ignore_urls')), fn($url) => trim($url) !== '');
            $prefetch_ignore_params = array_filter(explode('|', $this->get_config_data($config_path . 'prefetch_ignore_url_params')), fn($param) => trim($param) !== '');
            $conditions = [['href_matches' => '/*']];
            foreach ($prefetch_ignore_urls as $url) {
                $conditions[] = ['not' => ['href_matches' => trim($url)]];
            }
            foreach ($prefetch_ignore_params as $param) {
                $param = trim($param);
                $conditions[] = ['not' => ['selector_matches' => "[href*='?{$param}=']"]];
            }
            $rules['prefetch'][] = ['source' => 'document', 'where' => ['and' => $conditions], 'requires' => ['anonymous-client-ip-when-cross-origin'], 'referrer_policy' => 'no-referrer', 'eagerness' => $prefetch_eagerness];
        }
        return $rules;
    }
}