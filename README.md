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
- `backend/app/` - Backend-Logik (Auth, Validation, Repositories, Pricing, Bootstrap)
- `backend/database/connector.php` - Datenbankverbindung
- `database/init/` - Schema + Seed fuer Erststart
- `database/dump/projektdump.sql` - SQL-Dump fuer Abgabe/Import
- `docker-compose.yml` - lokale Laufumgebung

Hinweis: Das alte React-Prototyp-Geruest wurde entfernt, damit nur noch der aktive Anwendungsstand im Repository liegt.

## Start mit Docker

1. Docker Desktop starten.
2. Im Projektordner ausfuehren:

```bash
docker compose up --build
```

3. Aufrufe:
- Webapp: `http://localhost`
- phpMyAdmin: `http://localhost:8081`

Wenn Schema/Seed-Dateien geaendert wurden und neu eingespielt werden sollen:

```bash
docker compose down -v
docker compose up --build
```

## Aktive DB-Konfiguration (Docker)

- Host: `db`
- Port: `3306`
- Datenbank: `webtec`
- User: `root`
- Passwort: `Make1207`

## Login-Testdaten

Es gibt bewusst keine vorab angelegten Benutzer. Nutzer koennen ueber `register.php` erstellt werden.

Beispiel-Gutscheine aus dem Seed:

- `FIT10` (10 Prozent)
- `WELCOME5` (5 EUR fix)

## Erfuellte Pflichtanforderungen

- Landing Page mit Kurzinfo und Bild
- Registrierung + Login
- Konfigurator mit 4 Schritten
- Zutaten-Schritt mit 24 Wahlmoeglichkeiten
- Visuelle Darstellung der Konfiguration
- Zusammenfassung inkl. Preis und Button "Jetzt bestellen"
- Serverseitiges Speichern eingeloggter Nutzer in MariaDB
- Zwei Zusatzfeatures:
  - Gutscheincodes
  - Vorkonfigurierte Presets

## Hinweise fuer Abgabe

- SQL-Dump: `database/dump/projektdump.sql`
- Es werden nur relative Links verwendet (kein `http://localhost/...`, kein fuehrender `/`)
- Lokale Bootstrap-Dateien: `frontend/public/assets/vendor/bootstrap/`
