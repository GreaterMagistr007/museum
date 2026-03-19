<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        private readonly VerificationCodeService $verificationCodeService,
    ) {}

    /**
     * Форма входа.
     */
    public function showLoginForm(): View
    {
        return view('admin.login');
    }

    /**
     * Обработка входа: проверка credentials, генерация кода, отправка на email.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Rate limiting: 5 попыток в минуту по IP + email
        $throttleKey = Str::transliterate(
            Str::lower($credentials['email']) . '|' . $request->ip()
        );

        if (app('Illuminate\Cache\RateLimiter')->tooManyAttempts($throttleKey, 5)) {
            $seconds = app('Illuminate\Cache\RateLimiter')->availableIn($throttleKey);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => "Слишком много попыток входа. Попробуйте через {$seconds} сек.",
                ]);
        }

        if (! Auth::attempt($credentials)) {
            app('Illuminate\Cache\RateLimiter')->hit($throttleKey, 60);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Неверный email или пароль.',
                ]);
        }

        // Credentials верные — забираем пользователя и сразу разлогиниваем
        /** @var User $user */
        $user = Auth::user();
        Auth::logout();

        // Сбрасываем rate limiter при успешной проверке credentials
        app('Illuminate\Cache\RateLimiter')->clear($throttleKey);

        // Сохраняем ID в сессию и генерируем код
        $request->session()->put('pending_user_id', $user->id);

        $code = $this->verificationCodeService->generate($user, 'login');
        Mail::to($user->email)->send(new VerificationCodeMail($code, 'login'));

        return redirect()->route('admin.verify');
    }

    /**
     * Форма ввода кода верификации.
     */
    public function showVerifyForm(Request $request): View
    {
        $user = User::findOrFail($request->session()->get('pending_user_id'));

        return view('admin.verify', [
            'masked_email' => $this->maskEmail($user->email),
        ]);
    }

    /**
     * Проверка кода верификации.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = $request->session()->get('pending_user_id');
        $user = User::findOrFail($userId);

        // Определяем тип — если есть запись login, используем login, иначе registration
        $type = VerificationCode::forUser($userId, 'login')->exists()
            ? 'login'
            : 'registration';

        $verified = $this->verificationCodeService->verify($user, $request->input('code'), $type);

        if (! $verified) {
            // Проверяем, не превышены ли попытки (запись уже удалена сервисом)
            $remaining = VerificationCode::forUser($userId, $type)->first();

            if (! $remaining || $remaining->hasExceededAttempts()) {
                $request->session()->forget('pending_user_id');

                return redirect()->route('admin.login')
                    ->with('error', 'Превышено количество попыток ввода кода. Войдите заново.');
            }

            return back()->withErrors([
                'code' => 'Неверный код подтверждения.',
            ]);
        }

        // Код верный — авторизуем пользователя
        Auth::login($user);
        $request->session()->forget('pending_user_id');
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    /**
     * Повторная отправка кода верификации.
     */
    public function resendCode(Request $request): RedirectResponse
    {
        // Rate limiting: 1 раз в 60 сек
        $throttleKey = 'resend-code|' . $request->session()->get('pending_user_id');

        if (app('Illuminate\Cache\RateLimiter')->tooManyAttempts($throttleKey, 1)) {
            $seconds = app('Illuminate\Cache\RateLimiter')->availableIn($throttleKey);

            return back()->with('error', "Повторная отправка возможна через {$seconds} сек.");
        }

        $user = User::findOrFail($request->session()->get('pending_user_id'));

        // Определяем тип кода по наличию записи
        $type = VerificationCode::forUser($user->id, 'login')->exists()
            ? 'login'
            : 'registration';

        $code = $this->verificationCodeService->generate($user, $type);
        Mail::to($user->email)->send(new VerificationCodeMail($code, $type));

        app('Illuminate\Cache\RateLimiter')->hit($throttleKey, 60);

        return back()->with('success', 'Код отправлен повторно.');
    }

    /**
     * Выход из системы.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Маскирует email: показывает первые 2 символа и домен.
     * Пример: john@mail.ru -> jo***@mail.ru
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);

        $visible = Str::substr($local, 0, 2);

        return $visible . '***@' . $domain;
    }
}
