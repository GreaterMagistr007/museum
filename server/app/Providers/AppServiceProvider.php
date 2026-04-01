<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Передача настроек сайта во все публичные шаблоны и компоненты
        View::composer([
            'layouts.app',
            'pages.*',
            'components.header',
            'components.footer',
            'components.modals',
        ], function ($view) {
            $view->with('siteSettings', Setting::cached());
        });

        // Передача навигационных статей (корень + дети) в header
        View::composer('components.header', function ($view) {
            $navArticles = Cache::remember('nav_articles', 3600, function () {
                return Article::published()
                    ->roots()
                    ->with(['children' => fn ($q) => $q->published()->ordered()])
                    ->ordered()
                    ->get();
            });

            $view->with('navArticles', $navArticles);
        });
    }
}
