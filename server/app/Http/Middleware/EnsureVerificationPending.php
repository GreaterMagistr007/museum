<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Проверяет, что в сессии есть pending_user_id
 * (пользователь прошёл логин/регистрацию и ожидает ввода кода).
 */
class EnsureVerificationPending
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('pending_user_id')) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
