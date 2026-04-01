<footer class="footer">
    @if(!empty($siteSettings['general.footer_text']))
        <p>{{ $siteSettings['general.footer_text'] }}</p>
    @else
        <p>&copy; {{ date('Y') }} Музей «Иркутское юнкерское училище». Все права защищены.</p>
    @endif
</footer>
