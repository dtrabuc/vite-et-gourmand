<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <article class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Politique de confidentialité</h1>
                        <p class="text-muted">
                            Cette page explique quelles données personnelles Vite &amp; Gourmand traite, pourquoi, combien de
                            temps, et comment exercer vos droits (règlement européen 2016/679, « RGPD »).
                        </p>

                        <h2 class="h5 mt-4">Responsable du traitement</h2>
                        <p>
                            Vite &amp; Gourmand (Julie et José), <?= $escape($companyAddress) ?>.
                            Contact : <a href="mailto:<?= $escape($contactEmail) ?>"><?= $escape($contactEmail) ?></a>.
                            Entreprise fictive réalisée dans le cadre d’un projet de formation (voir les <a href="/legal">mentions légales</a>).
                        </p>

                        <h2 class="h5 mt-4">Données traitées, finalités et bases légales</h2>
                        <div class="table-responsive">
                            <table class="table table-sm align-top">
                                <caption class="visually-hidden">Traitements de données personnelles</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Traitement</th>
                                        <th scope="col">Données</th>
                                        <th scope="col">Base légale</th>
                                        <th scope="col">Conservation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Compte client</td>
                                        <td>Nom, prénom, e-mail, téléphone, GSM, adresse postale, mot de passe (stocké haché, jamais en clair)</td>
                                        <td>Exécution du contrat</td>
                                        <td>Tant que le compte est actif ; suppression sur demande</td>
                                    </tr>
                                    <tr>
                                        <td>Commandes</td>
                                        <td>Menu et choix des plats, nombre de convives, date, adresse et mode de prestation (avec le résultat de la vérification de l’adresse), téléphone de contact, prix, historique des statuts</td>
                                        <td>Exécution du contrat, obligations comptables</td>
                                        <td>10 ans (article L123-22 du Code de commerce)</td>
                                    </tr>
                                    <tr>
                                        <td>Allergies et demandes « sans allergène »</td>
                                        <td>Allergènes à retirer d’un plat, précisions libres pour la cuisine (données de santé : ne mentionnez aucun nom)</td>
                                        <td>Consentement explicite (case à cocher lors de la commande, article 9 du RGPD)</td>
                                        <td>Avec la commande ; effacées sur simple demande ou à la suppression du compte</td>
                                    </tr>
                                    <tr>
                                        <td>Notifications</td>
                                        <td>Messages de suivi de vos commandes et devis</td>
                                        <td>Exécution du contrat</td>
                                        <td>Tant que le compte est actif</td>
                                    </tr>
                                    <tr>
                                        <td>Formulaire de contact et demandes de devis</td>
                                        <td>E-mail, identité, téléphone, contenu du message</td>
                                        <td>Intérêt légitime (répondre aux demandes)</td>
                                        <td>3 ans après le dernier échange</td>
                                    </tr>
                                    <tr>
                                        <td>Avis clients</td>
                                        <td>Note, commentaire, lien avec la commande ; publication sous vos nom et prénom après validation</td>
                                        <td>Consentement (dépôt volontaire de l’avis)</td>
                                        <td>Tant que l’avis est publié ; retrait sur demande</td>
                                    </tr>
                                    <tr>
                                        <td>Sécurité du site</td>
                                        <td>Adresse IP (limitation du nombre de tentatives), nombre d’échecs de connexion, jetons de réinitialisation du mot de passe</td>
                                        <td>Intérêt légitime (protection des comptes)</td>
                                        <td>Adresse IP : 5 minutes ; jeton de réinitialisation : 1 heure</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h2 class="h5 mt-4">Destinataires et sous-traitants</h2>
                        <ul>
                            <li>Julie, José et les employés de Vite &amp; Gourmand, pour le traitement des commandes, des messages et des avis.</li>
                            <li>L’hébergeur du site et le prestataire d’envoi d’e-mails, uniquement pour assurer ces services.</li>
                            <li>OpenStreetMap : l’adresse de livraison saisie est transmise par notre serveur au service Photon (komoot, Allemagne) pour la retrouver, et les coordonnées au service OSRM (FOSSGIS, Allemagne) pour calculer la distance par la route. Aucune donnée d’identité n’est transmise et les données restent dans l’Union européenne.</li>
                            <li>jsDelivr (bibliothèque Bootstrap) et Unsplash (photographies) : ces services reçoivent l’adresse IP du navigateur lors du chargement de la page.</li>
                        </ul>
                        <p>Vos données ne sont jamais vendues ni utilisées à des fins publicitaires.</p>

                        <h2 class="h5 mt-4">Cookies</h2>
                        <p>
                            Le site dépose un seul cookie, strictement nécessaire : le cookie de session, qui maintient votre
                            connexion et protège les formulaires. Il est inaccessible aux scripts (HttpOnly) et expire après
                            <?= (int) $sessionLifetime ?> minutes. Aucun cookie de mesure d’audience ou de publicité n’est utilisé ;
                            aucun consentement n’est donc demandé.
                        </p>

                        <h2 class="h5 mt-4">Sécurité</h2>
                        <p>
                            Mots de passe hachés (bcrypt) et soumis à une politique de robustesse, protection des formulaires
                            contre les requêtes intersites (jeton CSRF), limitation des tentatives de connexion et blocage
                            temporaire après cinq échecs, requêtes préparées en base de données, échappement des contenus affichés.
                        </p>

                        <h2 class="h5 mt-4">Vos droits</h2>
                        <p>
                            Vous disposez d’un droit d’accès, de rectification, d’effacement, de limitation, de portabilité et
                            d’opposition. Depuis <a href="/profile">votre profil</a>, vous pouvez modifier vos informations,
                            <strong>télécharger toutes vos données</strong> (fichier JSON) et <strong>supprimer votre compte</strong>
                            (vos informations personnelles sont alors effacées ; les commandes passées sont conservées sans votre identité
                            pour les obligations comptables).
                            Pour toute autre demande, écrivez à <a href="mailto:<?= $escape($contactEmail) ?>"><?= $escape($contactEmail) ?></a>
                            ou utilisez la <a href="/contact">page de contact</a> ; une réponse vous est apportée sous un mois.
                            Vous pouvez également adresser une réclamation à la CNIL (www.cnil.fr).
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </div>
</main>
