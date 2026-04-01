<?php

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExcursionController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\RegisterController;
use App\Http\Controllers\Admin\ReorderController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Роуты админ-панели
|--------------------------------------------------------------------------
|
| Все роуты имеют префикс /admin и именной префикс admin.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // Гостевые роуты (только для неавторизованных)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('registration.enabled')->group(function () {
            Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
            Route::post('/register', [RegisterController::class, 'register']);
        });
    });

    // Роуты верификации (ожидание ввода кода)
    Route::middleware('verification.pending')->group(function () {
        Route::get('/verify', [AuthController::class, 'showVerifyForm'])->name('verify');
        Route::post('/verify', [AuthController::class, 'verify']);
        Route::post('/verify/resend', [AuthController::class, 'resendCode'])->name('verify.resend');
    });

    // Авторизованные роуты
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Настройки
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Новости
        Route::resource('news', NewsController::class);

        // Экскурсии
        Route::resource('excursions', ExcursionController::class);

        // Статьи
        Route::post('/articles/import', [ArticleController::class, 'import'])->name('articles.import');
        Route::resource('articles', ArticleController::class);

        // Каталог (экспозиция / архив)
        Route::prefix('catalog/{type}')->name('catalog.')->where(['type' => 'exposition|archive'])->group(function () {
            Route::get('/', [CatalogController::class, 'index'])->name('index');
            Route::get('/create', [CatalogController::class, 'create'])->name('create');
            Route::post('/', [CatalogController::class, 'store'])->name('store');
            Route::get('/{catalogItem}/edit', [CatalogController::class, 'edit'])->name('edit');
            Route::put('/{catalogItem}', [CatalogController::class, 'update'])->name('update');
            Route::delete('/{catalogItem}', [CatalogController::class, 'destroy'])->name('destroy');
        });

        // Пересортировка элементов (drag&drop)
        Route::post('/reorder/{entity}', [ReorderController::class, 'update'])->name('reorder');

        // Загрузка изображений (WYSIWYG)
        Route::post('/upload/image', [UploadController::class, 'image'])->name('upload.image')->middleware('throttle:30,1');
    });
});
