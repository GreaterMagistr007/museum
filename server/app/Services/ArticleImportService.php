<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;

/**
 * Сервис импорта статей из DOCX-файлов.
 * Конвертирует DOCX → HTML, извлекает body, очищает Word-мусор.
 */
class ArticleImportService
{
    /**
     * Конвертировать DOC/DOCX в очищенный HTML.
     *
     * @param UploadedFile $file загруженный DOC/DOCX-файл
     * @return string HTML-контент для поля content
     * @throws \Exception если файл не является валидным DOC/DOCX
     */
    public function importFromDocx(UploadedFile $file): string
    {
        $tmpPath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        // DOC → DOCX через LibreOffice
        $convertedPath = null;
        if ($extension === 'doc') {
            $tmpPath = $this->convertDocToDocx($tmpPath);
            $convertedPath = $tmpPath;
        }

        try {
            // Загрузка документа через PhpWord (всегда Word2007/DOCX)
            $phpWord = IOFactory::load($tmpPath, 'Word2007');

            // Сохранение в HTML во временный буфер
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            $tmpHtmlPath = tempnam(sys_get_temp_dir(), 'phpword_') . '.html';

            try {
                $htmlWriter->save($tmpHtmlPath);
                $rawHtml = file_get_contents($tmpHtmlPath);
            } finally {
                if (file_exists($tmpHtmlPath)) {
                    unlink($tmpHtmlPath);
                }
            }
        } finally {
            if ($convertedPath && file_exists($convertedPath)) {
                unlink($convertedPath);
            }
        }

        return $this->extractAndClean($rawHtml);
    }

    /**
     * Конвертировать DOC в DOCX через LibreOffice.
     *
     * @return string путь к временному DOCX-файлу
     * @throws \RuntimeException если конвертация не удалась
     */
    private function convertDocToDocx(string $docPath): string
    {
        $tmpDir = sys_get_temp_dir();
        // LibreOffice сохраняет файл с тем же именем, но расширением .docx
        $baseName = pathinfo($docPath, PATHINFO_FILENAME);

        $command = sprintf(
            'libreoffice --headless --convert-to docx --outdir %s %s 2>&1',
            escapeshellarg($tmpDir),
            escapeshellarg($docPath)
        );

        exec($command, $output, $exitCode);

        $docxPath = $tmpDir . '/' . $baseName . '.docx';

        if ($exitCode !== 0 || ! file_exists($docxPath)) {
            throw new \RuntimeException('Не удалось сконвертировать DOC в DOCX: ' . implode("\n", $output));
        }

        return $docxPath;
    }

    /**
     * Извлечь содержимое body и очистить Word-мусор.
     */
    private function extractAndClean(string $html): string
    {
        // Установка кодировки для корректной работы с UTF-8
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Извлечение body
        $body = $doc->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return '';
        }

        // Получить innerHTML body
        $innerHTML = '';
        foreach ($body->childNodes as $child) {
            $innerHTML .= $doc->saveHTML($child);
        }

        // Очистка Word-специфичного мусора
        $innerHTML = $this->removeWordJunk($innerHTML);

        return trim($innerHTML);
    }

    /**
     * Убрать Word-специфичные артефакты из HTML.
     */
    private function removeWordJunk(string $html): string
    {
        // Удалить inline-стили Word (style="mso-...")
        $html = preg_replace('/\s*style="[^"]*mso-[^"]*"/i', '', $html);

        // Удалить пустые style-атрибуты
        $html = preg_replace('/\s*style="\s*"/i', '', $html);

        // Удалить Word-классы (MsoNormal, MsoHeading и т.п.)
        $html = preg_replace('/\s*class="Mso[^"]*"/i', '', $html);

        // Удалить атрибуты lang
        $html = preg_replace('/\s*lang="[^"]*"/i', '', $html);

        // Удалить XML-namespace-атрибуты
        $html = preg_replace('/\s+xmlns:[a-z]+="[^"]*"/i', '', $html);

        // Удалить условные комментарии Word (<!--[if gte mso...]-->)
        $html = preg_replace('/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/si', '', $html);

        // Удалить пустые параграфы
        $html = preg_replace('/<p[^>]*>\s*(&nbsp;)?\s*<\/p>/i', '', $html);

        // Удалить теги span без атрибутов (обёртки Word)
        $html = preg_replace('/<span>\s*(.*?)\s*<\/span>/is', '$1', $html);

        // Удалить o:p (Office paragraph markers)
        $html = preg_replace('/<o:p[^>]*>.*?<\/o:p>/si', '', $html);

        // Нормализовать множественные переносы строк
        $html = preg_replace('/(\n\s*){3,}/', "\n\n", $html);

        return $html;
    }
}
