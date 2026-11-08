<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Conditions générales de vente</h1>
                        <p class="text-muted">
                            Vite &amp; Gourmand, <?= $escape($companyAddress) ?> —
                            <a href="mailto:<?= $escape($contactEmail) ?>"><?= $escape($contactEmail) ?></a>.
                            Entreprise fictive réalisée dans le cadre d’un projet de formation (voir les <a href="/legal">mentions légales</a>).
                        </p>

                        <h2 class="h5 mt-4" id="cgv-objet">Article 1 — Objet</h2>
                        <p>
                            Les présentes conditions s’appliquent à toute commande de menus passée sur le site par un client
                            disposant d’un compte. Passer commande implique leur acceptation sans réserve.
                        </p>

                        <h2 class="h5 mt-4" id="cgv-commande">Article 2 — Commande</h2>
                        <ul>
                            <li>Le client choisit un menu, un nombre de convives au moins égal au minimum indiqué pour ce menu, une date et un créneau pendant les horaires d’ouverture, ainsi qu’un mode de prestation : livraison, retrait ou service sur place.</li>
                            <li>Une commande n’est possible que dans la limite du stock disponible du menu. Les conditions propres à chaque menu (délai de commande, conservation…) sont affichées sur sa fiche et doivent être respectées.</li>
                            <li>La commande est confirmée par e-mail. Elle reste « en attente » jusqu’à son acceptation par notre équipe ; le client suit ensuite chaque étape depuis son espace.</li>
                            <li>Une demande de devis reste possible pour une prestation sur mesure ; elle est facultative.</li>
                        </ul>

                        <h2 class="h5 mt-4" id="cgv-prix">Article 3 — Prix</h2>
                        <ul>
                            <li>Les prix sont indiqués en euros toutes taxes comprises. Le prix affiché d’un menu correspond à son nombre minimum de personnes ; il est ajusté proportionnellement au nombre réel de convives.</li>
                            <li>Une remise de 10 % s’applique sur le prix du menu dès que le nombre de convives dépasse d’au moins 5 personnes le minimum du menu.</li>
                            <li>La livraison est offerte dans Bordeaux. En dehors de Bordeaux, elle est facturée 5 € plus 0,59 € par kilomètre, la distance routière étant calculée automatiquement depuis notre adresse. Si cette distance ne peut pas être calculée, la commande en livraison ne peut pas être enregistrée.</li>
                            <li>Le détail du prix (menu, remise, livraison et total) est présenté avant la validation de la commande.</li>
                        </ul>

                        <h2 class="h5 mt-4" id="cgv-paiement">Article 4 — Paiement</h2>
                        <p>Le règlement s’effectue en espèces, au moment de la remise de la commande.</p>

                        <h2 class="h5 mt-4" id="cgv-modification">Article 5 — Modification et annulation</h2>
                        <ul>
                            <li>Le client peut modifier sa commande depuis son espace (tout sauf le menu : choix des plats, nombre de convives, date, adresse…) ou l’annuler, tant qu’elle n’a pas été acceptée par notre équipe et dans les délais suivants à compter de la commande : <strong>15 minutes</strong> si la prestation a lieu le jour même, <strong>24 heures</strong> si elle a lieu le lendemain, <strong>48 heures</strong> si elle a lieu plus tard, et jamais après l’heure de la prestation. Le délai exact est affiché sur la commande.</li>
                            <li>Une fois la commande acceptée, toute modification ou annulation passe par un contact avec notre équipe. L’équipe peut elle-même modifier ou annuler une commande après avoir contacté le client ; le mode de contact et le motif sont alors enregistrés.</li>
                        </ul>

                        <h2 class="h5 mt-4" id="cgv-prestation">Article 6 — Livraison, retrait et service sur place</h2>
                        <p>
                            La prestation a lieu à la date et au créneau choisis. Pour une livraison, le client s’assure
                            qu’une personne est présente à l’adresse indiquée ; pour un retrait ou un service sur place,
                            la prestation se déroule à notre adresse.
                        </p>
                        <ul>
                            <li>Livraison offerte dans la ville de Bordeaux.</li>
                            <li>Jusqu’à 25 km de Bordeaux : 5 € majorés de 0,59 € par kilomètre parcouru.</li>
                            <li>Au-delà de 25 km : retrait gratuit à Bordeaux, ou livraison au même tarif kilométrique si le client l’accepte expressément lors de la commande.</li>
                            <li>Si l’adresse ne peut pas être vérifiée et que la distance n’a pas pu être calculée, les frais de livraison sont confirmés par téléphone avant l’acceptation de la commande.</li>
                        </ul>

                        <h2 class="h5 mt-4" id="cgv-materiel">Article 7 — Prêt de matériel</h2>
                        <p>
                            Du matériel peut être prêté avec la commande. Dans ce cas, la commande passe au statut
                            « en attente du retour de matériel » et le client en est informé par e-mail.
                            <strong>Le client dispose alors de 10 jours ouvrés pour restituer le matériel. À défaut de
                            restitution dans ce délai, des frais de 600 euros lui sont facturés.</strong>
                            Pour organiser la restitution, le client prend contact avec Vite &amp; Gourmand.
                            La commande est terminée une fois le matériel restitué.
                        </p>

                        <h2 class="h5 mt-4" id="cgv-allergenes">Article 8 — Allergènes et régimes alimentaires</h2>
                        <p>
                            Les allergènes de chaque plat sont indiqués sur la fiche du menu et lors de la commande. Il appartient au client de
                            les vérifier et d’en informer ses convives. Le client peut, sans supplément, remplacer un plat par un autre
                            de la même catégorie compatible avec le menu, ou demander une préparation sans un allergène pour certains convives.
                            Ces préparations sont réalisées à part, mais notre cuisine manipule les 14 allergènes réglementaires :
                            l’absence totale de traces ne peut pas être garantie.
                        </p>

                        <h2 class="h5 mt-4" id="cgv-retractation">Article 9 — Droit de rétractation</h2>
                        <p>
                            Conformément à l’article L221-28 du Code de la consommation, le droit de rétractation ne
                            s’applique pas aux prestations de restauration fournies à une date déterminée, ni aux denrées
                            susceptibles de se détériorer rapidement.
                        </p>

                        <h2 class="h5 mt-4" id="cgv-avis">Article 10 — Avis clients</h2>
                        <p>
                            Une fois la commande terminée, le client peut laisser une note de 1 à 5 et un commentaire.
                            Les avis sont publiés après validation par notre équipe.
                        </p>

                        <h2 class="h5 mt-4" id="cgv-donnees">Article 11 — Données personnelles</h2>
                        <p>Les données nécessaires aux commandes sont traitées selon la <a href="/confidentialite">politique de confidentialité</a>.</p>

                        <h2 class="h5 mt-4" id="cgv-litiges">Article 12 — Réclamations et litiges</h2>
                        <p>
                            Toute réclamation peut être adressée via la <a href="/contact">page de contact</a>.
                            Les présentes conditions sont soumises au droit français. Une entreprise réelle devrait désigner
                            un médiateur de la consommation (article L612-1 du Code de la consommation) ; Vite &amp; Gourmand
                            étant fictive, aucun médiateur n’est désigné.
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</main>
