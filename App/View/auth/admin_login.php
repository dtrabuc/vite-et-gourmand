<main class="flex-grow-1 d-flex align-items-center py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <section class="card border-0 shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <span class="badge bg-primary-subtle text-primary">Accès réservé</span>
                        <h1 class="h2 text-primary mt-3">Connexion équipe</h1>
                        <p class="text-muted">Espace réservé aux employés et administrateurs.</p>

                        <form method="post" action="/admin/login">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <div class="mb-3">
                                <label class="form-label" for="admin_email">Email</label>
                                <input class="form-control" id="admin_email" name="email" type="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="admin_password">Mot de passe</label>
                                <input class="form-control" id="admin_password" name="password" type="password" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Se connecter</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>