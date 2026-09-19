CREATE DATABASE IF NOT EXISTS `vitegourmand`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `vitegourmand`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Résultat de password_hash(), jamais le mot de passe en clair',
    `role` ENUM('user', 'employee', 'admin') NOT NULL DEFAULT 'user',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `gsm` VARCHAR(20) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `reset_token_hash` CHAR(64) NULL COMMENT 'SHA-256 du jeton de réinitialisation',
    `reset_token_expires_at` DATETIME NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_active` (`role`, `is_active`),
    KEY `idx_users_reset_token` (`reset_token_hash`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT NOT NULL,
    `theme` VARCHAR(80) NOT NULL COMMENT 'Exemples : Noël, Pâques, classique, événement',
    `dietary_regime` ENUM('classic', 'vegetarian', 'vegan', 'other') NOT NULL DEFAULT 'classic',
    `min_people` SMALLINT UNSIGNED NOT NULL,
    `base_price` DECIMAL(10,2) NOT NULL COMMENT 'Prix TTC pour le nombre minimal de personnes',
    `conditions` TEXT NOT NULL,
    `available_stock` SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Nombre de commandes encore possibles',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_menus_catalogue` (`is_active`, `available_stock`),
    KEY `idx_menus_theme` (`theme`),
    KEY `idx_menus_regime` (`dietary_regime`),
    KEY `idx_menus_price` (`base_price`),
    KEY `idx_menus_min_people` (`min_people`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `menu_images` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `menu_id` INT UNSIGNED NOT NULL,
    `path` VARCHAR(255) NOT NULL COMMENT 'Chemin relatif sous public/uploads',
    `alt_text` VARCHAR(150) NOT NULL,
    `position` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_menu_images_position` (`menu_id`, `position`),
    CONSTRAINT `fk_menu_images_menu`
        FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `dishes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_dishes_name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `menu_dishes` (
    `menu_id` INT UNSIGNED NOT NULL,
    `dish_id` INT UNSIGNED NOT NULL,
    `category` ENUM('starter', 'main', 'dessert') NOT NULL,
    `position` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`menu_id`, `dish_id`),
    KEY `idx_menu_dishes_category` (`menu_id`, `category`),
    CONSTRAINT `fk_menu_dishes_menu`
        FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_menu_dishes_dish`
        FOREIGN KEY (`dish_id`) REFERENCES `dishes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `allergens` (
    `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(80) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_allergens_name` (`name`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `dish_allergens` (
    `dish_id` INT UNSIGNED NOT NULL,
    `allergen_id` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`dish_id`, `allergen_id`),
    CONSTRAINT `fk_dish_allergens_dish`
        FOREIGN KEY (`dish_id`) REFERENCES `dishes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dish_allergens_allergen`
        FOREIGN KEY (`allergen_id`) REFERENCES `allergens` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ================================================================
-- COMMANDES ET SUIVI (MariaDB)
-- Les montants sont mémorisés : ils restent exacts si un menu évolue.
-- ================================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(30) NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `menu_id` INT UNSIGNED NOT NULL,
    `number_of_people` SMALLINT UNSIGNED NOT NULL,
    `order_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `delivery_date` DATE NOT NULL,
    `delivery_time` TIME NOT NULL,
    `delivery_address` VARCHAR(255) NOT NULL,
    `delivery_city` VARCHAR(100) NULL,
    `delivery_postal_code` VARCHAR(10) NULL,
    `delivery_distance_km` DECIMAL(7,2) NULL,
    `delivery_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `menu_price` DECIMAL(10,2) NOT NULL,
    `discount_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Pourcentage, par exemple 10.00',
    `total_price` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'accepted', 'preparing', 'delivering', 'delivered', 'awaiting_return', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `equipment_loaned` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 si du matériel a été prêté pour cette commande',
    `cancellation_reason` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_number` (`order_number`),
    KEY `idx_orders_user_date` (`user_id`, `delivery_date`),
    KEY `idx_orders_status_date` (`status`, `delivery_date`),
    KEY `idx_orders_menu` (`menu_id`),
    CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_orders_menu`
        FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `equipment_loaned` TINYINT(1) NOT NULL DEFAULT 0
    AFTER `status`;

CREATE TABLE IF NOT EXISTS `order_status_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'accepted', 'preparing', 'delivering', 'delivered', 'awaiting_return', 'completed', 'cancelled') NOT NULL,
    `changed_by` INT UNSIGNED NULL COMMENT 'Utilisateur ayant effectué le changement',
    `changed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_history_order_date` (`order_id`, `changed_at`),
    CONSTRAINT `fk_history_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_history_user`
        FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `opening_hours` (
    `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '1 = lundi, 7 = dimanche',
    `is_open` TINYINT(1) NOT NULL DEFAULT 1,
    `opening_time` TIME NULL,
    `closing_time` TIME NULL,
    PRIMARY KEY (`day_of_week`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'processed', 'closed') NOT NULL DEFAULT 'new',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_status_date` (`status`, `created_at`)
) ENGINE=InnoDB;

-- ================================================================
-- Données de départ
-- ================================================================
INSERT INTO `menus` (`id`, `title`, `description`, `theme`, `dietary_regime`, `min_people`, `base_price`, `conditions`, `available_stock`)
VALUES
    (1, 'Menu de Noël', 'Une formule festive pour les repas de fin d’année.', 'Noël', 'classic', 4, 35.50, 'Commande au minimum 48 heures à l’avance.', 10),
    (2, 'Menu de Pâques', 'Une formule de saison pour partager un repas convivial.', 'Pâques', 'classic', 4, 32.00, 'Commande au minimum 24 heures à l’avance.', 8),
    (3, 'Menu végétarien', 'Une formule végétarienne composée de produits de saison.', 'Classique', 'vegetarian', 2, 25.00, 'Disponible toute l’année selon le stock.', 15)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `dishes` (`id`, `name`, `description`) VALUES
    (1, 'Foie gras maison', 'Foie gras de canard et confit d’oignon.'),
    (2, 'Dinde aux marrons', 'Dinde rôtie aux marrons et champignons.'),
    (3, 'Bûche de Noël', 'Bûche traditionnelle au chocolat.'),
    (4, 'Œufs mimosa', 'Œufs durs, mayonnaise et ciboulette.'),
    (5, 'Agneau pascal', 'Gigot d’agneau rôti aux herbes.'),
    (6, 'Nid de Pâques', 'Dessert chocolaté de saison.'),
    (7, 'Salade composée', 'Légumes de saison et vinaigrette maison.'),
    (8, 'Lasagnes végétariennes', 'Légumes grillés et béchamel végétarienne.'),
    (9, 'Tarte aux pommes', 'Tarte aux pommes et cannelle.')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`), `updated_at` = CURRENT_TIMESTAMP;

INSERT INTO `menu_dishes` (`menu_id`, `dish_id`, `category`, `position`) VALUES
    (1, 1, 'starter', 1), (1, 2, 'main', 1), (1, 3, 'dessert', 1),
    (2, 4, 'starter', 1), (2, 5, 'main', 1), (2, 6, 'dessert', 1),
    (3, 7, 'starter', 1), (3, 8, 'main', 1), (3, 9, 'dessert', 1)
ON DUPLICATE KEY UPDATE `position` = VALUES(`position`);

INSERT INTO `allergens` (`id`, `name`) VALUES
    (1, 'Gluten'), (2, 'Lactose'), (3, 'Œufs'), (4, 'Poisson')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `dish_allergens` (`dish_id`, `allergen_id`) VALUES
    (1, 1), (1, 2), (2, 1), (2, 2), (3, 1), (3, 2), (3, 3),
    (4, 3), (6, 1), (6, 2), (6, 3), (7, 4), (9, 1), (9, 2)
ON DUPLICATE KEY UPDATE `allergen_id` = VALUES(`allergen_id`);

INSERT INTO `opening_hours` (`day_of_week`, `is_open`, `opening_time`, `closing_time`) VALUES
    (1, 1, '09:00:00', '18:00:00'), (2, 1, '09:00:00', '18:00:00'),
    (3, 1, '09:00:00', '18:00:00'), (4, 1, '09:00:00', '18:00:00'),
    (5, 1, '09:00:00', '18:00:00'), (6, 1, '09:00:00', '18:00:00'),
    (7, 1, '09:00:00', '12:00:00')
ON DUPLICATE KEY UPDATE `is_open` = VALUES(`is_open`), `opening_time` = VALUES(`opening_time`), `closing_time` = VALUES(`closing_time`);

