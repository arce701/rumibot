<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = config('app.available_locales', ['es', 'en', 'pt_BR']);
        $user = $request->user();

        if ($user && in_array($user->locale, $availableLocales)) {
            App::setLocale($user->locale);
        } elseif ($request->session()->has('locale') && in_array($request->session()->get('locale'), $availableLocales)) {
            App::setLocale($request->session()->get('locale'));
        }

        return $next($request);
    }
}
