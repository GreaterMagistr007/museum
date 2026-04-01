<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\Excursion;
use App\Models\News;
use Illuminate\View\View;

/**
 * Контроллер публичных страниц с динамическим контентом.
 */
class PageController extends Controller
{
    /**
     * Главная страница.
     */
    public function home(): View
    {
        $excursions = Excursion::published()->get();

        // Корневые статьи с дочерними для блока "Воинские формирования"
        $formations = \Illuminate\Support\Facades\Cache::remember('home_formations', 3600, function () {
            return Article::published()
                ->roots()
                ->with(['children' => fn ($q) => $q->published()->ordered()])
                ->ordered()
                ->get();
        });

        return view('pages.home', compact('excursions', 'formations'));
    }

    /**
     * Страница «О музее».
     */
    public function about(): View
    {
        return view('pages.about');
    }

    /**
     * Страница новостей.
     */
    public function news(): View
    {
        $news = News::published()->paginate(12);

        return view('pages.news', compact('news'));
    }

    /**
     * Список экскурсий.
     */
    public function excursions(): View
    {
        $excursions = Excursion::published()->get();

        return view('pages.excursions', compact('excursions'));
    }

    /**
     * Детальная страница экскурсии.
     */
    public function excursionShow(Excursion $excursion): View
    {
        if (! $excursion->is_published) {
            abort(404);
        }

        return view('pages.excursion-show', compact('excursion'));
    }

    /**
     * Страница экспозиции.
     */
    public function exposition(): View
    {
        $items = CatalogItem::ofType('exposition')->published()->get();

        return view('pages.exposition', compact('items'));
    }

    /**
     * Страница архива.
     */
    public function archive(): View
    {
        $items = CatalogItem::ofType('archive')->published()->get();

        return view('pages.archive', compact('items'));
    }

    /**
     * Страница контактов.
     */
    public function contacts(): View
    {
        return view('pages.contacts');
    }

    /**
     * Детальная страница статьи (военный городок и подразделы).
     */
    public function articleShow(Article $article): View
    {
        if (! $article->is_published) {
            abort(404);
        }

        // Загрузка родителя и опубликованных дочерних статей
        $article->load(['parent', 'children' => fn ($q) => $q->published()->ordered()]);

        return view('pages.article-show', compact('article'));
    }
}
