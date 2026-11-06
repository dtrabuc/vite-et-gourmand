<main class="flex-grow-1 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <section class="card border-0 shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Connexion</h1>
                        <p class="text-muted">Accédez à vos commandes et à votre profil.</p>
                        <form method="post" action="/login">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <div class="mb-3">
                                <label class="form-label" for="email">Adresse email</label>
                                <input class="form-control" id="email" name="email" type="email"
                                       autocomplete="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="password">Mot de passe</label>
                                <input class="form-control" id="password" name="password" type="password"
                                       autocomplete="current-password" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Se connecter</button>
                        </form>
                        <p class="mt-4 mb-0">
                            <a href="/forgot-password">Mot de passe oublié ?</a> ·
                            <a href="/register">Créer un compte</a>
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>