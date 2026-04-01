<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Excursion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Удаление сиротских изображений из uploads/content/.
 */
class CleanOrphanedImages extends Command
{
    protected $signature = 'cms:clean-orphaned-images {--dry-run}';

    protected $description = 'Удалить сиротские изображения из uploads/content/';

    public function handle(): int
    {
        $files = Storage::files('public/uploads/content');

        if (empty($files)) {
            $this->info('Нет файлов в uploads/content/.');

            return 0;
        }

        // Собрать все URL упоминаемые в HTML-контенте
        $usedUrls = collect();

        Article::chunk(50, function ($articles) use (&$usedUrls) {
            foreach ($articles as $article) {
                preg_match_all('/\/storage\/uploads\/content\/[^\s"\']+/', $article->content ?? '', $matches);
                $usedUrls = $usedUrls->merge($matches[0]);
            }
        });

        Excursion::chunk(50, function ($excursions) use (&$usedUrls) {
            foreach ($excursions as $excursion) {
                $html = ($excursion->description ?? '') . ($excursion->what_you_see ?? '') . ($excursion->interesting_facts ?? '');
                preg_match_all('/\/storage\/uploads\/content\/[^\s"\']+/', $html, $matches);
                $usedUrls = $usedUrls->merge($matches[0]);
            }
        });

        $usedUrls = $usedUrls->unique()->toArray();
        $deleted = 0;
        $dryRun = $this->option('dry-run');

        foreach ($files as $file) {
            $url = Storage::url($file);

            if (! in_array($url, $usedUrls)) {
                if ($dryRun) {
                    $this->line("Будет удалён: {$file}");
                } else {
                    Storage::delete($file);
                    $this->line("Удалён: {$file}");
                }
                $deleted++;
            }
        }

        $this->info($dryRun ? "Найдено сиротских: {$deleted}" : "Удалено: {$deleted}");

        return 0;
    }
}
