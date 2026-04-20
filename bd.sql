-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 15 avr. 2026 à 14:55
-- Version du serveur : 8.4.7
-- Version de PHP : 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `projetkbe`
--

DELIMITER $$
--
-- Procédures
--
DROP PROCEDURE IF EXISTS `sp_DeleteUserAccount`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_DeleteUserAccount` (IN `p_UserId` INT)   proc: BEGIN
    DECLARE v_user_exists INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_UserId IS NULL OR p_UserId <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identifiant utilisateur invalide.';
    END IF;

    START TRANSACTION;

    SELECT COUNT(*)
    INTO v_user_exists
    FROM Users
    WHERE UserId = p_UserId
    FOR UPDATE;

    IF v_user_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Compte introuvable.';
    END IF;

    DELETE FROM OrderItems
    WHERE OrderId IN (
        SELECT OrderId
        FROM Orders
        WHERE UserId = p_UserId
    );

    DELETE FROM Orders
    WHERE UserId = p_UserId;

    DELETE FROM Carts
    WHERE UserId = p_UserId;

    DELETE FROM Reviews
    WHERE UserId = p_UserId;

    DELETE FROM Inventory
    WHERE UserId = p_UserId;

    DELETE FROM Users
    WHERE UserId = p_UserId;

    IF ROW_COUNT() <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suppression du compte echouee.';
    END IF;

    COMMIT;
END$$

DROP PROCEDURE IF EXISTS `sp_GetUserByAlias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_GetUserByAlias` (IN `p_Alias` VARCHAR(30))   BEGIN
    DECLARE v_alias VARCHAR(30);
    SET v_alias = TRIM(CONVERT(p_Alias USING utf8mb4)) COLLATE utf8mb4_unicode_ci;

    SELECT UserId, Alias, Password, Role, Gold, Silver, Bronze, IsBanned
    FROM Users
    WHERE TRIM(Alias) COLLATE utf8mb4_unicode_ci = v_alias COLLATE utf8mb4_unicode_ci
    LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS `sp_RegisterUser`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_RegisterUser` (IN `p_Alias` VARCHAR(30), IN `p_Password` VARCHAR(255))   BEGIN
    DECLARE v_alias VARCHAR(30);

    SET v_alias = TRIM(CONVERT(p_Alias USING utf8mb4)) COLLATE utf8mb4_unicode_ci;

    IF v_alias IS NULL OR v_alias = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alias invalide.';
    END IF;

    IF p_Password IS NULL OR TRIM(p_Password) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mot de passe invalide.';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM Users
        WHERE TRIM(Alias) COLLATE utf8mb4_unicode_ci = v_alias COLLATE utf8mb4_unicode_ci
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet alias est deja utilise.';
    ELSE
        INSERT INTO Users (Alias, Password, Role, Gold, Silver, Bronze)
        VALUES (v_alias, p_Password, 'Player', 1000, 1000, 1000);
    END IF;
END$$

DELIMITER ;

SET FOREIGN_KEY_CHECKS = 0;
SET @OLD_DEFAULT_STORAGE_ENGINE = @@default_storage_engine;
SET default_storage_engine = InnoDB;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `UserId` int NOT NULL AUTO_INCREMENT,
  `Alias` varchar(30) NOT NULL,
  `FullName` varchar(80) DEFAULT NULL,
  `Email` varchar(190) DEFAULT NULL,
  `AvatarUrl` varchar(255) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` varchar(20) NOT NULL,
  `Gold` int NOT NULL DEFAULT '1000',
  `Silver` int NOT NULL DEFAULT '1000',
  `Bronze` int NOT NULL DEFAULT '1000',
  `ProfileIsDeleted` tinyint(1) NOT NULL DEFAULT '0',
  `ProfileDeletedAt` datetime DEFAULT NULL,
  `IsBanned` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserId`),
  UNIQUE KEY `UQ_Users_Alias` (`Alias`),
  UNIQUE KEY `UQ_Users_Email` (`Email`)
) ;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`UserId`, `Alias`, `FullName`, `Email`, `AvatarUrl`, `Password`, `Role`, `Gold`, `Silver`, `Bronze`, `ProfileIsDeleted`, `ProfileDeletedAt`, `IsBanned`) VALUES
(3, 'test123', 'Testual', 'testual@yopmail.com', 'https://images.twinkl.co.uk/tw1n/image/private/t_630/u/ux/screenshot-2023-04-18-at-09.43.39_ver_1.png', '$2y$10$0X4W/We1RjcRARt/J0fvmuttALMkud5Y07M6KSVbKOoouQOsd7dFy', 'Player', 13575, 61813, 85434, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `itemtypes`
--

DROP TABLE IF EXISTS `itemtypes`;
CREATE TABLE IF NOT EXISTS `itemtypes` (
  `ItemTypeId` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`ItemTypeId`),
  UNIQUE KEY `UQ_ItemTypes_Name` (`Name`)
) ;

