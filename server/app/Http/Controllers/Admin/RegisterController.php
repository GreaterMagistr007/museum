<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\VerificationCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(
        private readonly VerificationCodeService $verificationCodeService,
    ) {}

    /**
     * Форма регистрации.
     */
    public function showRegisterForm(): View
    {
        return view('admin.register');
    }

    /**
     * Обработка регистрации: создание пользователя, генерация кода, отправка email.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        // В режиме разработки (регистрация включена) — пропускаем 2FA
        if (config('app.admin_register_enable')) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        // Сохраняем ID в сессию, генерируем код верификации
        $request->session()->put('pending_user_id', $user->id);

        $code = $this->verificationCodeService->generate($user, 'registration');
        Mail::to($user->email)->send(new VerificationCodeMail($code, 'registration'));

        return redirect()->route('admin.verify');
    }
}
