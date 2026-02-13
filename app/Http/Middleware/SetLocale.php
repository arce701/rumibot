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
        $user = $request->user();

        if ($user && in_array($user->locale, config('app.available_locales', ['es', 'en', 'pt_BR']))) {
            App::setLocale($user->locale);
        }

        return $next($request);
    }
}
