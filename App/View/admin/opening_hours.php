<?php
$days = [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi',7=>'Dimanche'];
$byDay = [];
foreach ($openingHours as $hour) {
    $byDay[(int) $hour['day_of_week']] = $hour;
}
?>
<main class="py-5">
  <div class="container">
    <h1 class="h2 text-primary mb-4">Horaires d'ouverture</h1>
    <p class="text-muted">Les horaires enregistrés ici sont utilisés directement par le serveur pour les créneaux de commande.</p>
    <form method="post" action="/admin/hours" class="card border-0 shadow-sm">
      <div class="card-body">
        <?php foreach ($days as $day => $label): ?>
          <?php
          $h = $byDay[$day] ?? [
              'is_open' => 0,
              'opening_time' => '',
              'closing_time' => '',
              'opening_time_2' => '',
              'closing_time_2' => '',
          ];
          ?>
          <fieldset class="border rounded p-3 mb-3">
            <legend class="fs-6 fw-bold float-none w-auto px-1 mb-2"><?= $escape($label) ?></legend>
            <div class="row g-2 align-items-end">
              <div class="col-md-2">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="open_<?= $day ?>" name="is_open[<?= $day ?>]" <?= (int) $h['is_open'] === 1 ? 'checked' : '' ?>>
                  <label class="form-check-label" for="open_<?= $day ?>">Ouvert</label>
                </div>
              </div>
              <?php foreach ([0 => ['opening_time', 'closing_time'], 1 => ['opening_time_2', 'closing_time_2']] as $window => [$openKey, $closeKey]): ?>
                <div class="col-md-5">
                  <div class="row g-1">
                    <div class="col-6">
                      <label class="form-label small" for="open_<?= $day ?>_<?= $window ?>">Plage <?= $window + 1 ?> : ouverture</label>
                      <input class="form-control" type="time" id="open_<?= $day ?>_<?= $window ?>" name="windows[<?= $day ?>][<?= $window ?>][opening]" value="<?= $escape(substr((string) $h[$openKey], 0, 5)) ?>">
                    </div>
                    <div class="col-6">
                      <label class="form-label small" for="close_<?= $day ?>_<?= $window ?>">Plage <?= $window + 1 ?> : fermeture</label>
                      <input class="form-control" type="time" id="close_<?= $day ?>_<?= $window ?>" name="windows[<?= $day ?>][<?= $window ?>][closing]" value="<?= $escape(substr((string) $h[$closeKey], 0, 5)) ?>">
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </fieldset>
        <?php endforeach; ?>
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
        <button class="btn btn-primary" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
</main>
