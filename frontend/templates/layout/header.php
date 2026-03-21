<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'MySmoothie';
$activeNav = $activeNav ?? '';
$user = current_user();
$flashSuccess = flash_get('success');
$flashError = flash_get('error');
?>
<!doctype html>
<html lang="de">
  <head>
    <!-- Basis-Metadaten + lokale CSS-Assets -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
  </head>
  <body class="bg-body-tertiary">
    <!-- Hauptnavigation: je nach Login-Zustand unterschiedliche Actions -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
      <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php">MySmoothie</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link<?= $activeNav === 'home' ? ' active' : '' ?>" href="index.php">Start</a>
            </li>
            <li class="nav-item">
              <a class="nav-link<?= $activeNav === 'configurator' ? ' active' : '' ?>" href="configurator.php">Konfigurator</a>
            </li>
            <?php if ($user): ?>
              <li class="nav-item">
                <a class="nav-link<?= $activeNav === 'dashboard' ? ' active' : '' ?>" href="dashboard.php">Dashboard</a>
              </li>
            <?php endif; ?>
          </ul>
          <div class="d-flex gap-2">
            <?php if ($user): ?>
              <span class="small text-muted d-none d-md-inline align-self-center me-2">
                Hallo, <?= e($user['first_name']) ?>
              </span>
              <a class="btn btn-outline-secondary btn-sm" href="logout.php">Abmelden</a>
            <?php else: ?>
              <a class="btn btn-outline-secondary btn-sm" href="login.php">Login</a>
              <a class="btn btn-success btn-sm" href="register.php">Registrierung</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </nav>

    <!-- Seiteninhalt; Flash-Messages werden zentral im Layout angezeigt -->
    <main class="container py-4">
      <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= e($flashSuccess) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= e($flashError) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>
