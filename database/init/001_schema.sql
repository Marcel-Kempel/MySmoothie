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
