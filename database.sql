-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3307
-- Généré le : sam. 12 sep. 2026 à 06:59
-- Version du serveur : 10.11.8-MariaDB-1:10.11.8+maria~ubu2204
-- Version de PHP : 8.5.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vitegourmand`
--
CREATE DATABASE IF NOT EXISTS `vitegourmand` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vitegourmand`;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','employee','admin') NOT NULL DEFAULT 'user',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gsm` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

-- --------------------------------------------------------

--
-- Structure de la table `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `theme` varchar(100) NOT NULL,
  `min_people` int(11) NOT NULL,
  `base_price` decimal(10,2) NOT NULL,
  `conditions` text DEFAULT NULL,
  `available_stock` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_theme` (`theme`),
  ADD KEY `idx_min_people` (`min_people`),
  ADD KEY `idx_base_price` (`base_price`);

-- --------------------------------------------------------

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure de la table `menu_items` (plats possibles dans un menu)
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('entrée','plat','dessert') NOT NULL,
  `allergens` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`),
  ADD KEY `idx_category` (`category`);

-- --------------------------------------------------------

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `number_of_people` int(11) NOT NULL,
  `delivery_date` date NOT NULL,
  `delivery_time` time NOT NULL,
  `delivery_address` varchar(255) NOT NULL,
  `status` enum('pending','accepted','preparing','delivering','delivered','awaiting_return','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `menu_id` (`menu_id`),
  ADD KEY `idx_delivery_date` (`delivery_date`),
  ADD KEY `idx_status` (`status`);

-- --------------------------------------------------------

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Données de test pour la table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `first_name`, `last_name`, `phone`, `gsm`, `address`, `created_at`, `updated_at`) VALUES
(1, 'admin@viteetgourmand.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'Istrator', '0123456789', '0612345678', '123 Rue de la Paix', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(2, 'employee@viteetgourmand.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee', 'Employé', 'Modèle', '0123456789', '0612345678', '456 Avenue des Champs', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(3, 'user@viteetgourmand.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'Utilisateur', 'Modèle', '0123456789', '0612345678', '789 Boulevard Saint-Michel', '2026-09-12 06:59:00', '2026-09-12 06:59:00');

-- --------------------------------------------------------

--
-- Données de test pour la table `menus`
--

INSERT INTO `menus` (`id`, `title`, `description`, `theme`, `min_people`, `base_price`, `conditions`, `available_stock`, `created_at`, `updated_at`) VALUES
(1, 'Menu de Noël', 'Un délicieux menu pour les fêtes de fin d\'année', 'Noël', 4, 35.50, 'À commander 48h à l\'avance', 10, '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(2, 'Menu de Pâques', 'Menu spécial pour célébrer Pâques', 'Pâques', 4, 32.00, 'À commander 24h à l\'avance', 8, '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(3, 'Menu Classique', 'Notre menu classique disponible toute l\'année', 'classique', 2, 25.00, 'Aucune condition particulière', 15, '2026-09-12 06:59:00', '2026-09-12 06:59:00');

-- --------------------------------------------------------

--
-- Données de test pour la table `menu_items`
--

INSERT INTO `menu_items` (`id`, `menu_id`, `name`, `description`, `category`, `allergens`, `created_at`, `updated_at`) VALUES
(1, 1, 'Foie gras maison', 'Foie gras de canard accompagné de son confit d\'oignon', 'entrée', 'Gluten, Lactose', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(2, 1, 'Dinde aux marrons', 'Dinde rôtie farcie aux marrons et aux champignons', 'plat', 'Gluten, Lactose', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(3, 1, 'Bûche de Noël', 'Bûche traditionnelle au chocolat et à la crème au beurre', 'dessert', 'Gluten, Lactose, Œufs', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(4, 2, 'Œufs mimosa', 'Œufs durs mayonnaise et ciboulette', 'entrée', 'Œufs', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(5, 2, 'Agneau pascal', 'Gigot d\'agneau rôti aux herbes de Provence', 'plat', '', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(6, 2, 'Nid de Pâques', 'Nid en chocolat garnis d\'œufs en sucre', 'dessert', 'Gluten, Lactose, Œufs', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(7, 3, 'Salade composée', 'Salade verte, tomates, concombre, maïs et thon', 'entrée', 'Poisson', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(8, 3, 'Steak frites', 'Steak haché accompagné de frites maison', 'plat', 'Gluten', '2026-09-12 06:59:00', '2026-09-12 06:59:00'),
(9, 3, 'Tarte aux pommes', 'Tarte traditionnelle aux pommes et à la cannelle', 'dessert', 'Gluten, Lactose', '2026-09-12 06:59:00', '2026-09-12 06:59:00');

-- --------------------------------------------------------

--
-- Données de test pour la table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `menu_id`, `number_of_people`, `delivery_date`, `delivery_time`, `delivery_address`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 4, '2026-12-25', '19:00:00', '123 Rue de la Paix, 33000 Bordeaux', 'pending', '2026-09-12 06:59:00', '2026-09-12 06:59:00');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
