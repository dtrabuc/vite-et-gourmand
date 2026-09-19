<footer class="bg-primary text-white mt-auto pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h2 class="h5 fw-bold">Vite &amp; Gourmand</h2>
                <p class="small mb-0">Traiteur événementiel à Bordeaux : des menus préparés pour vos repas privés et professionnels.</p>
            </div>
            <div class="col-md-5">
                <h2 class="h6 fw-bold">Horaires</h2>
                <ul class="list-unstyled small mb-0">
                    <?php
                    $days = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
                    foreach ($openingHours ?? [] as $hour):
                    ?>
                        <li><?= $escape($days[(int)$hour['day_of_week']] ?? '') ?> :
                            <?php if ((int)$hour['is_open'] === 1): ?>
                                <?= $escape(substr((string)$hour['opening_time'], 0, 5)) ?> - <?= $escape(substr((string)$hour['closing_time'], 0, 5)) ?>
                            <?php else: ?>Fermé<?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-3">
                <h2 class="h6 fw-bold">Informations</h2>
                <a class="link-light small d-block" href="/menus">Nos menus</a>
                <a class="link-light small d-block" href="/contact">Contact</a>
                <a class="link-light small d-block" href="/legal">Mentions légales</a>
                <a class="link-light small d-block" href="/cgv">CGV</a>
                <a class="link-light small d-block" href="/login">Espace client</a>
            </div>
        </div>
    </div>
</footer>
<script src="/assets/js/api.js" defer></script>
<script src="/assets/js/OrderPriceCalculator.js" defer></script>
<script src="/assets/js/script.js" defer></script>
</body>
</html>
