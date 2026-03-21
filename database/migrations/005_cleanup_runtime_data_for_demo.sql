USE webtec;

-- Löscht Laufzeitdaten aus der Anwendung (Accounts + gespeicherte Konfigurationen).
-- Stammdaten aus Seed (sizes, ingredients, toppings, presets, coupons) bleiben erhalten.
DELETE FROM users;

-- AUTO_INCREMENT zurücksetzen, damit die Demo mit sauberer ID-Reihenfolge startet.
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE configurations AUTO_INCREMENT = 1;
