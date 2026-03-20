<?php

declare (strict_types=1);
namespace Webkul\Customer;

use Webkul\Customer\Contracts\Captcha as CaptchaContract;
class Captcha implements Captcha_Contract
{
    /**
     * Site key.
     *
     * @var string
     */
    protected $site_key;
    /**
     * Secret key.
     *
     * @var string
     */
    protected $secret_key;
    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->site_key = $this->get_site_key();
        $this->secret_key = $this->get_secret_key();
    }
    /**
     * Check whether captcha is active or not.
     */
    public function is_active(): bool
    {
        return (bool) core()->get_config_data('customer.captcha.credentials.status');
    }
    /**
     * Get site key from the core config.
     */
    public function get_site_key(): ?string
    {
        return core()->get_config_data('customer.captcha.credentials.site_key');
    }
    /**
     * Get secret key from the core config.
     */
    public function get_secret_key(): ?string
    {
        return core()->get_config_data('customer.captcha.credentials.secret_key');
    }
    /**
     * Get client endpoint.
     */
    public function get_client_endpoint(): string
    {
        return static::CLIENT_ENDPOINT;
    }
    /**
     * Get site verify endpoint.
     */
    public function get_site_verify_endpoint(): string
    {
        return static::SITE_VERIFY_ENDPOINT;
    }
    /**
     * Render JS.
     */
    public function render_js(): string
    {
        return $this->is_active() ? $this->get_captcha_js_view() : '';
    }
    /**
     * Render Captcha.
     */
    public function render(): string
    {
        return $this->is_active() ? $this->get_captcha_view() : '';
    }
    /**
     * Validate response.
     */
    public function validate_response($response): bool
    {
        $client = new \Guzzle_Http\Client();
        $response = $client->post($this->get_site_verify_endpoint(), ['query' => ['secret' => $this->secret_key, 'response' => $response]]);
        return json_decode($response->get_body())->success;
    }
    /**
     * Get or merge existing validations with your captcha validations.
     */
    public function get_validations($rules = []): array
    {
        return $this->is_active() ? array_merge($rules, ['g-recaptcha-response' => 'required|captcha']) : $rules;
    }
    /**
     * Get or merge existing validation messages with your captcha validation messages.
     */
    public function get_validation_messages($messages = []): array
    {
        return $this->is_active() ? array_merge($messages, ['g-recaptcha-response.required' => trans('customer::app.validations.captcha.required'), 'g-recaptcha-response.captcha' => trans('customer::app.validations.captcha.captcha')]) : $messages;
    }
    /**
     * Get attributes.
     */
    protected function get_attributes(): array
    {
        return ['class' => 'g-recaptcha', 'data-sitekey' => $this->site_key];
    }
    /**
     * Build attributes.
     */
    protected function build_html_attributes(array $attributes): string
    {
        $html_attributes = [];
        foreach ($attributes as $key => $value) {
            $html_attributes[] = "{$key}=\"{$value}\"";
        }
        return count($html_attributes) ? implode(' ', $html_attributes) : '';
    }
    /**
     * Get captcha view.
     *
     * @return string
     */
    protected function get_captcha_view()
    {
        $html_attributes = $this->build_html_attributes($this->get_attributes());
        return view('customer::captcha.view', ['htmlAttributes' => $html_attributes])->render();
    }
    /**
     * Get captcha script view.
     *
     * @return string
     */
    protected function get_captcha_js_view()
    {
        return view('customer::captcha.scripts', ['clientEndPoint' => $this->get_client_endpoint()])->render();
    }
}