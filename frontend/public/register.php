<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/app/bootstrap.php';

// Bereits eingeloggte User sollen nicht erneut registrieren.
if (is_logged_in()) {
    redirect('dashboard.php');
}

$formValues = [
    'first_name' => '',
    'last_name' => '',
    'address' => '',
    'email' => '',
];
$errors = [];

// Formular-Submit: validieren, Duplikat-E-Mail prüfen und User anlegen.
if (is_post_request()) {
    csrf_validate_or_fail($_POST['csrf_token'] ?? null);

    $registration = service_register_user($_POST);
    $formValues = $registration['values'];
    $errors = $registration['errors'];

    if ($registration['success']) {
        login_user_by_id((int) $registration['user_id']);
        flash_set('success', 'Registrierung erfolgreich. Willkommen bei MySmoothie.');
        redirect('configurator.php');
    }
}

$pageTitle = 'MySmoothie | Registrierung';
$activeNav = '';

include __DIR__ . '/../templates/layout/header.php';
?>
<!-- Registrierungsformular mit serverseitiger Fehlerausgabe -->
<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-7">
    <div class="card shadow-sm">
      <div class="card-body p-4 p-md-5">
        <h1 class="h3 mb-3">Registrierung</h1>
        <p class="text-muted mb-4">Lege dein Konto an, um Konfigurationen in der Datenbank zu speichern.</p>

        <form method="post" novalidate>
          <?= csrf_field() ?>

          <div class="row g-3">
            <div class="col-md-6">
              <label for="first_name" class="form-label">Vorname</label>
              <input type="text" class="form-control<?= isset($errors['first_name']) ? ' is-invalid' : '' ?>" id="first_name" name="first_name" value="<?= e($formValues['first_name']) ?>" required>
              <?php if (isset($errors['first_name'])): ?>
                <div class="invalid-feedback"><?= e($errors['first_name']) ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label for="last_name" class="form-label">Nachname</label>
              <input type="text" class="form-control<?= isset($errors['last_name']) ? ' is-invalid' : '' ?>" id="last_name" name="last_name" value="<?= e($formValues['last_name']) ?>" required>
              <?php if (isset($errors['last_name'])): ?>
                <div class="invalid-feedback"><?= e($errors['last_name']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-12">
              <label for="address" class="form-label">Adresse</label>
              <input type="text" class="form-control<?= isset($errors['address']) ? ' is-invalid' : '' ?>" id="address" name="address" placeholder="Strasse, Hausnummer, PLZ, Ort" value="<?= e($formValues['address']) ?>" required>
              <?php if (isset($errors['address'])): ?>
                <div class="invalid-feedback"><?= e($errors['address']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-12">
              <label for="email" class="form-label">E-Mail</label>
              <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= e($formValues['email']) ?>" required>
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?= e($errors['email']) ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label for="password" class="form-label">Passwort</label>
              <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" required>
              <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
              <?php endif; ?>
            </div>
            <div class="col-md-6">
              <label for="password_confirm" class="form-label">Passwort bestätigen</label>
              <input type="password" class="form-control<?= isset($errors['password_confirm']) ? ' is-invalid' : '' ?>" id="password_confirm" name="password_confirm" required>
              <?php if (isset($errors['password_confirm'])): ?>
                <div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-success btn-lg">Registrieren</button>
          </div>
        </form>

        <p class="text-muted mt-4 mb-0">
          Bereits registriert? <a href="login.php">Zum Login</a>
        </p>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/layout/footer.php'; ?>
