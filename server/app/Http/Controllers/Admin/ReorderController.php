<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\Excursion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Контроллер пересортировки элементов через drag&drop.
 */
class ReorderController extends Controller
{
    /** Карта сущностей: ключ => класс модели или [класс, тип] */
    private const ENTITY_MAP = [
        'excursions' => Excursion::class,
        'articles' => Article::class,
        'catalog-exposition' => [CatalogItem::class, 'exposition'],
        'catalog-archive' => [CatalogItem::class, 'archive'],
    ];

    /**
     * Обновить порядок сортировки.
     */
    public function update(Request $request, string $entity): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        if (!array_key_exists($entity, self::ENTITY_MAP)) {
            abort(404);
        }

        $config = self::ENTITY_MAP[$entity];

        DB::transaction(function () use ($request, $config) {
            foreach ($request->ids as $index => $id) {
                if (is_array($config)) {
                    // CatalogItem с фильтрацией по type
                    $config[0]::where('id', $id)->where('type', $config[1])->update(['sort_order' => $index]);
                } else {
                    $config::where('id', $id)->update(['sort_order' => $index]);
                }
            }
        });

        if ($entity === 'articles') {
            Cache::forget('home_formations');
            Cache::forget('nav_articles');
        }

        return response()->json(['success' => true]);
    }
}
