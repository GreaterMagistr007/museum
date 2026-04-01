<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Заполнение таблицы настроек начальными данными из хардкода шаблонов.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Контакты
            [
                'key' => 'contacts.address',
                'value' => 'г. Иркутск, ул. Ярослава Гашека, д. 5',
                'group' => 'contacts',
            ],
            [
                'key' => 'contacts.phone',
                'value' => '+7 (3952) XX-XX-XX',
                'group' => 'contacts',
            ],
            [
                'key' => 'contacts.email',
                'value' => 'museum@example.ru',
                'group' => 'contacts',
            ],
            [
                'key' => 'contacts.map_id',
                'value' => '0882d0472dd33f77a1ebbf43d7c3768c4479d7029e56e57ce49536c1530ff6df',
                'group' => 'contacts',
            ],

            // Расписание
            [
                'key' => 'schedule.weekdays',
                'value' => '09:00 – 17:00',
                'group' => 'schedule',
            ],
            [
                'key' => 'schedule.saturday',
                'value' => '10:00 – 15:00',
                'group' => 'schedule',
            ],
            [
                'key' => 'schedule.sunday',
                'value' => 'Выходной',
                'group' => 'schedule',
            ],
            [
                'key' => 'schedule.note',
                'value' => 'Экскурсии проводятся по предварительной записи',
                'group' => 'schedule',
            ],

            // Модальные окна
            [
                'key' => 'modals.about',
                'value' => '<h4>О музее</h4>' . "\n"
                    . '<p>Музей «Иркутское юнкерское училище» — внештатное музейное образование, осуществляющее свою деятельность в здании бывшего юнкерского училища.</p>' . "\n"
                    . '<p>Музей рассказывает о богатой истории военного образования в Иркутске, начиная с 1874 года и до наших дней. В экспозиции представлены уникальные документы, фотографии, предметы быта и вооружения различных исторических эпох.</p>',
                'group' => 'modals',
            ],
            [
                'key' => 'modals.location_address',
                'value' => 'г. Иркутск, ул. Советская, д. 176',
                'group' => 'modals',
            ],

            // Общие
            [
                'key' => 'general.site_title',
                'value' => 'Музей «Иркутское юнкерское училище»',
                'group' => 'general',
            ],
            [
                'key' => 'general.site_subtitle',
                'value' => '(внештатное музейное образование, осуществляющее деятельность в здании бывшего юнкерского училища)',
                'group' => 'general',
            ],
        ];

        DB::transaction(function () use ($settings) {
            foreach ($settings as $item) {
                Setting::updateOrCreate(
                    ['key' => $item['key']],
                    ['value' => $item['value'], 'group' => $item['group']]
                );
            }
        });
    }
}
