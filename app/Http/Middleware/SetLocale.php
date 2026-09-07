<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'ka';
        $lang = null;
        if ($request->has('lang')) {
            $lang = strtolower(substr($request->input('lang'), 0, 2));
        } elseif ($request->hasHeader('Accept-Language')) {
            $lang = strtolower(substr($request->header('Accept-Language'), 0, 2));
        }
        if ($lang === 'ge' || $lang === 'ka') {
            $locale = 'ka';
        } elseif (in_array($lang, ['en', 'ru'])) {
            $locale = $lang;
        }
        \Illuminate\Support\Facades\App::setLocale($locale);
        return $next($request);
    }
}
