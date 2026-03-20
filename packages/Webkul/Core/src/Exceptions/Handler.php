<?php

declare (strict_types=1);
namespace Webkul\Core\Exceptions;

use Illuminate\Auth\Authentication_Exception;
use Illuminate\Foundation\Exceptions\Handler as BaseHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\Validation_Exception;
use Symfony\Component\Http_Kernel\Exception\Http_Exception;
use Throwable;
class Handler extends Base_Handler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        if (config('app.debug')) {
            return;
        }
        $this->handle_authentication_exception();
        $this->handle_http_exception();
        $this->handle_validation_exception();
        $this->handle_server_exception();
    }
    /**
     * Handle the authentication exception.
     */
    protected function handle_authentication_exception(): void
    {
        $this->renderable(function (Authentication_Exception $exception, Request $request) {
            $namespace = $request->is(config('app.admin_url') . '/*') ? 'admin' : 'shop';
            if ($request->wants_json()) {
                return response()->json(['error' => trans("{$namespace}::app.errors.401.description")], 401);
            }
            if ($namespace !== 'admin') {
                return redirect()->guest(route('shop.customer.session.index'));
            }
            return redirect()->guest(route('admin.session.create'));
        });
    }
    /**
     * Handle the http exceptions.
     */
    protected function handle_http_exception(): void
    {
        $this->renderable(function (Http_Exception $exception, Request $request) {
            $namespace = $request->is(config('app.admin_url') . '/*') ? 'admin' : 'shop';
            $error_code = in_array($exception->get_status_code(), [401, 403, 404, 503]) ? $exception->get_status_code() : 500;
            if ($request->wants_json()) {
                return response()->json(['error' => trans("{$namespace}::app.errors.{$error_code}.title"), 'description' => trans("{$namespace}::app.errors.{$error_code}.description")], $error_code);
            }
            $view_path = "{$namespace}::errors.{$error_code}";
            if (!view()->exists($view_path)) {
                $view_path = "{$namespace}::errors.index";
            }
            return response()->view($view_path, compact('errorCode'), $error_code);
        });
    }
    /**
     * Handle validation exceptions.
     */
    protected function handle_validation_exception(): void
    {
        $this->renderable(function (Validation_Exception $exception, Request $request) {
            return parent::convert_validation_exception_to_response($exception, $request);
        });
    }
    /**
     * Handle the server exceptions.
     */
    protected function handle_server_exception(): void
    {
        $this->renderable(function (Throwable $throwable, Request $request) {
            $namespace = $request->is(config('app.admin_url') . '/*') ? 'admin' : 'shop';
            $error_code = 500;
            if ($request->wants_json()) {
                return response()->json(['error' => trans("{$namespace}::app.errors.{$error_code}.title"), 'description' => trans("{$namespace}::app.shop.errors.{$error_code}.description")], $error_code);
            }
            $view_path = "{$namespace}::errors.{$error_code}";
            if (!view()->exists($view_path)) {
                $view_path = "{$namespace}::errors.index";
            }
            return response()->view($view_path, compact('errorCode'), $error_code);
        });
    }
}