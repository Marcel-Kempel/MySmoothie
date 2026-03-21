<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/app/bootstrap.php';

require_login();
$user = current_user();

// Sicherheitsnetz: Falls die Session zwischenzeitlich invalide wurde, neu anmelden.
if (!is_array($user)) {
    redirect('login.php');
}

// POST-Flow: löscht genau eine Konfiguration des aktuellen Users.
if (is_post_request()) {
    csrf_validate_or_fail($_POST['csrf_token'] ?? null);

    $configurationId = (int) ($_POST['configuration_id'] ?? 0);
    if ($configurationId > 0 && $user) {
        $deleted = service_delete_user_configuration((int) $user['id'], $configurationId);
        if ($deleted) {
            flash_set('success', 'Konfiguration wurde gelöscht.');
        } else {
            flash_set('error', 'Konfiguration konnte nicht gelöscht werden.');
        }
    }

    redirect('dashboard.php');
}

$dashboardData = service_get_dashboard_data((int) $user['id']);
$configurations = $dashboardData['configurations'];

// Mapping von technischen Enum-Werten zu lesbaren Labels aus dem zentralen Optionen-Modul.
$optionLabels = configuration_adjustment_label_map();
$adjustmentFields = configuration_adjustment_ui_definitions();

$pageTitle = 'MySmoothie | Dashboard';
$activeNav = 'dashboard';
$pageScripts = ['assets/js/dashboard.js'];

include __DIR__ . '/../templates/layout/header.php';
?>
<!-- Kopfbereich mit schneller Navigation zurück in den Konfigurator -->
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h1 class="h3 mb-1">Dashboard</h1>
    <p class="text-muted mb-0">Gespeicherte Konfigurationen für <?= e((string) $user['first_name']) ?> <?= e((string) $user['last_name']) ?></p>
  </div>
  <a class="btn btn-success" href="configurator.php">Neuen Smoothie konfigurieren</a>
</div>

<!-- Überblick über Profildaten und Anzahl gespeicherter Konfigurationen -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body row g-3">
    <div class="col-md-4">
      <div class="text-muted small">E-Mail</div>
      <div class="fw-semibold"><?= e((string) $user['email']) ?></div>
    </div>
    <div class="col-md-4">
      <div class="text-muted small">Adresse</div>
      <div class="fw-semibold"><?= e((string) $user['address']) ?></div>
    </div>
    <div class="col-md-4">
      <div class="text-muted small">Gespeicherte Smoothies</div>
      <div class="fw-semibold"><?= count($configurations) ?></div>
    </div>
  </div>
</div>

<?php if ($configurations === []): ?>
  <!-- Empty-State für neue User ohne gespeicherte Konfiguration -->
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <h2 class="h5">Noch keine Konfiguration gespeichert</h2>
      <p class="text-muted">Starte den Konfigurator und speichere deine erste Bestellung.</p>
      <a class="btn btn-success" href="configurator.php">Zum Konfigurator</a>
    </div>
  </div>
<?php else: ?>
  <!-- Kartenliste der gespeicherten Konfigurationen -->
  <div class="row g-4">
    <?php foreach ($configurations as $configuration): ?>
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                <h2 class="h5 mb-1"><?= e((string) $configuration['name']) ?></h2>
                <div class="small text-muted"><?= e(date('d.m.Y H:i', strtotime((string) $configuration['created_at']))) ?></div>
              </div>
              <span class="badge text-bg-success"><?= e((string) $configuration['size_name']) ?> (<?= (int) $configuration['size_ml'] ?> ml)</span>
            </div>

            <div class="small mb-3">
              <?php foreach ($adjustmentFields as $adjustment): ?>
                <?php
                  $field = (string) ($adjustment['field'] ?? '');
                  if ($field === '') {
                      continue;
                  }

                  $fieldLabel = (string) ($adjustment['label'] ?? $field);
                  $default = (string) ($adjustment['default'] ?? '');
                  $rawValue = (string) ($configuration[$field] ?? $default);
                  $displayValue = (string) ($optionLabels[$field][$rawValue] ?? $rawValue);
                ?>
                <div><strong><?= e($fieldLabel) ?>:</strong> <?= e($displayValue) ?></div>
              <?php endforeach; ?>
            </div>

            <div class="mb-3">
              <div class="small text-muted mb-1">Zutaten (<?= count($configuration['ingredients']) ?>)</div>
              <div class="d-flex flex-wrap gap-1">
                <?php foreach ($configuration['ingredients'] as $ingredient): ?>
                  <span class="badge text-bg-light border"><?= e((string) $ingredient['name']) ?></span>
                <?php endforeach; ?>
              </div>
            </div>

            <?php if ($configuration['toppings'] !== []): ?>
              <div class="mb-3">
                <div class="small text-muted mb-1">Toppings</div>
                <div class="d-flex flex-wrap gap-1">
                  <?php foreach ($configuration['toppings'] as $topping): ?>
                    <span class="badge text-bg-light border"><?= e((string) $topping['name']) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="border rounded p-3 bg-body-tertiary mb-3 small">
              <div class="d-flex justify-content-between"><span>Zwischensumme</span><span>EUR <?= number_format((float) $configuration['subtotal'], 2, ',', '.') ?></span></div>
              <div class="d-flex justify-content-between"><span>Rabatt</span><span>- EUR <?= number_format((float) $configuration['discount_amount'], 2, ',', '.') ?></span></div>
              <div class="d-flex justify-content-between fw-semibold fs-6 mt-2"><span>Gesamt</span><span>EUR <?= number_format((float) $configuration['total_price'], 2, ',', '.') ?></span></div>
              <?php if (!empty($configuration['coupon_code'])): ?>
                <div class="text-muted mt-1">Gutschein: <?= e((string) $configuration['coupon_code']) ?></div>
              <?php endif; ?>
            </div>

            <form method="post" class="d-inline" data-delete-config>
              <?= csrf_field() ?>
              <input type="hidden" name="configuration_id" value="<?= (int) $configuration['id'] ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm">Löschen</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include __DIR__ . '/../templates/layout/footer.php'; ?>
