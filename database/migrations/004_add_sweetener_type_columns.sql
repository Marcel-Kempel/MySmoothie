USE webtec;

ALTER TABLE presets
  ADD COLUMN IF NOT EXISTS sweetener_type ENUM('none', 'honey', 'agave') NOT NULL DEFAULT 'none'
  AFTER temperature;

ALTER TABLE configurations
  ADD COLUMN IF NOT EXISTS sweetener_type ENUM('none', 'honey', 'agave') NOT NULL DEFAULT 'none'
  AFTER temperature;
