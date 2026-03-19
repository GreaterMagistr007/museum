<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проверяет, что регистрация администраторов включена в конфигурации.
 */
class CheckRegistrationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.admin_register_enable')) {
            abort(404);
        }

        return $next($request);
    }
}
