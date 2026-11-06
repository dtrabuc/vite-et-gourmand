<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-5">
                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h1 class="h2 text-primary">Nouveau mot de passe</h1>
                        <form method="post" action="/reset-password/<?= $escape($token) ?>">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <div class="mb-3">
                                <label class="form-label" for="new_password">Nouveau mot de passe</label>
                                <input class="form-control" id="new_password" name="password"
                                       type="password" autocomplete="new-password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="confirm_password">Confirmation</label>
                                <input class="form-control" id="confirm_password" name="confirm_password"
                                       type="password" autocomplete="new-password" required>
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Modifier le mot de passe</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>