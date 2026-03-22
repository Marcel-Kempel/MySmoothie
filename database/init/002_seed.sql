SET NAMES utf8mb4;
USE webtec;

INSERT INTO sizes (id, name, ml, base_price) VALUES
  (1, 'Small', 300, 4.50),
  (2, 'Medium', 500, 5.50),
  (3, 'Large', 700, 6.50);

INSERT INTO ingredients (
  id, name, category, price, image_url, is_active
) VALUES
  (1, 'Banane', 'fruit', 0.50, 'assets/images/ingredients/banane.jpeg', 1),
  (2, 'Erdbeere', 'fruit', 0.75, 'assets/images/ingredients/erdbeere.jpeg', 1),
  (3, 'Mango', 'fruit', 0.80, 'assets/images/ingredients/mango.jpeg', 1),
  (4, 'Ananas', 'fruit', 0.70, 'assets/images/ingredients/ananas.jpeg', 1),
  (5, 'Blaubeere', 'fruit', 0.90, 'assets/images/ingredients/blaubeere.jpeg', 1),
  (6, 'Kiwi', 'fruit', 0.60, 'assets/images/ingredients/kiwi.jpeg', 1),
  (7, 'Orange', 'fruit', 0.65, 'assets/images/ingredients/orange.jpeg', 1),
  (8, 'Apfel', 'fruit', 0.45, 'assets/images/ingredients/apfel.jpeg', 1),
  (9, 'Himbeere', 'fruit', 0.85, 'assets/images/ingredients/himbeere.jpeg', 1),

  (10, 'Spinat', 'vegetable', 0.40, 'assets/images/ingredients/spinat.jpeg', 1),
  (11, 'Grünkohl', 'vegetable', 0.50, 'assets/images/ingredients/gruenkohl.jpeg', 1),
  (12, 'Karotte', 'vegetable', 0.35, 'assets/images/ingredients/karotte.jpeg', 1),
  (13, 'Rote Beete', 'vegetable', 0.55, 'assets/images/ingredients/rote-beete.jpeg', 1),
  (14, 'Gurke', 'vegetable', 0.30, 'assets/images/ingredients/gurke.jpeg', 1),
  (15, 'Sellerie', 'vegetable', 0.40, 'assets/images/ingredients/sellerie.jpeg', 1),
  (16, 'Ingwer', 'vegetable', 0.45, 'assets/images/ingredients/ingwer.jpeg', 1),

  (17, 'Whey Vanille', 'protein', 1.50, 'assets/images/ingredients/whey-vanille.jpeg', 1),
  (18, 'Whey Schoko', 'protein', 1.50, 'assets/images/ingredients/whey-schoko.jpeg', 1),
  (19, 'Veganes Protein', 'protein', 1.70, 'assets/images/ingredients/veganes-protein.jpeg', 1),
  (20, 'Skyr', 'protein', 1.20, 'assets/images/ingredients/skyr.jpeg', 1),
  (21, 'Sojaprotein', 'protein', 1.40, 'assets/images/ingredients/sojaprotein.jpeg', 1),
  (22, 'Hanfprotein', 'protein', 1.65, 'assets/images/ingredients/hanfprotein.jpeg', 1),
  (23, 'Erbsenprotein', 'protein', 1.45, 'assets/images/ingredients/erbsenprotein.jpeg', 1),
  (24, 'Reisprotein', 'protein', 1.55, 'assets/images/ingredients/reisprotein.jpeg', 1);

INSERT INTO toppings (id, name, price) VALUES
  (1, 'Chiasamen', 0.50),
  (2, 'Granola', 0.50),
  (3, 'Nüsse', 0.60),
  (4, 'Kokosflakes', 0.50);

INSERT INTO presets (id, name, description, size_id, sweetness, consistency, temperature, sweetener_type) VALUES
  (1, 'Green Power', 'Grüne Mischung mit frischer Basis.', 2, 'medium', 'standard', 'chilled', 'none'),
  (2, 'Berry Protein', 'Beeren + Protein für Trainingstage.', 3, 'low', 'creamy', 'extra_cold', 'honey'),
  (3, 'Tropical Vegan', 'Tropisch, vegan und ausgewogen.', 2, 'medium', 'standard', 'chilled', 'agave'),
  (4, 'Detox Fresh', 'Leicht und frisch für zwischendurch.', 1, 'none', 'liquid', 'chilled', 'none');

INSERT INTO preset_ingredients (preset_id, ingredient_id) VALUES
  (1, 10), (1, 11), (1, 6), (1, 8), (1, 1),
  (2, 2), (2, 5), (2, 9), (2, 17), (2, 20),
  (3, 3), (3, 4), (3, 1), (3, 19), (3, 14),
  (4, 14), (4, 15), (4, 10), (4, 8), (4, 16);

INSERT INTO coupons (code, discount_type, discount_value, is_active, valid_until) VALUES
  ('FIT10', 'percent', 10.00, 1, NULL),
  ('WELCOME5', 'fixed', 5.00, 1, '2026-12-31'),
  ('SPRING15', 'percent', 15.00, 1, '2026-06-30'),
  ('WINTER20', 'percent', 20.00, 1, '2026-01-15'),
  ('INACTIVE5', 'fixed', 5.00, 0, NULL);

