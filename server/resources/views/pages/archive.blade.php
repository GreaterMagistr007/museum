@extends('layouts.app')

@section('title', 'Архив — Музей «Иркутское юнкерское училище»')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Архив', 'url' => null],
    ]" />
    <h2 class="page__title">Архив</h2>
    <div class="card-list">
        <div class="card">
            <div class="card__image">Фото документа</div>
            <div class="card__body">
                <h3 class="card__title">Приказы по Иркутскому юнкерскому училищу</h3>
                <p class="card__text">Подлинные приказы начальника училища за 1890–1917 годы. Назначения, производство в чины, распоряжения по учебному процессу и внутреннему распорядку.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото документа</div>
            <div class="card__body">
                <h3 class="card__title">Фотографические материалы</h3>
                <p class="card__text">Коллекция исторических фотографий: групповые снимки выпусков, портреты преподавателей и юнкеров, виды здания училища и военного городка в разные периоды.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото документа</div>
            <div class="card__body">
                <h3 class="card__title">Личные дела выпускников</h3>
                <p class="card__text">Документы личного характера: послужные списки, аттестаты, свидетельства об окончании училища. Материалы о дальнейшей службе выпускников в армии.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото документа</div>
            <div class="card__body">
                <h3 class="card__title">Планы и карты военного городка</h3>
                <p class="card__text">Архитектурные планы зданий, топографические карты территории училища и прилегающих кварталов. Чертежи конца XIX — первой половины XX века.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото документа</div>
            <div class="card__body">
                <h3 class="card__title">Переписка и рапорты</h3>
                <p class="card__text">Служебная переписка с военным ведомством, губернскими властями и другими учреждениями. Рапорты командиров о состоянии курсов и отряда.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
        <div class="card">
            <div class="card__image">Фото документа</div>
            <div class="card__body">
                <h3 class="card__title">Учебные программы и расписания</h3>
                <p class="card__text">Документы учебной части: программы занятий, расписания курсов, экзаменационные листы. Свидетельства об организации образовательного процесса в разные периоды.</p>
                <a href="#" class="card__link">Подробнее →</a>
            </div>
        </div>
    </div>
</div>
@endsection
