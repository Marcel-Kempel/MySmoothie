<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/app/bootstrap.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$formValues = [
    'email' => '',
];
$errors = [];
$generalError = '';

if (is_post_request()) {
    csrf_validate_or_fail($_POST['csrf_token'] ?? null);

    $validated = validate_login_input($_POST);
    $formValues = $validated['values'];
    $errors = $validated['errors'];

    if ($errors === []) {
        $user = repo_find_user_by_email($formValues['email']);

        if ($user && password_verify($validated['password'], (string) $user['password_hash'])) {
            login_user_by_id((int) $user['id']);
            flash_set('success', 'Login erfolgreich.');
            redirect(consume_post_login_redirect('dashboard.php'));
        }

        $generalError = 'E-Mail oder Passwort ist ungültig.';
    }
}

$pageTitle = 'MySmoothie | Login';
$activeNav = '';

include __DIR__ . '/../templates/layout/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-5 col-xl-4">
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <h1 class="h3 mb-3">Login</h1>
        <p class="text-muted mb-4">Melde dich an, um gespeicherte Konfigurationen zu verwalten.</p>

        <?php if ($generalError !== ''): ?>
          <div class="alert alert-danger"><?= e($generalError) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="email" class="form-label">E-Mail</label>
            <input type="email" class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>" id="email" name="email" value="<?= e($formValues['email']) ?>" required>
            <?php if (isset($errors['email'])): ?>
              <div class="invalid-feedback"><?= e($errors['email']) ?></div>
            <?php endif; ?>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Passwort</label>
            <input type="password" class="form-control<?= isset($errors['password']) ? ' is-invalid' : '' ?>" id="password" name="password" required>
            <?php if (isset($errors['password'])): ?>
              <div class="invalid-feedback"><?= e($errors['password']) ?></div>
            <?php endif; ?>
          </div>

          <div class="d-grid">
            <button type="submit" class="btn btn-success btn-lg">Anmelden</button>
          </div>
        </form>

        <p class="text-muted mt-4 mb-0">
          Noch kein Konto? <a href="register.php">Jetzt registrieren</a>
        </p>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../templates/layout/footer.php'; ?>
