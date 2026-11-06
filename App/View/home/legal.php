<?php
$hosting = is_array($hosting ?? null) ? $hosting : [];
$hostingName = (string) ($hosting['name'] ?? '');
?>
<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Mentions légales</h1>
                        <p class="text-muted">
                            Informations prévues par l’article 6 de la loi n° 2004-575 du 21 juin 2004
                            pour la confiance dans l’économie numérique (LCEN).
                        </p>

                        <div class="alert alert-info">
                            Vite &amp; Gourmand est une entreprise <strong>fictive</strong>, créée pour l’Évaluation en Cours
                            de Formation (ECF) du titre professionnel Développeur Web et Web Mobile. Elle ne dispose ni de
                            numéro SIRET ni de numéro de TVA intracommunautaire, et aucune commande passée sur ce site ne
                            donne lieu à une prestation réelle.
                        </div>

                        <h2 class="h5 mt-4">Éditeur du site</h2>
                        <ul>
                            <li>Raison sociale : Vite &amp; Gourmand, traiteur évènementiel</li>
                            <li>Adresse : <?= $escape($companyAddress) ?></li>
                            <li>Contact : <a href="mailto:<?= $escape($contactEmail) ?>"><?= $escape($contactEmail) ?></a> ou via la <a href="/contact">page de contact</a></li>
                            <li>Responsables de la publication : Julie et José, fondateurs</li>
                        </ul>

                        <h2 class="h5 mt-4">Conception et développement</h2>
                        <p>Application réalisée par Dylan Trabuc dans le cadre du projet ECF.</p>

                        <h2 class="h5 mt-4">Hébergement</h2>
                        <?php if ($hostingName !== ''): ?>
                            <ul>
                                <li>Hébergeur : <?= $escape($hostingName) ?></li>
                                <?php if (($hosting['address'] ?? '') !== ''): ?>
                                    <li>Adresse : <?= $escape($hosting['address']) ?></li>
                                <?php endif; ?>
                                <?php if (($hosting['website'] ?? '') !== ''): ?>
                                    <li>Site : <?= $escape($hosting['website']) ?></li>
                                <?php endif; ?>
                            </ul>
                        <?php else: ?>
                            <p>
                                Cette instance du site fonctionne en environnement de développement, sans hébergeur tiers.
                                Sur une instance en ligne, le nom et l’adresse de l’hébergeur figurent ici.
                            </p>
                        <?php endif; ?>

                        <h2 class="h5 mt-4">Propriété intellectuelle</h2>
                        <p>
                            Les textes, la présentation et les menus de ce site appartiennent à Vite &amp; Gourmand.
                            Les photographies des menus proviennent d’Unsplash et sont utilisées selon la licence Unsplash.
                            Toute reproduction sans autorisation est interdite.
                        </p>

                        <h2 class="h5 mt-4">Données personnelles et cookies</h2>
                        <p>
                            Le traitement des données personnelles (compte, commandes, contact, avis) est décrit dans la
                            <a href="/index">politique de confidentialité</a>. Le site n’utilise qu’un cookie de
                            session strictement nécessaire à son fonctionnement (connexion et sécurité des formulaires),
                            sans mesure d’audience ni publicité : aucun consentement préalable n’est donc requis.
                        </p>

                        <h2 class="h5 mt-4">Conditions de vente</h2>
                        <p>Les commandes sont soumises aux <a href="/index">conditions générales de vente</a>.</p>

                        <h2 class="h5 mt-4">Droit applicable</h2>
                        <p>Le site et ces mentions sont soumis au droit français.</p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</main>
