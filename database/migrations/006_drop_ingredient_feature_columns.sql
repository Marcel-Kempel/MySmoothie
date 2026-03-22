USE webtec;

ALTER TABLE ingredients
  DROP COLUMN IF EXISTS is_vegan,
  DROP COLUMN IF EXISTS is_lactose_free,
  DROP COLUMN IF EXISTS is_high_protein,
  DROP COLUMN IF EXISTS is_low_sugar;
