<?php

declare(strict_types=1);
?>
    </main>

    <footer class="border-top py-4 bg-white">
      <div class="container d-flex flex-column flex-md-row justify-content-between gap-2 small text-muted">
        <span>MySmoothie Produkt-Konfigurator</span>
        <span>Projektarbeit Web-Technologien WiSe 2025/2026</span>
      </div>
    </footer>

    <script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
      <?php foreach ($pageScripts as $scriptPath): ?>
        <script src="<?= e((string) $scriptPath) ?>"></script>
      <?php endforeach; ?>
    <?php endif; ?>
  </body>
</html>