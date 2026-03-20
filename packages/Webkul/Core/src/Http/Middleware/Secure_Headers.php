<?php

declare (strict_types=1);
namespace Webkul\Core\Http\Middleware;

use Closure;
class Secure_Headers
{
    /**
     * Unwanted header list.
     *
     * @var array
     */
    private $unwanted_header_list = [];
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->remove_unwanted_headers();
        $response = $next($request);
        $this->set_headers($response);
        return $response;
    }
    /**
     * Set headers.
     *
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    private function set_headers($response)
    {
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Built-With', 'Bagisto');
    }
    /**
     * Remove unwanted headers.
     *
     * @return void
     */
    private function remove_unwanted_headers()
    {
        if (headers_sent()) {
            return;
        }
        foreach ($this->unwanted_header_list as $header) {
            header_remove($header);
        }
    }
}