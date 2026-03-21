# MySmoothie - Produkt-Konfigurator (Web-Technologien)

Dieses Projekt ist eine Umsetzung mit serverseitigem PHP-Rendering, Vanilla JavaScript und MariaDB.

## Stack

- Frontend: servergerenderte HTML-Seiten, Bootstrap 5 (lokal), Vanilla JavaScript
- Backend: PHP 8.4 (Apache)
- Datenbank: MariaDB
- Tools: Docker Compose, phpMyAdmin

## Projektstruktur

- `frontend/public/` - oeffentliche Weboberflaeche (Seiten, API-Endpunkte, Assets)
- `frontend/templates/` - wiederverwendbare Frontend-Layouts
- `backend/app/` - Backend-Logik (Auth, Validation, Repositories, Pricing, Configuration Options, Services, Bootstrap)
- `backend/database/connector.php` - Datenbankverbindung
- `database/init/` - Schema + Seed fuer Erststart
- `database/migrations/` - inkrementelle SQL-Migrationen fuer bestehende DBs
- `database/dump/projektdump.sql` - SQL-Dump fuer Abgabe/Import
- `docker-compose.yml` - lokale Laufumgebung

Hinweis: Das alte React-Prototyp-Geruest wurde entfernt, damit nur noch der aktive Anwendungsstand im Repository liegt.

## .env

- `.env` enthaelt echte lokale Laufzeitwerte (z. B. Passwoerter) und bleibt lokal auf deinem Rechner.

## Schichtentrennung (Frontend/Backend)

- `frontend/public/*.php` fungiert als duenne Controller- und View-Schicht.
- Fachlogik liegt in `backend/app/services.php` (Use-Cases).
- Datenbankzugriffe liegen ausschliesslich in `backend/app/repositories.php`.
- Validierung/Pricing sind als wiederverwendbare Backend-Module gekapselt.
- Vorteil: klarere Verantwortlichkeiten, bessere Testbarkeit und weniger Duplikate.

## Start mit Docker

1. Docker Desktop starten.
2. Werte in `.env` bei Bedarf anpassen (mindestens `DB_PASSWORD` und `DB_ROOT_PASSWORD`).
3. Im Projektordner ausfuehren:

```bash
docker compose up -d
```

4. Aufrufe:
- Webapp: `http://localhost`
- phpMyAdmin: `http://localhost:8081`

Wenn Schema/Seed-Dateien geaendert wurden und neu eingespielt werden sollen:

```bash
docker compose down -v
docker compose up -d
```

## Bestehende DB aktualisieren (ohne Reset)

Wenn die Datenbank bereits laeuft, werden Aenderungen in `database/init/*.sql` nicht automatisch uebernommen.
In diesem Fall die passenden Migrationen manuell ausfuehren (z. B. in DBeaver oder phpMyAdmin):

- `database/migrations/003_update_ingredient_image_paths_to_jpeg.sql`
- `database/migrations/004_add_sweetener_type_columns.sql`
- `database/migrations/005_cleanup_runtime_data_for_demo.sql` (optional vor Demo/Abgabe)

Nach dem Ausfuehren Seite hart neu laden (`Ctrl+Shift+R`), damit der Browser-Cache keine alten Assets anzeigt.

## Aktive DB-Konfiguration (Docker)

Die Verbindungsdaten werden über `.env` gesteuert:

- `DB_HOST`
- `DB_PORT`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `APP_DEBUG` (optional, `0` oder `1`)
- `APP_TIMEZONE` (optional, z. B. `Europe/Berlin`)
- `PASSWORD_PEPPER` (optional zusaetzlicher Geheimwert fuer Passwort-Hashing)

## Login-Testdaten

Es gibt bewusst keine vorab angelegten Benutzer. Nutzer koennen ueber `register.php` erstellt werden.

Beispiel-Gutscheine aus dem Seed:

- `FIT10` (10 Prozent)
- `WELCOME5` (5 EUR fix)

## Erfuellte Projektpunkte

- Landing Page
- Registrierung + Login
- Konfigurator mit 4 Schritten
- Zutaten-Schritt mit 24 Wahlmoeglichkeiten
- Sinnvolle Visualisierung
- Zusammenfassung inkl. Preis und Button "Jetzt bestellen"
- Serverseitiges Speichern eingeloggter Nutzer in MariaDB
- Zwei Zusatzfeatures:
  - Gutscheincodes
  - Vorkonfigurierte Presets

## Hinweise fuer Abgabe

- SQL-Dump: `database/dump/projektdump.sql`
- Optional vor Demo/Abgabe Laufzeitdaten bereinigen:
  - `database/migrations/005_cleanup_runtime_data_for_demo.sql`
- Es werden nur relative Links verwendet (kein `http://localhost/...`, kein fuehrender `/`)
- Lokale Bootstrap-Dateien: `frontend/public/assets/vendor/bootstrap/`
