-- MySmoothie SQL Dump
-- Generated for project hand-in

SET NAMES utf8mb4;
USE webtec;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS configuration_toppings;
DROP TABLE IF EXISTS configuration_ingredients;
DROP TABLE IF EXISTS configurations;
DROP TABLE IF EXISTS preset_ingredients;
DROP TABLE IF EXISTS presets;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS toppings;
DROP TABLE IF EXISTS ingredients;
DROP TABLE IF EXISTS sizes;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  address VARCHAR(255) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sizes (
  id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  ml SMALLINT UNSIGNED NOT NULL,
  base_price DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ingredients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  category ENUM('fruit', 'vegetable', 'protein') NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  image_url VARCHAR(500) NOT NULL,
  is_vegan TINYINT(1) NOT NULL DEFAULT 0,
  is_lactose_free TINYINT(1) NOT NULL DEFAULT 0,
  is_high_protein TINYINT(1) NOT NULL DEFAULT 0,
  is_low_sugar TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ingredients_category (category),
  INDEX idx_ingredients_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE toppings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  price DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons (
  code VARCHAR(50) PRIMARY KEY,
  discount_type ENUM('percent', 'fixed') NOT NULL,
  discount_value DECIMAL(10,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  valid_until DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE presets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  description VARCHAR(255) NOT NULL,
  size_id TINYINT UNSIGNED NOT NULL,
  sweetness ENUM('none', 'low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  consistency ENUM('liquid', 'standard', 'creamy', 'extra_creamy') NOT NULL DEFAULT 'standard',
  temperature ENUM('chilled', 'extra_cold', 'frozen') NOT NULL DEFAULT 'chilled',
  sweetener_type ENUM('none', 'honey', 'agave') NOT NULL DEFAULT 'none',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_presets_size FOREIGN KEY (size_id) REFERENCES sizes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE preset_ingredients (
  preset_id INT UNSIGNED NOT NULL,
  ingredient_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (preset_id, ingredient_id),
  CONSTRAINT fk_preset_ingredients_preset FOREIGN KEY (preset_id) REFERENCES presets(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_preset_ingredients_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configurations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  size_id TINYINT UNSIGNED NOT NULL,
  sweetness ENUM('none', 'low', 'medium', 'high') NOT NULL,
  consistency ENUM('liquid', 'standard', 'creamy', 'extra_creamy') NOT NULL,
  temperature ENUM('chilled', 'extra_cold', 'frozen') NOT NULL,
  sweetener_type ENUM('none', 'honey', 'agave') NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_price DECIMAL(10,2) NOT NULL,
  coupon_code VARCHAR(50) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_configurations_user FOREIGN KEY (user_id) REFERENCES users(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_configurations_size FOREIGN KEY (size_id) REFERENCES sizes(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_configurations_coupon FOREIGN KEY (coupon_code) REFERENCES coupons(code)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_configurations_user_created (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuration_ingredients (
  configuration_id BIGINT UNSIGNED NOT NULL,
  ingredient_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (configuration_id, ingredient_id),
  CONSTRAINT fk_configuration_ingredients_configuration FOREIGN KEY (configuration_id) REFERENCES configurations(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_configuration_ingredients_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE configuration_toppings (
  configuration_id BIGINT UNSIGNED NOT NULL,
  topping_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (configuration_id, topping_id),
  CONSTRAINT fk_configuration_toppings_configuration FOREIGN KEY (configuration_id) REFERENCES configurations(id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_configuration_toppings_topping FOREIGN KEY (topping_id) REFERENCES toppings(id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO sizes (id, name, ml, base_price) VALUES
  (1, 'Small', 300, 4.50),
  (2, 'Medium', 500, 5.50),
  (3, 'Large', 700, 6.50);

INSERT INTO ingredients (
  id, name, category, price, image_url,
  is_vegan, is_lactose_free, is_high_protein, is_low_sugar, is_active
) VALUES
  (1, 'Banane', 'fruit', 0.50, 'assets/images/ingredients/banane.jpeg', 1, 1, 0, 0, 1),
  (2, 'Erdbeere', 'fruit', 0.75, 'assets/images/ingredients/erdbeere.jpeg', 1, 1, 0, 0, 1),
  (3, 'Mango', 'fruit', 0.80, 'assets/images/ingredients/mango.jpeg', 1, 1, 0, 0, 1),
  (4, 'Ananas', 'fruit', 0.70, 'assets/images/ingredients/ananas.jpeg', 1, 1, 0, 0, 1),
  (5, 'Blaubeere', 'fruit', 0.90, 'assets/images/ingredients/blaubeere.jpeg', 1, 1, 0, 1, 1),
  (6, 'Kiwi', 'fruit', 0.60, 'assets/images/ingredients/kiwi.jpeg', 1, 1, 0, 0, 1),
  (7, 'Orange', 'fruit', 0.65, 'assets/images/ingredients/orange.jpeg', 1, 1, 0, 0, 1),
  (8, 'Apfel', 'fruit', 0.45, 'assets/images/ingredients/apfel.jpeg', 1, 1, 0, 0, 1),
  (9, 'Himbeere', 'fruit', 0.85, 'assets/images/ingredients/himbeere.jpeg', 1, 1, 0, 1, 1),

  (10, 'Spinat', 'vegetable', 0.40, 'assets/images/ingredients/spinat.jpeg', 1, 1, 0, 1, 1),
  (11, 'Grünkohl', 'vegetable', 0.50, 'assets/images/ingredients/gruenkohl.jpeg', 1, 1, 0, 1, 1),
  (12, 'Karotte', 'vegetable', 0.35, 'assets/images/ingredients/karotte.jpeg', 1, 1, 0, 0, 1),
  (13, 'Rote Beete', 'vegetable', 0.55, 'assets/images/ingredients/rote-beete.jpeg', 1, 1, 0, 1, 1),
  (14, 'Gurke', 'vegetable', 0.30, 'assets/images/ingredients/gurke.jpeg', 1, 1, 0, 1, 1),
  (15, 'Sellerie', 'vegetable', 0.40, 'assets/images/ingredients/sellerie.jpeg', 1, 1, 0, 1, 1),
  (16, 'Ingwer', 'vegetable', 0.45, 'assets/images/ingredients/ingwer.jpeg', 1, 1, 0, 1, 1),

  (17, 'Whey Vanille', 'protein', 1.50, 'assets/images/ingredients/whey-vanille.jpeg', 0, 0, 1, 1, 1),
  (18, 'Whey Schoko', 'protein', 1.50, 'assets/images/ingredients/whey-schoko.jpeg', 0, 0, 1, 1, 1),
  (19, 'Veganes Protein', 'protein', 1.70, 'assets/images/ingredients/veganes-protein.jpeg', 1, 1, 1, 1, 1),
  (20, 'Skyr', 'protein', 1.20, 'assets/images/ingredients/skyr.jpeg', 0, 0, 1, 1, 1),
  (21, 'Sojaprotein', 'protein', 1.40, 'assets/images/ingredients/sojaprotein.jpeg', 1, 1, 1, 1, 1),
  (22, 'Hanfprotein', 'protein', 1.65, 'assets/images/ingredients/hanfprotein.jpeg', 1, 1, 1, 1, 1),
  (23, 'Erbsenprotein', 'protein', 1.45, 'assets/images/ingredients/erbsenprotein.jpeg', 1, 1, 1, 1, 1),
  (24, 'Reisprotein', 'protein', 1.55, 'assets/images/ingredients/reisprotein.jpeg', 1, 1, 1, 1, 1);

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