--
-- Déchargement des données de la table `itemtypes`
--

INSERT INTO `itemtypes` (`ItemTypeId`, `Name`) VALUES
(2, 'Armor'),
(4, 'MagicSpell'),
(3, 'Potion'),
(1, 'Weapon');

-- --------------------------------------------------------

--
-- Structure de la table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `ItemId` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(80) NOT NULL,
  `Description` text,
  `PriceGold` int NOT NULL DEFAULT '0',
  `PriceSilver` int NOT NULL DEFAULT '0',
  `PriceBronze` int NOT NULL DEFAULT '0',
  `Stock` int NOT NULL DEFAULT '0',
  `ItemTypeId` int NOT NULL,
  `Rarity` enum('Commun','Rare','Épique','Légendaire','Mythique') NOT NULL DEFAULT 'Commun',
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ItemId`),
  UNIQUE KEY `UQ_Items_Name` (`Name`),
  KEY `FK_Items_ItemTypes` (`ItemTypeId`)
) ;

--
-- Déchargement des données de la table `items`
--

INSERT INTO `items` (`ItemId`, `Name`, `Description`, `PriceGold`, `PriceSilver`, `PriceBronze`, `Stock`, `ItemTypeId`, `Rarity`, `IsActive`) VALUES
(1, 'Basic Sword', 'Épée simple pour débutant.', 50, 20, 10, 5, 1, 'Commun', 1),
(2, 'Knight Blade', 'Épée robuste de chevalier.', 120, 40, 15, 4, 1, 'Rare', 1),
(3, 'Dagger', 'Petite lame rapide et légère.', 30, 10, 5, 15, 1, 'Commun', 1),
(4, 'War Axe', 'Hache lourde à fort impact.', 140, 60, 25, 4, 1, 'Rare', 1),
(5, 'Golden Sword', 'Épée rare avec une belle finition.', 300, 150, 50, 2, 1, 'Épique', 1),
(6, 'Hunter Bow', 'Arc précis pour les longues distances.', 100, 35, 12, 6, 1, 'Rare', 1),
(7, 'Iron Hammer', 'Marteau puissant mais lent.', 160, 70, 30, 3, 1, 'Épique', 1),
(8, 'Shadow Dagger', 'Dague noire très rapide.', 180, 80, 25, 4, 1, 'Épique', 1),
(9, 'Royal Spear', 'Lance équilibrée pour combattants avancés.', 220, 95, 35, 3, 1, 'Épique', 1),
(10, 'Dragon Slayer', 'Grande épée conçue pour les monstres.', 500, 250, 90, 1, 1, 'Légendaire', 1),
(11, 'Leather Armor', 'Armure légère en cuir.', 40, 15, 5, 10, 2, 'Commun', 1),
(12, 'Chainmail', 'Armure intermédiaire en mailles.', 90, 40, 15, 6, 2, 'Rare', 1),
(13, 'Steel Armor', 'Armure lourde en acier.', 150, 60, 20, 4, 2, 'Rare', 1),
(14, 'Golden Armor', 'Armure prestigieuse et résistante.', 400, 200, 80, 1, 2, 'Légendaire', 1),
(15, 'Mage Robe', 'Robe conçue pour les utilisateurs de magie.', 80, 30, 10, 7, 2, 'Rare', 1),
(16, 'Iron Shield Armor', 'Protection lourde pour tank.', 210, 90, 30, 1, 2, 'Épique', 1),
(17, 'Shadow Cloak', 'Cape défensive et discrète.', 170, 75, 25, 3, 2, 'Épique', 1),
(18, 'Paladin Armor', 'Armure sacrée de paladin.', 320, 140, 50, 2, 2, 'Légendaire', 1),
(19, 'Traveler Vest', 'Veste légère pour aventurier.', 35, 12, 4, 12, 2, 'Commun', 1),
(20, 'Dragon Scale Armor', 'Armure forgée avec des écailles rares.', 550, 260, 100, 1, 2, 'Mythique', 1),
(21, 'Small Health Potion', 'Restaure un peu de vie.', 10, 5, 2, 50, 3, 'Commun', 1),
(22, 'Medium Health Potion', 'Restaure une quantité moyenne de vie.', 20, 10, 5, 39, 3, 'Commun', 1),
(23, 'Big Health Potion', 'Restaure beaucoup de vie.', 35, 15, 8, 30, 3, 'Commun', 1),
(24, 'Mana Potion', 'Restaure du mana.', 15, 7, 3, 40, 3, 'Commun', 1),
(25, 'Strength Potion', 'Augmente la force temporairement.', 25, 12, 6, 19, 3, 'Rare', 1),
(26, 'Defense Potion', 'Augmente la défense temporairement.', 25, 12, 6, 20, 3, 'Rare', 1),
(27, 'Speed Potion', 'Augmente la vitesse temporairement.', 22, 11, 5, 20, 3, 'Rare', 1),
(28, 'Elixir of Life', 'Potion rare de soin supérieur.', 80, 35, 15, 10, 3, 'Rare', 1),
(29, 'Antidote', 'Supprime les effets de poison.', 8, 4, 1, 35, 3, 'Commun', 1),
(30, 'Mega Mana Potion', 'Restaure énormément de mana.', 40, 18, 9, 15, 3, 'Rare', 1),
(31, 'Fireball', 'Sort de feu basique.', 80, 30, 10, 10, 4, 'Rare', 1),
(32, 'Ice Spike', 'Projectile magique de glace.', 90, 35, 12, 10, 4, 'Rare', 1),
(33, 'Lightning Bolt', 'Éclair puissant.', 110, 50, 20, 8, 4, 'Épique', 1),
(34, 'Earthquake', 'Sort de terre à large impact.', 150, 70, 30, 5, 4, 'Épique', 1),
(35, 'Wind Slash', 'Lame de vent rapide.', 70, 25, 10, 12, 4, 'Rare', 1),
(36, 'Inferno Blast', 'Explosion de flammes avancée.', 220, 100, 40, 4, 4, 'Épique', 1),
(37, 'Frost Nova', 'Onde de glace autour du lanceur.', 170, 80, 30, 5, 4, 'Épique', 1),
(38, 'Thunder Storm', 'Tempête électrique destructrice.', 260, 120, 45, 3, 4, 'Légendaire', 1),
(39, 'Stone Wall', 'Mur protecteur de pierre.', 95, 40, 15, 6, 4, 'Rare', 1),
(40, 'Holy Light', 'Magie sacrée lumineuse.', 140, 60, 20, 7, 4, 'Épique', 1);

-- --------------------------------------------------------

--
-- Structure de la table `weaponproperties`
--

DROP TABLE IF EXISTS `weaponproperties`;
CREATE TABLE IF NOT EXISTS `weaponproperties` (
  `ItemId` int NOT NULL,
  `DamageMin` int NOT NULL,
  `DamageMax` int NOT NULL,
  `Durability` int NOT NULL DEFAULT '100',
  `RequiredLevel` int NOT NULL DEFAULT '1',
  `AttackSpeed` decimal(4,2) NOT NULL DEFAULT '1.00',
  PRIMARY KEY (`ItemId`)
) ;

--
-- Déchargement des données de la table `weaponproperties`
--

INSERT INTO `weaponproperties` (`ItemId`, `DamageMin`, `DamageMax`, `Durability`, `RequiredLevel`, `AttackSpeed`) VALUES
(1, 5, 10, 100, 1, 1.00),
(2, 12, 20, 120, 2, 0.90),
(3, 3, 8, 80, 1, 1.50),
(4, 15, 25, 110, 3, 0.70),
(5, 25, 40, 200, 5, 1.10),
(6, 10, 18, 90, 2, 1.20),
(7, 18, 30, 140, 3, 0.65),
(8, 14, 22, 85, 4, 1.70),
(9, 20, 32, 130, 4, 1.00),
(10, 35, 55, 250, 7, 0.85);

-- --------------------------------------------------------

--
-- Structure de la table `armorproperties`
--

DROP TABLE IF EXISTS `armorproperties`;
CREATE TABLE IF NOT EXISTS `armorproperties` (
  `ItemId` int NOT NULL,
  `Defense` int NOT NULL,
  `Durability` int NOT NULL DEFAULT '100',
  `RequiredLevel` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ItemId`)
) ;

