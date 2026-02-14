<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        // Content Security Policy (CSP) - Disabled temporarily to fix display issues
        // TODO: Re-enable with proper configuration after testing
        // $csp = "default-src 'self'; ";
        // $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; ";
        // $csp .= "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdnjs.cloudflare.com; ";
        // $csp .= "font-src 'self' https://fonts.bunny.net https://cdnjs.cloudflare.com; ";
        // $csp .= "img-src 'self' data: https: blob:; ";
        // $csp .= "connect-src 'self'; ";
        // $csp .= "frame-ancestors 'none'; ";
        // $csp .= "base-uri 'self'; ";
        // $csp .= "form-action 'self';";
        // $response->headers->set('Content-Security-Policy', $csp);



        // Strict Transport Security (HSTS) - only in production
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
