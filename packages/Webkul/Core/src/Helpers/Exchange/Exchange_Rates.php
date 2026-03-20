<?php

declare (strict_types=1);
namespace Webkul\Core\Helpers\Exchange;

use Webkul\Core\Repositories\Currency_Repository;
use Webkul\Core\Repositories\Exchange_Rate_Repository;
class Exchange_Rates extends Exchange_Rate
{
    /**
     * API key.
     *
     * @var string
     */
    protected $api_key;
    /**
     * API endpoint.
     *
     * @var string
     */
    protected $api_end_point;
    /**
     * Create a new helper instance.
     *
     * @return void
     */
    public function __construct(protected Currency_Repository $currency_repository, protected Exchange_Rate_Repository $exchange_rate_repository)
    {
        $this->api_end_point = config('services.exchange_api.exchange_rates.url');
        $this->api_key = config('services.exchange_api.exchange_rates.key');
    }
    /**
     * Fetch rates and updates in `currency_exchange_rates` table.
     *
     * @return \Exception|void
     */
    public function update_rates()
    {
        $client = new \Guzzle_Http\Client();
        foreach ($this->currency_repository->all() as $currency) {
            if ($currency->code == config('app.currency')) {
                continue;
            }
            $result = $client->request('GET', $this->api_end_point, ['headers' => ['Content-Type' => 'text/plain', 'apikey' => $this->api_key], 'query' => ['to' => $currency->code, 'from' => config('app.currency'), 'amount' => 1]]);
            $result = json_decode($result->get_body()->get_contents(), true);
            if (isset($result['success']) && !$result['success']) {
                throw new \Exception($result['error']['info'] ?? $result['error']['type'], 1);
            }
            if ($exchange_rate = $currency->exchange_rate) {
                $this->exchange_rate_repository->update(['rate' => $result['result']], $exchange_rate->id);
            } else {
                $this->exchange_rate_repository->create(['rate' => $result['result'], 'target_currency' => $currency->id]);
            }
        }
    }
}