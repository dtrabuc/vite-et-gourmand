<footer class="bg-primary text-white mt-auto pt-5 pb-4">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h2 class="h5 fw-bold">Vite &amp; Gourmand</h2>
                <p class="small mb-0">Traiteur événementiel à Bordeaux : des menus préparés pour vos repas privés et professionnels.</p>
            </div>

            <div class="col-md-5">
                <h2 class="h6 fw-bold">Horaires d’ouverture</h2>
                <?php
                // Horaires lus en base (modifiables dans /admin/hours), chargés par BaseController::render().
                $footerDays = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
                $footerTime = static fn (?string $time): string => str_replace(':', 'h', substr((string) $time, 0, 5));
                $footerHours = [];
                foreach (is_array($openingHours ?? null) ? $openingHours : [] as $row) {
                    $footerHours[(int) $row['day_of_week']] = $row;
                }
                ?>
                <?php if ($footerHours === []): ?>
                    <p class="small mb-0">Horaires momentanément indisponibles.</p>
                <?php else: ?>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($footerDays as $dayNumber => $dayName): ?>
                            <?php
                            $row = $footerHours[$dayNumber] ?? null;
                            $windows = [];
                            if ($row !== null && (int) $row['is_open'] === 1) {
                                foreach ([['opening_time', 'closing_time'], ['opening_time_2', 'closing_time_2']] as [$openKey, $closeKey]) {
                                    if (!empty($row[$openKey]) && !empty($row[$closeKey])) {
                                        $windows[] = $footerTime($row[$openKey]) . '–' . $footerTime($row[$closeKey]);
                                    }
                                }
                            }
                            ?>
                            <li><?= $escape($dayName) ?> : <?= $windows === [] ? '<strong>Fermé</strong>' : $escape(implode(' / ', $windows)) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="col-md-3">
                <h2 class="h6 fw-bold">Informations</h2>
                <a class="link-light small d-block" href="/menus">Nos menus</a>
                <a class="link-light small d-block" href="/contact">Contact</a>
                <a class="link-light small d-block" href="/legal">Mentions légales</a>
                <a class="link-light small d-block" href="/cgv">CGV</a>
                <a class="link-light small d-block" href="/confidentialite">Confidentialité</a>
                <a class="link-light small d-block" href="/quote">Demander un devis</a>
                <h2 class="h6 fw-bold mt-3">Connexion</h2>
                <div class="footer-auth">
                    <a class="btn footer-auth-btn footer-auth-btn--client" href="/login">Espace client</a>
                </div>
                <?php /* accès de l'équipe : discret, dans un sous-menu repliable */ ?>
                <details class="footer-staff mt-3">
                    <summary>Accès réservé</summary>
                    <a class="link-light small footer-staff__link" href="/admin/login" rel="nofollow">Espace équipe (employés, administrateur)</a>
                </details>
            </div>
        </div>
    </div>
</footer>

<?php /* Bootstrap JS par CDN (jsDelivr), même version que le CSS ; integrity = empreinte SRI : le navigateur refuse le fichier s'il a été modifié */ ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous" defer></script>
<script src="/assets/js/accessibility.js" defer></script>
<script src="/assets/js/api.js" defer></script>
<script src="/assets/js/script.js" defer></script>
<script src="/assets/js/notifications.js" defer></script>
<script src="/assets/js/password.js" defer></script>
<script src="/assets/js/menus.js" defer></script>
<script src="/assets/js/menu-detail.js" defer></script>
<script src="/assets/js/composition.js" defer></script>
<script src="/assets/js/order.js" defer></script>
<script src="/assets/js/order-edit.js" defer></script>
<script src="/assets/js/address.js" defer></script>
<?php /* module téléphone (intl-tel-input), seulement là où il y a un champ téléphone */ ?>
<?php if (!empty($phoneInput)): ?>
<script type="module" src="/assets/js/phone-input.js"></script>
<?php endif; ?>