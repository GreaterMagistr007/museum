<header class="header">
    <div class="header__top">
        <div class="header__logo-group">
            <img src="{{ asset('images/znam-irk-obl.jpg') }}" alt="Флаг Иркутской области" class="header__img header__img--flag" title="Флаг Иркутской области">
            <img src="{{ asset('images/ivu.jpg') }}" alt="Здание юнкерского училища" class="header__img header__img--photo" title="Здание юнкерского училища">
            <div class="header__title-block">
                <h1 class="header__title">{{ $siteSettings['general.site_title'] ?? 'Музей «Иркутское юнкерское училище»' }}</h1>
                <p class="header__subtitle">{{ $siteSettings['general.site_subtitle'] ?? '(внештатное музейное образование, осуществляющее деятельность в здании бывшего юнкерского училища)' }}</p>
            </div>
            <img src="{{ asset('images/uu.jpg') }}" alt="Историческое фото" class="header__img header__img--photo-old" title="Историческое фото">
            <img src="{{ asset('images/znak-ivu.jpg') }}" alt="Знак юнкерского училища" class="header__img header__img--emblem" title="Знак юнкерского училища">
        </div>
    </div>

    <nav class="nav">
        <button class="nav__burger" id="burgerBtn" aria-label="Открыть меню" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <ul class="nav__list" id="navList">
            <li class="nav__item">
                <a href="{{ route('home') }}" class="nav__link" data-route="home">Главная</a>
            </li>
            <li class="nav__item">
                <a href="{{ route('news') }}" class="nav__link" data-route="news">Новости</a>
            </li>
            <li class="nav__item">
                <a href="{{ route('exposition') }}" class="nav__link" data-route="exposition">Экспозиция</a>
            </li>
            <li class="nav__item">
                <a href="{{ route('archive') }}" class="nav__link" data-route="archive">Архив</a>
            </li>

            {{-- Dropdown "Военный городок" — формируется из БД --}}
            @if($navArticles->isNotEmpty())
                @foreach($navArticles as $rootArticle)
                    @if($rootArticle->children->isNotEmpty())
                        {{-- Корневая статья с дочерними — выводим как dropdown --}}
                        <li class="nav__item nav__item--dropdown" data-nav-group="article-{{ $rootArticle->slug }}">
                            <a href="{{ route('article.show', $rootArticle) }}" class="nav__link nav__link--has-dropdown"
                               data-route="article">
                                {{ $rootArticle->nav_title ?: $rootArticle->title }}
                                <span class="nav__arrow">▾</span>
                            </a>
                            <ul class="nav__dropdown">
                                @foreach($rootArticle->children as $child)
                                    <li>
                                        <a href="{{ route('article.show', $child) }}" class="nav__dropdown-link">
                                            {{ $child->nav_title ?: $child->title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @else
                        {{-- Корневая статья без дочерних — обычная ссылка --}}
                        <li class="nav__item" data-nav-group="article-{{ $rootArticle->slug }}">
                            <a href="{{ route('article.show', $rootArticle) }}" class="nav__link" data-route="article">
                                {{ $rootArticle->nav_title ?: $rootArticle->title }}
                            </a>
                        </li>
                    @endif
                @endforeach
            @endif

            <li class="nav__item">
                <a href="{{ route('excursions') }}" class="nav__link" data-route="excursions">Экскурсии</a>
            </li>
            <li class="nav__item">
                <a href="{{ route('about') }}" class="nav__link" data-route="about">О музее</a>
            </li>
            <li class="nav__item">
                <a href="{{ route('contacts') }}" class="nav__link" data-route="contacts">Контакты</a>
            </li>
        </ul>
    </nav>
</header>
