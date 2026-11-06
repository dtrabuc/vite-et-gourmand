<main class="py-5">
    <div class="container">
        <h1 class="h2 text-primary mb-4">Chiffre d'affaires par menu</h1>

        <form method="get" action="/admin/revenue" class="row g-3 card border-0 shadow-sm p-3 mb-4">
            <div class="col-md-4">
                <label class="form-label" for="revenue_menu">Menu</label>
                <select class="form-select" id="revenue_menu" name="menu_id">
                    <option value="">Tous</option>
                    <?php foreach ($menus as $menu): ?>
                        <option value="<?= $menu->getId() ?>" <?= (int) ($menuId ?? 0) === $menu->getId() ? 'selected' : '' ?>><?= $escape($menu->getTitle()) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="revenue_from">Du</label>
                <input class="form-control" id="revenue_from" type="date" name="from" value="<?= $escape($from ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="revenue_to">Au</label>
                <input class="form-control" id="revenue_to" type="date" name="to" value="<?= $escape($to ?? '') ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <button class="btn btn-primary w-100" type="submit">Filtrer</button>
            </div>
        </form>

        <div class="table-responsive card border-0 shadow-sm" tabindex="0" role="region" aria-label="Chiffre d’affaires par menu (défilable)">
            <table class="table mb-0">
                <caption class="visually-hidden">Commandes terminées et chiffre d'affaires par menu</caption>
                <thead>
                    <tr>
                        <th scope="col">Menu</th>
                        <th scope="col" class="text-end">Commandes</th>
                        <th scope="col" class="text-end">CA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revenue as $row): ?>
                        <tr>
                            <td><?= $escape($row['menu_title']) ?></td>
                            <td class="text-end"><?= (int) $row['order_count'] ?></td>
                            <td class="text-end text-nowrap"><?= number_format((float) $row['revenue'], 2, ',', ' ') ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
