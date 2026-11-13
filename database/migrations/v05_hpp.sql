-- ============================================================
-- Migrasi 005: Skema kalkulator HPP
-- ============================================================

CREATE TABLE IF NOT EXISTS `hpp_ingredients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `base_unit` ENUM('g', 'ml', 'pcs') NOT NULL,
    `pack_size` DECIMAL(12,3) NOT NULL,
    `pack_price` INT NOT NULL,
    `note` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_hpp_ing_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hpp_recipes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `variant` VARCHAR(40) NOT NULL DEFAULT '',
    `yield_jars` SMALLINT NOT NULL DEFAULT 1,
    `jar_ml` SMALLINT NULL,
    `round_step` INT NOT NULL DEFAULT 1000,
    `note` VARCHAR(255) NULL,
    `product_item_id` INT NULL,
    `total_cost` INT NOT NULL DEFAULT 0,
    `hpp_recipe` INT NOT NULL DEFAULT 0,
    `hpp_per_jar` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_hpp_recipe_item` (`product_item_id`),
    CONSTRAINT `fk_hpp_recipe_item`
        FOREIGN KEY (`product_item_id`) REFERENCES `product_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hpp_recipe_lines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipe_id` INT NOT NULL,
    `ingredient_id` INT NULL,
    `custom_name` VARCHAR(120) NULL,
    `qty` DECIMAL(12,3) NOT NULL,
    `unit_cost` DECIMAL(14,6) NOT NULL,
    `sort_order` SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT `fk_hpp_line_recipe`
        FOREIGN KEY (`recipe_id`) REFERENCES `hpp_recipes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_hpp_line_ing`
        FOREIGN KEY (`ingredient_id`) REFERENCES `hpp_ingredients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hpp_recipe_costs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `recipe_id` INT NOT NULL,
    `label` VARCHAR(80) NOT NULL,
    `calc_type` ENUM('pct_bahan', 'per_jar', 'flat') NOT NULL,
    `amount` DECIMAL(12,4) NOT NULL,
    `sort_order` SMALLINT NOT NULL DEFAULT 0,
    CONSTRAINT `fk_hpp_cost_recipe`
        FOREIGN KEY (`recipe_id`) REFERENCES `hpp_recipes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('hpp_default_costs', '[{"label":"Tenaga kerja","calc_type":"pct_bahan","amount":5},{"label":"Listrik, gas, dan air","calc_type":"pct_bahan","amount":5},{"label":"Penyusutan","calc_type":"pct_bahan","amount":5},{"label":"Packing","calc_type":"per_jar","amount":4000}]'),
    ('hpp_margins', '50,75,100,200'),
    ('hpp_round_step', '1000');