--
-- Déchargement des données de la table `armorproperties`
--

INSERT INTO `armorproperties` (`ItemId`, `Defense`, `Durability`, `RequiredLevel`) VALUES
(11, 8, 100, 1),
(12, 15, 120, 2),
(13, 20, 150, 3),
(14, 35, 200, 5),
(15, 12, 90, 2),
(16, 24, 170, 4),
(17, 18, 110, 3),
(18, 30, 180, 5),
(19, 6, 70, 1),
(20, 40, 220, 7);

-- --------------------------------------------------------

--
-- Structure de la table `potionproperties`
--

DROP TABLE IF EXISTS `potionproperties`;
CREATE TABLE IF NOT EXISTS `potionproperties` (
  `ItemId` int NOT NULL,
  `EffectType` varchar(50) NOT NULL,
  `EffectValue` int NOT NULL,
  `DurationSeconds` int DEFAULT NULL,
  PRIMARY KEY (`ItemId`)
) ;

--
-- Déchargement des données de la table `potionproperties`
--

INSERT INTO `potionproperties` (`ItemId`, `EffectType`, `EffectValue`, `DurationSeconds`) VALUES
(21, 'Heal', 25, NULL),
(22, 'Heal', 50, NULL),
(23, 'Heal', 100, NULL),
(24, 'Mana', 40, NULL),
(25, 'Strength', 10, 30),
(26, 'Defense', 10, 30),
(27, 'Speed', 10, 25),
(28, 'Heal', 200, NULL),
(29, 'CurePoison', 1, NULL),
(30, 'Mana', 100, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `magicspellproperties`
--

DROP TABLE IF EXISTS `magicspellproperties`;
CREATE TABLE IF NOT EXISTS `magicspellproperties` (
  `ItemId` int NOT NULL,
  `SpellDamage` int NOT NULL DEFAULT '0',
  `ManaCost` int NOT NULL,
  `ElementType` varchar(30) NOT NULL,
  `RequiredLevel` int NOT NULL DEFAULT '1',
  `CooldownSeconds` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ItemId`)
) ;

--
-- Déchargement des données de la table `magicspellproperties`
--

INSERT INTO `magicspellproperties` (`ItemId`, `SpellDamage`, `ManaCost`, `ElementType`, `RequiredLevel`, `CooldownSeconds`) VALUES
(31, 30, 15, 'Fire', 1, 3),
(32, 35, 18, 'Ice', 2, 4),
(33, 45, 22, 'Lightning', 3, 5),
(34, 60, 30, 'Earth', 4, 6),
(35, 25, 12, 'Wind', 1, 2),
(36, 80, 40, 'Fire', 5, 8),
(37, 65, 32, 'Ice', 4, 7),
(38, 95, 50, 'Lightning', 6, 10),
(39, 10, 20, 'Earth', 3, 12),
(40, 50, 25, 'Holy', 3, 5);

-- --------------------------------------------------------

--
-- Structure de la table `carts`
--

DROP TABLE IF EXISTS `carts`;
CREATE TABLE IF NOT EXISTS `carts` (
  `CartId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CartId`),
  UNIQUE KEY `UQ_Carts_User` (`UserId`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `carts`
--

INSERT INTO `carts` (`CartId`, `UserId`, `CreatedAt`) VALUES
(8, 3, '2026-04-08 10:29:49');

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `OrderId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `OrderDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TotalGold` int NOT NULL DEFAULT '0',
  `TotalSilver` int NOT NULL DEFAULT '0',
  `TotalBronze` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`OrderId`),
  KEY `FK_Orders_Users` (`UserId`)
) ;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`OrderId`, `UserId`, `OrderDate`, `TotalGold`, `TotalSilver`, `TotalBronze`) VALUES
(3, 3, '2026-04-08 10:30:31', 50, 20, 10),
(4, 3, '2026-04-08 11:42:27', 400, 200, 80),
(5, 3, '2026-04-15 09:15:39', 120, 40, 15),
(6, 3, '2026-04-15 09:36:42', 80, 30, 10),
(7, 3, '2026-04-15 09:37:11', 210, 90, 30),
(8, 3, '2026-04-15 09:38:56', 210, 90, 30),
(9, 3, '2026-04-15 09:39:24', 20, 10, 5),
(10, 3, '2026-04-15 09:39:44', 25, 12, 6),
(11, 3, '2026-04-15 10:18:09', 170, 75, 25);

