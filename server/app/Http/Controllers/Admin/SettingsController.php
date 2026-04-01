<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Контроллер настроек админ-панели.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService,
    ) {}

    /**
     * Форма редактирования настроек.
     */
    public function edit(): View
    {
        $settings = Setting::cached();

        return view('admin.settings.edit', compact('settings'));
    }

    /**
     * Сохранение настроек.
     */
    public function update(Request $request): RedirectResponse
    {
        $updated = 0;

        // Обработка загрузки изображения здания
        if ($request->hasFile('home_building_image')) {
            $request->validate(['home_building_image' => 'image|max:5120']);
            $oldPath = Setting::get('home.building_image');
            $this->imageUploadService->delete($oldPath);
            $path = $this->imageUploadService->upload($request->file('home_building_image'), 'settings');
            Setting::set('home.building_image', $path);
            $updated++;
        }

        // Удаление изображения здания
        if ($request->boolean('remove_building_image')) {
            $oldPath = Setting::get('home.building_image');
            $this->imageUploadService->delete($oldPath);
            Setting::set('home.building_image', null);
            $updated++;
        }

        $data = $request->except(['_token', '_method', 'home_building_image', 'remove_building_image']);

        // Развернуть вложенные массивы (contacts.address и т.д.)
        $flat = [];
        $this->flattenArray($data, $flat);

        foreach ($flat as $key => $value) {
            if (in_array($key, Setting::ALLOWED_KEYS) && $key !== 'home.building_image') {
                Setting::set($key, $value);
                $updated++;
            }
        }

        return redirect()->route('admin.settings')->with('success', "Настройки сохранены ({$updated}).");
    }

    /**
     * Рекурсивное «разворачивание» вложенных массивов в точечную нотацию.
     */
    private function flattenArray(array $array, array &$result, string $prefix = ''): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->flattenArray($value, $result, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }
    }
}
