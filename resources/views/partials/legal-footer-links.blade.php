{{--
    Liens légaux présents sur toutes les pages des gabarits communs
    (layouts/app et layouts/auth), dont « Règles de classement »
    (article D111-6 I du code de la consommation).
    Styles : public/css/legal-footer.css, chargée dans le head des deux gabarits.
--}}
<footer class="pk-legal-footer">
    <nav class="pk-legal-footer__nav" aria-label="Informations légales">
        <ul class="pk-legal-footer__links">
            <li><a href="{{ route('legal.platform-rules') }}#classement">Règles de classement</a></li>
            <li><a href="{{ route('legal.terms') }}">CGU</a></li>
            <li><a href="{{ route('legal.privacy') }}">Confidentialité</a></li>
            <li><a href="{{ route('legal.cookies') }}">Cookies</a></li>
            <li><a href="{{ route('legal.mentions') }}">Mentions légales</a></li>
        </ul>
    </nav>
</footer>