-- --------------------------------------------------------

--
-- Structure de la table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `InventoryId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `ItemId` int NOT NULL,
  `Quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`InventoryId`),
  UNIQUE KEY `UQ_Inventory_User_Item` (`UserId`,`ItemId`),
  KEY `FK_Inventory_Items` (`ItemId`)
) ;

--
-- Déchargement des données de la table `inventory`
--

INSERT INTO `inventory` (`InventoryId`, `UserId`, `ItemId`, `Quantity`) VALUES
(3, 3, 1, 1),
(4, 3, 14, 1),
(5, 3, 2, 1),
(6, 3, 15, 1),
(7, 3, 16, 2),
(9, 3, 22, 1),
(10, 3, 25, 1),
(11, 3, 17, 1);

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `ReviewId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `ItemId` int NOT NULL,
  `Rating` decimal(2,1) NOT NULL,
  `Comment` text,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReviewId`),
  UNIQUE KEY `UQ_Reviews_User_Item` (`UserId`,`ItemId`),
  KEY `FK_Reviews_Items` (`ItemId`)
) ;

--
-- Déchargement des données de la table `reviews`
--

INSERT INTO `reviews` (`ReviewId`, `UserId`, `ItemId`, `Rating`, `Comment`, `CreatedAt`) VALUES
(1, 3, 1, 3.0, NULL, '2026-04-08 11:40:54'),
(2, 3, 2, 1.0, NULL, '2026-04-15 09:16:36'),
(3, 3, 14, 1.5, NULL, '2026-04-15 09:16:40'),
(4, 3, 15, 2.5, NULL, '2026-04-15 09:36:48'),
(5, 3, 16, 4.5, NULL, '2026-04-15 09:37:24'),
(6, 3, 22, 1.5, NULL, '2026-04-15 09:39:31'),
(7, 3, 25, 3.5, NULL, '2026-04-15 09:50:57'),
(8, 3, 17, 2.5, NULL, '2026-04-15 10:25:23');

