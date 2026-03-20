<?php

declare (strict_types=1);
namespace Webkul\Core\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\Prevent_Requests_During_Maintenance as BasePreventRequestsDuringMaintenance;
use Illuminate\Routing\Route;
use Symfony\Component\Http_Kernel\Exception\Http_Exception;
class Prevent_Requests_During_Maintenance extends Base_Prevent_Requests_During_Maintenance
{
    /**
     * Exclude route names.
     *
     * @var array
     */
    protected $excluded_names = [];
    /**
     * Exclude Channel Ip's.
     *
     * @var array
     */
    protected $excluded_i_ps = [];
    /**
     * Constructor.
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->except[] = config('app.admin_url') . '*';
    }
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        if ($this->app->maintenance_mode()->active()) {
            try {
                $data = $this->app->maintenance_mode()->data();
            } catch (\ErrorException $exception) {
                if (!$this->app->maintenance_mode()->active()) {
                    return $next($request);
                }
                throw $exception;
            }
            if (isset($data['secret']) && $request->path() === $data['secret']) {
                return $this->bypass_response($data['secret']);
            }
            if ($this->has_valid_bypass_cookie($request, $data)) {
                return $next($request);
            }
            $this->set_allowed_ips();
            if (in_array($request->ip(), $this->excluded_i_ps) || $this->in_except_array($request) || !(bool) core()->get_current_channel()->is_maintenance_on) {
                return $next($request);
            }
            if ($request->route() instanceof Route && in_array($request->route()->get_name(), $this->excluded_names)) {
                return $next($request);
            }
            if (isset($data['redirect'])) {
                $path = $data['redirect'] === '/' ? $data['redirect'] : trim($data['redirect'], '/');
                if ($request->path() !== $path) {
                    return redirect($path);
                }
            }
            if (isset($data['template'])) {
                return response($data['template'], $data['status'] ?? 503, $this->get_headers($data));
            }
            throw new Http_Exception($data['status'] ?? 503, 'Service Unavailable', null, $this->get_headers($data));
        }
        return $next($request);
    }
    /**
     * Set allowed IPs.
     */
    protected function set_allowed_ips(): void
    {
        if ($channel = core()->get_current_channel()) {
            $this->excluded_i_ps = array_map('trim', explode(',', $channel->allowed_ips ?? ''));
        }
    }
}