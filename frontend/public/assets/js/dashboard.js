// Schützt vor versehentlichem Löschen gespeicherter Konfigurationen.
document.querySelectorAll('[data-delete-config]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const approved = window.confirm('Soll diese gespeicherte Konfiguration wirklich gelöscht werden?');
    if (!approved) {
      event.preventDefault();
    }
  });
});