-- --------------------------------------------------------

--
-- Structure de la table `cartitems`
--

DROP TABLE IF EXISTS `cartitems`;
CREATE TABLE IF NOT EXISTS `cartitems` (
  `CartItemId` int NOT NULL AUTO_INCREMENT,
  `CartId` int NOT NULL,
  `ItemId` int NOT NULL,
  `Quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`CartItemId`),
  UNIQUE KEY `UQ_CartItems_Cart_Item` (`CartId`,`ItemId`),
  KEY `FK_CartItems_Items` (`ItemId`)
) ;

-- --------------------------------------------------------

--
-- Structure de la table `orderitems`
--

DROP TABLE IF EXISTS `orderitems`;
CREATE TABLE IF NOT EXISTS `orderitems` (
  `OrderItemId` int NOT NULL AUTO_INCREMENT,
  `OrderId` int NOT NULL,
  `ItemId` int NOT NULL,
  `Quantity` int NOT NULL DEFAULT '1',
  `PriceGold` int NOT NULL DEFAULT '0',
  `PriceSilver` int NOT NULL DEFAULT '0',
  `PriceBronze` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`OrderItemId`),
  KEY `FK_OrderItems_Orders` (`OrderId`),
  KEY `FK_OrderItems_Items` (`ItemId`)
) ;

--
-- Déchargement des données de la table `orderitems`
--

INSERT INTO `orderitems` (`OrderItemId`, `OrderId`, `ItemId`, `Quantity`, `PriceGold`, `PriceSilver`, `PriceBronze`) VALUES
(3, 3, 1, 1, 50, 20, 10),
(4, 4, 14, 1, 400, 200, 80),
(5, 5, 2, 1, 120, 40, 15),
(6, 6, 15, 1, 80, 30, 10),
(7, 7, 16, 1, 210, 90, 30),
(8, 8, 16, 1, 210, 90, 30),
(9, 9, 22, 1, 20, 10, 5),
(10, 10, 25, 1, 25, 12, 6),
(11, 11, 17, 1, 170, 75, 25);

SET default_storage_engine = @OLD_DEFAULT_STORAGE_ENGINE;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `FK_Items_ItemTypes` FOREIGN KEY (`ItemTypeId`) REFERENCES `itemtypes` (`ItemTypeId`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `weaponproperties`
--
ALTER TABLE `weaponproperties`
  ADD CONSTRAINT `FK_WeaponProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `armorproperties`
--
ALTER TABLE `armorproperties`
  ADD CONSTRAINT `FK_ArmorProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `potionproperties`
--
ALTER TABLE `potionproperties`
  ADD CONSTRAINT `FK_PotionProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `magicspellproperties`
--
ALTER TABLE `magicspellproperties`
  ADD CONSTRAINT `FK_MagicSpellProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `FK_Carts_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `FK_Orders_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `FK_Inventory_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Inventory_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `FK_Reviews_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Reviews_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `cartitems`
--
ALTER TABLE `cartitems`
  ADD CONSTRAINT `FK_CartItems_Carts` FOREIGN KEY (`CartId`) REFERENCES `carts` (`CartId`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_CartItems_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Contraintes pour la table `orderitems`
--
ALTER TABLE `orderitems`
  ADD CONSTRAINT `FK_OrderItems_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_OrderItems_Orders` FOREIGN KEY (`OrderId`) REFERENCES `orders` (`OrderId`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;