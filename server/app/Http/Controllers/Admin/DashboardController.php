<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\Excursion;
use App\Models\News;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Главная страница панели администратора.
     */
    public function index(): View
    {
        $stats = [
            'news' => News::count(),
            'excursions' => Excursion::count(),
            'articles' => Article::count(),
            'exposition' => CatalogItem::where('type', 'exposition')->count(),
            'archive' => CatalogItem::where('type', 'archive')->count(),
        ];

        $latestNews = News::latest('published_at')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'latestNews'));
    }
}
