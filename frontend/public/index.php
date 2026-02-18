<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/app/bootstrap.php';

$pageTitle = 'MySmoothie | Landing Page';
$activeNav = 'home';

include __DIR__ . '/../templates/layout/header.php';
?>
<section class="hero rounded-4 overflow-hidden mb-5">
  <div class="row g-0 align-items-stretch">
    <div class="col-lg-6 p-4 p-lg-5 bg-white">
      <span class="badge text-bg-success mb-3">Produkt-Konfigurator</span>
      <h1 class="display-5 fw-bold mb-3">Stelle deinen Smoothie in 4 Schritten zusammen</h1>
      <p class="lead mb-4">
        MySmoothie ist ein interaktiver Konfigurator für individuelle Smoothies mit 24 Zutaten, Live-Vorschau,
        Preisberechnung und serverseitiger Speicherung.
      </p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-success btn-lg" href="configurator.php">Jetzt konfigurieren</a>
        <?php if (!is_logged_in()): ?>
          <a class="btn btn-outline-secondary btn-lg" href="register.php">Kostenlos registrieren</a>
        <?php else: ?>
          <a class="btn btn-outline-secondary btn-lg" href="dashboard.php">Zum Dashboard</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-6">
      <img
        src="https://images.unsplash.com/photo-1553530666-ba11a7da3888?w=1200"
        alt="Frischer Smoothie"
        class="w-100 h-100 object-cover"
        style="min-height: 320px;"
      >
    </div>
  </div>
</section>

<section class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h5">24+ Zutaten</h2>
        <p class="text-muted mb-0">Früchte, Gemüse und Proteinquellen stehen im Zutaten-Schritt zur Auswahl.</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h5">Live Visualisierung</h2>
        <p class="text-muted mb-0">Die Vorschau reagiert direkt auf Größe, Zutaten und Konsistenz.</p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body">
        <h2 class="h5">Speichern im Konto</h2>
        <p class="text-muted mb-0">Eingeloggte Nutzer können ihre Konfigurationen serverseitig abspeichern.</p>
      </div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../templates/layout/footer.php'; ?>
