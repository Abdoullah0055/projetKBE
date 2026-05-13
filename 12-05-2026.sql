-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 12 mai 2026 à 23:40
-- Version du serveur :  10.3.39-MariaDB-0ubuntu0.20.04.2
-- Version de PHP : 7.4.3-4ubuntu2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `dbdarquest15`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`equipe15`@`%` PROCEDURE `sp_DeleteUserAccount` (IN `p_UserId` INT)  proc: BEGIN
    DECLARE v_user_exists INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_UserId IS NULL OR p_UserId <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Identifiant utilisateur invalide.';
    END IF;

    START TRANSACTION;

    SELECT COUNT(*) INTO v_user_exists
    FROM Users
    WHERE UserId = p_UserId
    FOR UPDATE;

    IF v_user_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Compte introuvable.';
    END IF;

    DELETE FROM OrderItems WHERE OrderId IN (SELECT OrderId FROM Orders WHERE UserId = p_UserId);
    DELETE FROM Orders WHERE UserId = p_UserId;
    DELETE FROM Carts WHERE UserId = p_UserId;
    DELETE FROM Reviews WHERE UserId = p_UserId;
    DELETE FROM Inventory WHERE UserId = p_UserId;
    DELETE FROM Users WHERE UserId = p_UserId;

    COMMIT;
END$$

CREATE DEFINER=`equipe15`@`%` PROCEDURE `sp_GetUserByAlias` (IN `p_Alias` VARCHAR(30))  BEGIN
    SELECT UserId, Alias, Password, Role, Gold, Silver, Bronze, IsBanned
    FROM Users
    WHERE TRIM(Alias) COLLATE utf8mb4_unicode_ci
        = TRIM(p_Alias) COLLATE utf8mb4_unicode_ci
    LIMIT 1;
END$$

CREATE DEFINER=`equipe15`@`%` PROCEDURE `sp_RegisterUser` (IN `p_Alias` VARCHAR(30), IN `p_Password` VARCHAR(255), IN `p_Email` VARCHAR(190))  BEGIN
    IF EXISTS (SELECT 1 FROM Users WHERE Alias = p_Alias) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet alias est deja utilise.';
    ELSEIF EXISTS (SELECT 1 FROM Users WHERE Email = p_Email) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ce courriel est deja utilise.';
    ELSE
        INSERT INTO Users (Alias, Password, Email, Role, Gold, Silver, Bronze, CurrentHP, MaxHP)
        VALUES (p_Alias, p_Password, p_Email, 'Player', 1000, 1000, 1000, 100, 100);
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `ArmorProperties`
--

CREATE TABLE `ArmorProperties` (
  `ItemId` int(11) NOT NULL,
  `Defense` int(11) NOT NULL,
  `Durability` int(11) NOT NULL DEFAULT 100,
  `RequiredLevel` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ArmorProperties`
--

INSERT INTO `ArmorProperties` (`ItemId`, `Defense`, `Durability`, `RequiredLevel`) VALUES
(11, 8, 100, 1),
(14, 35, 200, 5),
(67, 15, 100, 1);

-- --------------------------------------------------------

--
-- Structure de la table `CartItems`
--

CREATE TABLE `CartItems` (
  `CartItemId` int(11) NOT NULL,
  `CartId` int(11) NOT NULL,
  `ItemId` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `CartItems`
--

INSERT INTO `CartItems` (`CartItemId`, `CartId`, `ItemId`, `Quantity`) VALUES
(64, 2, 32, 1);

-- --------------------------------------------------------

--
-- Structure de la table `Carts`
--

CREATE TABLE `Carts` (
  `CartId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Carts`
--

INSERT INTO `Carts` (`CartId`, `UserId`, `CreatedAt`) VALUES
(1, 3, '2026-04-21 14:16:20'),
(2, 18, '2026-05-10 06:27:29'),
(3, 20, '2026-05-10 13:12:36');

-- --------------------------------------------------------

--
-- Structure de la table `Demandes`
--

CREATE TABLE `Demandes` (
  `DemandeId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` varchar(16) NOT NULL DEFAULT 'Pending',
  `ProcessedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Demandes`
--

INSERT INTO `Demandes` (`DemandeId`, `UserId`, `CreatedAt`, `Status`, `ProcessedAt`) VALUES
(1, 17, '2026-05-06 14:33:26', 'Accepted', '2026-05-06 14:44:45'),
(2, 17, '2026-05-06 14:44:59', 'Accepted', '2026-05-06 14:45:05'),
(3, 17, '2026-05-06 14:45:17', 'Accepted', '2026-05-06 14:45:21'),
(4, 17, '2026-05-06 14:45:30', 'Refused', '2026-05-06 14:45:42'),
(5, 18, '2026-05-10 06:41:59', 'Accepted', '2026-05-10 06:42:09'),
(6, 18, '2026-05-10 06:42:32', 'Accepted', '2026-05-10 06:42:45'),
(7, 18, '2026-05-10 06:43:09', 'Accepted', '2026-05-10 06:43:12');

-- --------------------------------------------------------

--
-- Structure de la table `EmailVerifications`
--

CREATE TABLE `EmailVerifications` (
  `Email` varchar(190) NOT NULL,
  `Token` varchar(128) NOT NULL,
  `ExpiresAt` datetime NOT NULL,
  `VerifiedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `EmailVerifications`
--

INSERT INTO `EmailVerifications` (`Email`, `Token`, `ExpiresAt`, `VerifiedAt`) VALUES
('abdouemail@yopmail.com', '69f5fe8732189f5e43447b144f337b87da8c21f215ecc0ea0b73b7fd2dcb41c2', '2026-05-13 22:09:46', '2026-05-12 22:09:53'),
('abdousprint2@yopmail.com', '040976f2ecc1d2180d0a6a6ba67cea5246a120f7e05ba03f308ad8c503e42e42', '2026-05-13 21:39:45', '2026-05-12 21:44:45'),
('testuser@yopmail.com', '4870c5d49da1ce3f56f98c66ae7555d8ba4244fb41797cb766f448e5192785ef', '2026-05-11 13:11:40', NULL),
('userinventory@yopmail.com', 'ccc733ce8e10b95880881cbb1b0f3525fb762c1f7aab3c98771711d12ed6031e', '2026-05-11 13:12:03', '2026-05-10 13:12:19');

-- --------------------------------------------------------

--
-- Structure de la table `Inventory`
--

CREATE TABLE `Inventory` (
  `InventoryId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `ItemId` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Inventory`
--

INSERT INTO `Inventory` (`InventoryId`, `UserId`, `ItemId`, `Quantity`) VALUES
(2, 3, 42, 1),
(3, 3, 1, 1),
(4, 3, 2, 1),
(5, 3, 32, 2),
(7, 3, 11, 5),
(13, 3, 46, 1),
(21, 3, 31, 2),
(22, 3, 14, 1),
(24, 3, 47, 1),
(27, 3, 49, 1),
(28, 3, 50, 2),
(29, 3, 51, 1),
(33, 18, 1, 4),
(35, 18, 42, 1),
(36, 18, 50, 4),
(37, 18, 32, 1),
(38, 20, 1, 1),
(39, 20, 32, 1),
(40, 20, 50, 4),
(41, 20, 51, 4),
(42, 20, 40, 1),
(43, 20, 47, 7),
(46, 18, 46, 3);

-- --------------------------------------------------------

--
-- Structure de la table `Items`
--

CREATE TABLE `Items` (
  `ItemId` int(11) NOT NULL,
  `Name` varchar(80) NOT NULL,
  `Description` text DEFAULT NULL,
  `PriceGold` int(11) NOT NULL DEFAULT 0,
  `PriceSilver` int(11) NOT NULL DEFAULT 0,
  `PriceBronze` int(11) NOT NULL DEFAULT 0,
  `Stock` int(11) NOT NULL DEFAULT 0,
  `ItemTypeId` int(11) NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `Rarity` varchar(20) NOT NULL DEFAULT 'Commun',
  `ImageUrl` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Items`
--

INSERT INTO `Items` (`ItemId`, `Name`, `Description`, `PriceGold`, `PriceSilver`, `PriceBronze`, `Stock`, `ItemTypeId`, `IsActive`, `Rarity`, `ImageUrl`) VALUES
(1, 'Basic Sword', 'Épée simple pour débutant.', 50, 20, 10, 998, 1, 1, 'Commun', NULL),
(2, 'Knight Blade', 'Épée robuste de chevalier.', 120, 40, 15, 3, 1, 1, 'Rare', NULL),
(11, 'Leather Armor', 'Armure légère en cuir.', 40, 15, 5, 5, 2, 1, 'Commun', NULL),
(14, 'Golden Armor', 'Armure prestigieuse et résistante.', 400, 200, 80, 0, 2, 1, 'Epique', NULL),
(31, 'Fireball', 'Sort de feu basique.', 80, 30, 10, 6, 4, 1, 'Commun', NULL),
(32, 'Bull Battleaxe', 'Hache massive utilisée par les guerriers brutaux.', 180, 60, 20, 1, 1, 1, 'Rare', 'assets/images/armes/bull_battleaxe.png'),
(33, 'Dragon Slayer Longsword', 'Épée légendaire capable d’abattre des dragons.', 800, 300, 150, 1, 1, 1, 'Mythique', 'assets/images/armes/dragon_slayer_longsword.png'),
(34, 'Elf Longsword', 'Lame élégante forgée par les elfes.', 220, 80, 30, 4, 1, 1, 'Rare', 'assets/images/armes/elf_longsword.png'),
(35, 'Elven Knight Bow', 'Arc précis utilisé par les chevaliers elfes.', 260, 90, 35, 3, 1, 1, 'Epique', 'assets/images/armes/elven_knight_bow.png'),
(36, 'Knight Longsword', 'Épée classique des chevaliers humains.', 200, 70, 25, 6, 1, 1, 'Commun', 'assets/images/armes/knight_longsword.png'),
(37, 'Kratos Spear', 'Lance divine inspirée des dieux de la guerre.', 700, 250, 120, 1, 1, 1, 'Legendaire', 'assets/images/armes/kratos_spear.png'),
(38, 'Mage Staff', 'Bâton magique amplifiant les sorts.', 300, 120, 50, 4, 1, 1, 'Epique', 'assets/images/armes/mage_staff.png'),
(39, 'Orc Battleaxe', 'Hache lourde forgée pour la guerre brutale.', 240, 85, 30, 5, 1, 1, 'Rare', 'assets/images/armes/orc_battleaxe.png'),
(40, 'Samurai Katana', 'Katana tranchant d’un maître samouraï.', 500, 200, 90, 1, 1, 1, 'Legendaire', 'assets/images/armes/samurai_katana.png'),
(41, 'Spartan Spear and Shield', 'Arme et bouclier des guerriers spartiates.', 450, 170, 80, 3, 1, 1, 'Epique', 'assets/images/armes/spartan_spear_and_shield.png'),
(42, 'Sultan Scimitar and Shield', 'Arme élégante du désert royal.', 420, 160, 70, 1, 1, 1, 'Epique', 'assets/images/armes/sultan_scimitar_and_shield.png'),
(43, 'Viking Battleaxe', 'Hache redoutable des guerriers nordiques.', 280, 100, 40, 4, 1, 1, 'Rare', 'assets/images/armes/viking_battleaxe.png'),
(46, 'Small Health Potion', 'Restores a small amount of health.', 0, 0, 5, 90, 3, 1, 'Commun', 'assets/images/potions/small_health_potion.png'),
(47, 'Small Mana Potion', 'Restores a small amount of mana.', 0, 0, 5, 90, 3, 1, 'Commun', 'assets/images/potions/small_mana_potion.png'),
(48, 'Medium Health Potion', 'Restores a moderate amount of health.', 0, 5, 0, 95, 3, 1, 'Commun', 'assets/images/potions/medium_health_potion.png'),
(49, 'Medium Mana Potion', 'Restores a moderate amount of mana.', 0, 5, 0, 97, 3, 1, 'Commun', 'assets/images/potions/medium_mana_potion.png'),
(50, 'Large Health Potion', 'Restores a large amount of health.', 0, 10, 0, 62, 3, 1, 'Commun', 'assets/images/potions/large_health_potion.png'),
(51, 'Large Mana Potion', 'Restores a large amount of mana.', 0, 10, 0, 91, 3, 1, 'Commun', 'assets/images/potions/large_mana_potion.png'),
(52, 'Explosion Tome', 'Grimoire ancien libérant une explosion destructrice.', 350, 140, 60, 4, 4, 1, 'Epique', 'assets/images/sorts/explosion_tome.png'),
(53, 'Water Tome', 'Grimoire mystique contrôlant les eaux et les vagues.', 300, 120, 50, 53, 4, 1, 'Rare', 'assets/images/sorts/water_tome.png'),
(67, 'testsetesestes', 'testes', 0, 0, 100, 1, 2, 0, 'Commun', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ItemTypes`
--

CREATE TABLE `ItemTypes` (
  `ItemTypeId` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `ItemTypes`
--

INSERT INTO `ItemTypes` (`ItemTypeId`, `Name`) VALUES
(2, 'Armor'),
(4, 'MagicSpell'),
(3, 'Potion'),
(1, 'Weapon');

-- --------------------------------------------------------

--
-- Structure de la table `MagicSpellProperties`
--

CREATE TABLE `MagicSpellProperties` (
  `ItemId` int(11) NOT NULL,
  `SpellDamage` int(11) NOT NULL DEFAULT 0,
  `ManaCost` int(11) NOT NULL,
  `ElementType` varchar(30) NOT NULL,
  `RequiredLevel` int(11) NOT NULL DEFAULT 1,
  `CooldownSeconds` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `MagicSpellProperties`
--

INSERT INTO `MagicSpellProperties` (`ItemId`, `SpellDamage`, `ManaCost`, `ElementType`, `RequiredLevel`, `CooldownSeconds`) VALUES
(31, 30, 15, 'Fire', 1, 3),
(52, 80, 25, 'Fire', 5, 5),
(53, 50, 15, 'Water', 3, 3);

-- --------------------------------------------------------

--
-- Structure de la table `OrderItems`
--

CREATE TABLE `OrderItems` (
  `OrderItemId` int(11) NOT NULL,
  `OrderId` int(11) NOT NULL,
  `ItemId` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `PriceGold` int(11) NOT NULL DEFAULT 0,
  `PriceSilver` int(11) NOT NULL DEFAULT 0,
  `PriceBronze` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `OrderItems`
--

INSERT INTO `OrderItems` (`OrderItemId`, `OrderId`, `ItemId`, `Quantity`, `PriceGold`, `PriceSilver`, `PriceBronze`) VALUES
(1, 1, 14, 1, 400, 200, 80),
(2, 1, 42, 1, 420, 160, 70),
(3, 2, 1, 1, 50, 20, 10),
(4, 2, 2, 1, 120, 40, 15),
(5, 2, 32, 1, 180, 60, 20),
(6, 2, 34, 1, 220, 80, 30),
(7, 3, 11, 1, 40, 15, 5),
(8, 4, 11, 5, 40, 15, 5),
(9, 5, 34, 1, 220, 80, 30),
(10, 5, 46, 1, 0, 5, 0),
(11, 6, 51, 1, 1, 0, 0),
(12, 7, 52, 1, 350, 140, 60),
(13, 8, 46, 3, 0, 5, 0),
(14, 9, 46, 3, 0, 5, 0),
(15, 10, 32, 1, 180, 60, 20),
(16, 11, 46, 1, 0, 0, 5),
(17, 11, 48, 1, 0, 5, 0),
(18, 11, 50, 1, 0, 10, 0),
(19, 12, 53, 2, 300, 120, 50),
(20, 13, 31, 1, 80, 30, 10),
(21, 14, 31, 1, 80, 30, 10),
(22, 15, 14, 1, 400, 200, 80),
(23, 16, 54, 1, 4, 0, 0),
(24, 17, 47, 3, 0, 0, 5),
(25, 17, 50, 3, 0, 10, 0),
(26, 17, 48, 3, 0, 5, 0),
(27, 18, 49, 3, 0, 5, 0),
(28, 19, 50, 4, 0, 10, 0),
(29, 20, 51, 4, 0, 10, 0),
(30, 21, 52, 1, 350, 140, 60),
(31, 21, 31, 1, 80, 30, 10),
(32, 21, 53, 1, 300, 120, 50),
(33, 22, 1, 1, 50, 20, 10),
(34, 23, 31, 1, 80, 30, 10),
(35, 24, 42, 1, 420, 160, 70),
(36, 25, 50, 1, 0, 10, 0),
(37, 26, 32, 1, 180, 60, 20),
(38, 27, 1, 1, 50, 20, 10),
(39, 27, 32, 1, 180, 60, 20),
(40, 27, 50, 4, 0, 10, 0),
(41, 27, 51, 4, 0, 10, 0),
(42, 27, 40, 1, 500, 200, 90),
(43, 27, 47, 7, 0, 0, 5),
(44, 28, 1, 1, 50, 20, 10),
(45, 28, 50, 5, 0, 10, 0),
(46, 29, 46, 1, 0, 0, 5),
(47, 30, 46, 3, 0, 0, 5),
(48, 31, 46, 1, 0, 0, 5),
(49, 32, 50, 20, 0, 10, 0),
(50, 33, 48, 1, 0, 5, 0),
(51, 34, 1, 2, 50, 20, 10);

-- --------------------------------------------------------

--
-- Structure de la table `Orders`
--

CREATE TABLE `Orders` (
  `OrderId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `OrderDate` datetime NOT NULL DEFAULT current_timestamp(),
  `TotalGold` int(11) NOT NULL DEFAULT 0,
  `TotalSilver` int(11) NOT NULL DEFAULT 0,
  `TotalBronze` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Orders`
--

INSERT INTO `Orders` (`OrderId`, `UserId`, `OrderDate`, `TotalGold`, `TotalSilver`, `TotalBronze`) VALUES
(1, 3, '2026-04-26 17:22:35', 820, 360, 150),
(2, 3, '2026-04-28 17:16:36', 570, 200, 75),
(3, 3, '2026-04-28 22:35:26', 40, 15, 5),
(4, 3, '2026-04-29 13:17:56', 200, 75, 25),
(5, 3, '2026-04-29 17:01:35', 220, 85, 30),
(6, 3, '2026-04-29 19:01:03', 1, 0, 0),
(7, 3, '2026-04-29 19:03:40', 350, 140, 60),
(8, 3, '2026-04-29 19:05:27', 0, 15, 0),
(9, 3, '2026-04-29 19:05:58', 0, 15, 0),
(10, 3, '2026-04-30 00:41:49', 180, 60, 20),
(11, 3, '2026-04-30 00:45:37', 0, 15, 5),
(12, 3, '2026-04-30 00:47:31', 600, 240, 100),
(13, 3, '2026-04-30 01:35:54', 80, 30, 10),
(14, 3, '2026-04-30 01:38:10', 80, 30, 10),
(15, 3, '2026-04-30 13:24:00', 400, 200, 80),
(16, 3, '2026-04-30 13:38:28', 4, 0, 0),
(17, 3, '2026-04-30 17:24:21', 0, 45, 15),
(18, 3, '2026-04-30 17:25:58', 0, 15, 0),
(19, 3, '2026-04-30 17:26:47', 0, 40, 0),
(20, 3, '2026-04-30 17:27:03', 0, 40, 0),
(21, 3, '2026-04-30 17:33:32', 730, 290, 120),
(22, 18, '2026-05-10 06:27:37', 50, 20, 10),
(23, 18, '2026-05-10 06:36:28', 80, 30, 10),
(24, 18, '2026-05-10 06:44:05', 420, 160, 70),
(25, 18, '2026-05-10 06:46:47', 0, 10, 0),
(26, 18, '2026-05-10 13:06:37', 180, 60, 20),
(27, 20, '2026-05-10 13:13:08', 730, 360, 155),
(28, 18, '2026-05-10 18:32:32', 50, 70, 10),
(29, 18, '2026-05-11 17:40:17', 0, 0, 5),
(30, 18, '2026-05-11 17:41:53', 0, 0, 15),
(31, 18, '2026-05-11 17:42:23', 0, 0, 5),
(32, 18, '2026-05-11 18:53:34', 0, 200, 0),
(33, 18, '2026-05-11 20:54:35', 0, 5, 0),
(34, 18, '2026-05-12 21:54:09', 100, 40, 20);

-- --------------------------------------------------------

--
-- Structure de la table `PasswordResets`
--

CREATE TABLE `PasswordResets` (
  `Email` varchar(190) NOT NULL,
  `Token` varchar(255) NOT NULL,
  `ExpiresAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `PotionProperties`
--

CREATE TABLE `PotionProperties` (
  `ItemId` int(11) NOT NULL,
  `EffectType` varchar(50) NOT NULL,
  `EffectValue` int(11) NOT NULL,
  `DurationSeconds` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `PotionProperties`
--

INSERT INTO `PotionProperties` (`ItemId`, `EffectType`, `EffectValue`, `DurationSeconds`) VALUES
(46, 'Heal', 3, NULL),
(47, 'Mana', 3, NULL),
(48, 'Heal', 4, NULL),
(49, 'Mana', 4, NULL),
(50, 'Heal', 5, NULL),
(51, 'Mana', 5, NULL),
(54, 'Heal', 5, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `Reviews`
--

CREATE TABLE `Reviews` (
  `ReviewId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `ItemId` int(11) NOT NULL,
  `Rating` decimal(2,1) NOT NULL,
  `Comment` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Reviews`
--

INSERT INTO `Reviews` (`ReviewId`, `UserId`, `ItemId`, `Rating`, `Comment`, `CreatedAt`) VALUES
(1, 3, 14, '3.0', NULL, '2026-04-26 17:23:00'),
(2, 3, 42, '5.0', NULL, '2026-04-26 17:23:03'),
(3, 3, 1, '2.5', NULL, '2026-04-28 22:35:38'),
(4, 3, 32, '3.0', NULL, '2026-04-28 22:35:45'),
(5, 3, 34, '3.5', NULL, '2026-04-28 22:35:49'),
(6, 3, 2, '3.0', NULL, '2026-04-28 22:35:53'),
(7, 3, 11, '4.0', NULL, '2026-04-28 22:35:56'),
(8, 3, 46, '5.0', NULL, '2026-04-29 17:01:41'),
(12, 18, 1, '4.0', 'tres bon no cap', '2026-05-10 18:26:27');

-- --------------------------------------------------------

--
-- Structure de la table `RiddleCategories`
--

CREATE TABLE `RiddleCategories` (
  `RiddleCategoryId` int(11) NOT NULL,
  `Name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `RiddleCategories`
--

INSERT INTO `RiddleCategories` (`RiddleCategoryId`, `Name`) VALUES
(3, 'Armes'),
(4, 'Armures'),
(5, 'Autres'),
(1, 'Magie'),
(2, 'Potions');

-- --------------------------------------------------------

--
-- Structure de la table `Riddles`
--

CREATE TABLE `Riddles` (
  `RiddleId` int(11) NOT NULL,
  `QuestionText` text NOT NULL,
  `AnswerText` varchar(255) NOT NULL,
  `WrongAnswer1` varchar(255) NOT NULL DEFAULT '',
  `WrongAnswer2` varchar(255) NOT NULL DEFAULT '',
  `WrongAnswer3` varchar(255) NOT NULL DEFAULT '',
  `HintText` text DEFAULT NULL,
  `Difficulty` varchar(20) NOT NULL,
  `RiddleCategoryId` int(11) NOT NULL,
  `RewardGold` int(11) NOT NULL DEFAULT 0,
  `RewardSilver` int(11) NOT NULL DEFAULT 0,
  `RewardBronze` int(11) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `RiddleType` varchar(50) DEFAULT 'MultipleChoice'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Riddles`
--

INSERT INTO `Riddles` (`RiddleId`, `QuestionText`, `AnswerText`, `WrongAnswer1`, `WrongAnswer2`, `WrongAnswer3`, `HintText`, `Difficulty`, `RiddleCategoryId`, `RewardGold`, `RewardSilver`, `RewardBronze`, `IsActive`, `RiddleType`) VALUES
(1, 'Je suis lié à un oiseau obscur et je frappe dans le silence avant d’être vu. Qui suis-je ?', 'Lame du Corbeau Noir', 'Basic Sword', 'Dagger', 'Knight Blade', 'Cherche parmi les armes sombres et discrètes.', 'Difficile', 3, 10, 0, 0, 1, 'MultipleChoice'),
(2, 'Je porte la mémoire des anciens et chacun de mes coups sonne comme un tambour. Qui suis-je ?', 'Marteau des Ancêtres', 'Hunter Bow', 'War Axe', 'Dragon Slayer', 'Une arme lourde héritée du passé.', 'Difficile', 3, 10, 0, 0, 1, 'MultipleChoice'),
(3, 'Je vise de loin, je me cache dans la brume, puis j’atteins toujours ma cible. Qui suis-je ?', 'Arc de Brume-Lune', 'Basic Sword', 'Dagger', 'War Axe', 'Une arme de précision liée au brouillard.', 'Difficile', 3, 10, 0, 0, 1, 'MultipleChoice'),
(4, 'Je protège comme une forteresse grise et je résiste à d’innombrables coups. Qui suis-je ?', 'Cuirasse du Bastion Gris', 'Leather Armor', 'Traveler Vest', 'Chainmail', 'Cherche une protection solide comme un mur.', 'Difficile', 4, 10, 0, 0, 1, 'MultipleChoice'),
(5, 'Je suis de métal saint, et l’ombre fuit quand j’avance. Qui suis-je ?', 'Voile d’Acier Sacré', 'Mage Robe', 'Shadow Cloak', 'Dragon Scale Armor', 'Une armure bénie contre les ténèbres.', 'Difficile', 4, 10, 0, 0, 1, 'MultipleChoice'),
(6, 'Je viens avec le matin et je rends la vigueur à celui qui me boit. Qui suis-je ?', 'Élixir de l’Aube Claire', 'Small Health Potion', 'Antidote', 'Medium Health Potion', 'Une potion liée à la lumière du jour.', 'Difficile', 2, 10, 0, 0, 1, 'MultipleChoice'),
(7, 'Je refroidis les nerfs et fais taire la peur dans la bataille. Qui suis-je ?', 'Breuvage du Sang-Froid', 'Mana Potion', 'Strength Potion', 'Mega Mana Potion', 'Une potion qui calme le cœur.', 'Difficile', 2, 10, 0, 0, 1, 'MultipleChoice'),
(8, 'Je suis une colère venue du ciel et je frappe sept fois en lumière. Qui suis-je ?', 'Tempête des Sept Éclairs', 'Fireball', 'Holy Light', 'Ice Spike', 'Un sort ancien lié à la foudre.', 'Difficile', 1, 10, 0, 0, 1, 'MultipleChoice'),
(9, 'Je suis une petite flamme lancée par la main d’un mage. Qui suis-je ?', 'Fireball', 'Holy Light', 'Ice Spike', 'Wind Slash', 'Va voir les sorts de feu les plus simples.', 'Facile', 1, 0, 0, 10, 1, 'MultipleChoice'),
(10, 'Je suis une lumière sacrée qui chasse l’ombre. Qui suis-je ?', 'Holy Light', 'Fireball', 'Ice Spike', 'Lightning Bolt', 'Cherche un sort lumineux et béni.', 'Facile', 1, 0, 0, 10, 1, 'MultipleChoice'),
(11, 'Je suis un projectile glacé qui transperce l’air. Qui suis-je ?', 'Ice Spike', 'Wind Slash', 'Lightning Bolt', 'Earthquake', 'Regarde les sorts liés à la glace.', 'Moyenne', 1, 0, 10, 0, 1, 'MultipleChoice'),
(12, 'Je suis une lame invisible faite de vent rapide. Qui suis-je ?', 'Wind Slash', 'Ice Spike', 'Lightning Bolt', 'Tempête des Sept Éclairs', 'Cherche un sort rapide associé à l’air.', 'Moyenne', 1, 0, 10, 0, 1, 'MultipleChoice'),
(13, 'Je tombe du ciel avec une force électrique redoutable. Qui suis-je ?', 'Lightning Bolt', 'Earthquake', 'Fireball', 'Holy Light', 'Va dans les sorts de foudre.', 'Difficile', 1, 10, 0, 0, 1, 'MultipleChoice'),
(14, 'Je fais trembler le sol sous les pieds de tous. Qui suis-je ?', 'Earthquake', 'Lightning Bolt', 'Wind Slash', 'Ice Spike', 'Cherche un sort qui frappe la terre entière.', 'Difficile', 1, 10, 0, 0, 1, 'MultipleChoice'),
(15, 'Je rends un peu de vie après un combat. Qui suis-je ?', 'Small Health Potion', 'Antidote', 'Medium Health Potion', 'Mana Potion', 'Va voir les petites potions de soin.', 'Facile', 2, 0, 0, 10, 1, 'MultipleChoice'),
(16, 'Je retire le poison du corps de celui qui me boit. Qui suis-je ?', 'Antidote', 'Small Health Potion', 'Medium Health Potion', 'Strength Potion', 'Cherche une potion contre un mauvais effet.', 'Facile', 2, 0, 0, 10, 1, 'MultipleChoice'),
(17, 'Je rends une quantité moyenne de vie au joueur. Qui suis-je ?', 'Medium Health Potion', 'Small Health Potion', 'Mana Potion', 'Strength Potion', 'Va voir les potions de soin intermédiaires.', 'Moyenne', 2, 0, 10, 0, 1, 'MultipleChoice'),
(18, 'Je rends du mana pour continuer à lancer des sorts. Qui suis-je ?', 'Mana Potion', 'Medium Health Potion', 'Strength Potion', 'Mega Mana Potion', 'Cherche une potion liée à la magie.', 'Moyenne', 2, 0, 10, 0, 1, 'MultipleChoice'),
(19, 'Je donne plus de force pendant un moment. Qui suis-je ?', 'Strength Potion', 'Mana Potion', 'Mega Mana Potion', 'Medium Health Potion', 'Va voir les potions qui améliorent les statistiques.', 'Difficile', 2, 10, 0, 0, 1, 'MultipleChoice'),
(20, 'Je rends énormément de mana à celui qui me boit. Qui suis-je ?', 'Mega Mana Potion', 'Strength Potion', 'Mana Potion', 'Medium Health Potion', 'Cherche la version la plus puissante d’une potion magique.', 'Difficile', 2, 10, 0, 0, 1, 'MultipleChoice'),
(21, 'Je suis une épée simple pensée pour débuter. Qui suis-je ?', 'Basic Sword', 'Dagger', 'Knight Blade', 'Hunter Bow', 'Regarde les armes les plus de base.', 'Facile', 3, 0, 0, 10, 1, 'MultipleChoice'),
(22, 'Je suis une petite lame légère et rapide. Qui suis-je ?', 'Dagger', 'Basic Sword', 'Knight Blade', 'War Axe', 'Cherche une arme courte et discrète.', 'Facile', 3, 0, 0, 10, 1, 'MultipleChoice'),
(23, 'Je suis une épée robuste portée par les chevaliers. Qui suis-je ?', 'Knight Blade', 'Basic Sword', 'Dagger', 'Dragon Slayer', 'Va voir les armes de chevalier.', 'Moyenne', 3, 0, 10, 0, 1, 'MultipleChoice'),
(24, 'Je suis une arme précise pour attaquer à distance. Qui suis-je ?', 'Hunter Bow', 'Knight Blade', 'War Axe', 'Dragon Slayer', 'Cherche une arme qui lance des projectiles.', 'Moyenne', 3, 0, 10, 0, 1, 'MultipleChoice'),
(25, 'Je suis une hache lourde faite pour frapper fort. Qui suis-je ?', 'War Axe', 'Hunter Bow', 'Dragon Slayer', 'Knight Blade', 'Va voir les armes les plus massives.', 'Difficile', 3, 10, 0, 0, 1, 'MultipleChoice'),
(26, 'Je suis une grande épée créée pour tuer les monstres. Qui suis-je ?', 'Dragon Slayer', 'War Axe', 'Hunter Bow', 'Knight Blade', 'Cherche une arme légendaire très puissante.', 'Difficile', 3, 10, 0, 0, 1, 'MultipleChoice'),
(27, 'Je suis une armure légère en cuir. Qui suis-je ?', 'Leather Armor', 'Traveler Vest', 'Chainmail', 'Mage Robe', 'Regarde les protections les plus simples.', 'Facile', 4, 0, 0, 10, 1, 'MultipleChoice'),
(28, 'Je suis une veste légère portée par les aventuriers. Qui suis-je ?', 'Traveler Vest', 'Leather Armor', 'Chainmail', 'Shadow Cloak', 'Cherche une protection modeste de voyage.', 'Facile', 4, 0, 0, 10, 1, 'MultipleChoice'),
(29, 'Je suis une armure faite de mailles de métal. Qui suis-je ?', 'Chainmail', 'Leather Armor', 'Traveler Vest', 'Dragon Scale Armor', 'Va voir les armures intermédiaires.', 'Moyenne', 4, 0, 10, 0, 1, 'MultipleChoice'),
(30, 'Je suis une robe conçue pour ceux qui utilisent la magie. Qui suis-je ?', 'Mage Robe', 'Chainmail', 'Shadow Cloak', 'Dragon Scale Armor', 'Cherche une tenue liée aux mages.', 'Moyenne', 4, 0, 10, 0, 1, 'MultipleChoice'),
(31, 'Je suis une cape sombre qui protège avec discrétion. Qui suis-je ?', 'Shadow Cloak', 'Mage Robe', 'Dragon Scale Armor', 'Chainmail', 'Va voir les protections furtives.', 'Difficile', 4, 10, 0, 0, 1, 'MultipleChoice'),
(32, 'Je suis une armure forgée à partir d’écailles rares. Qui suis-je ?', 'Dragon Scale Armor', 'Shadow Cloak', 'Mage Robe', 'Chainmail', 'Cherche l’une des protections les plus puissantes.', 'Difficile', 4, 10, 0, 0, 1, 'MultipleChoice'),
(33, 'Je suis un peuple du nord connu pour mes drakkars, mes raids et mes guerriers redoutés. Qui suis-je ?', 'Vikings', 'France', 'Croisés', 'Mongols', 'Peuple scandinave célèbre du Moyen Âge.', 'Facile', 5, 0, 0, 10, 1, 'MultipleChoice'),
(34, 'Je suis un grand royaume médiéval souvent associé aux rois, aux châteaux et aux chevaliers. Qui suis-je ?', 'France', 'Vikings', 'Croisés', 'Constantinople', 'Un royaume très puissant en Europe médiévale.', 'Facile', 5, 0, 0, 10, 1, 'MultipleChoice'),
(35, 'Nous sommes des chevaliers chrétiens partis combattre en Terre sainte. Qui sommes-nous ?', 'Croisés', 'Vikings', 'Constantinople', 'Mongols', 'Ils participaient aux croisades.', 'Moyenne', 5, 0, 10, 0, 1, 'MultipleChoice'),
(36, 'Je suis une grande ville souvent vue comme le cœur de l’Empire byzantin. Qui suis-je ?', 'Constantinople', 'France', 'Croisés', 'Espagne', 'Aujourd’hui, cette ville porte un autre nom.', 'Moyenne', 5, 0, 10, 0, 1, 'MultipleChoice'),
(37, 'Je suis un peuple cavalier venu d’Asie qui a bâti un immense empire sous Gengis Khan. Qui suis-je ?', 'Mongols', 'Vikings', 'Croisés', 'Espagne', 'Empire nomade très vaste.', 'Difficile', 5, 10, 0, 0, 1, 'MultipleChoice'),
(38, 'Je suis un royaume chrétien de la péninsule ibérique lié à la Reconquista. Qui suis-je ?', 'Espagne', 'Mongols', 'Constantinople', 'France', 'Pense à la Reconquista.', 'Difficile', 5, 10, 0, 0, 1, 'MultipleChoice'),
(26, 'Le sort Fireball est un sort de type feu.', 'Vrai', 'Faux', '', '', 'Ce sort lance une boule de feu.', 'Facile', 1, 0, 0, 10, 1, 'TrueFalse'),
(27, 'Le sort Holy Light est un sort de type ténèbres.', 'Faux', 'Vrai', '', '', 'Pense à la lumière sacrée.', 'Facile', 1, 0, 0, 10, 1, 'TrueFalse'),
(28, 'Le sort Ice Spike inflige des dégâts de glace.', 'Vrai', 'Faux', '', '', 'Un projectile glacé transperce l\'air.', 'Moyenne', 1, 0, 10, 0, 1, 'TrueFalse'),
(29, 'Le sort Earthquake est un sort de type feu.', 'Faux', 'Vrai', '', '', 'Ce sort fait trembler le sol.', 'Moyenne', 1, 0, 10, 0, 1, 'TrueFalse'),
(30, 'Le sort Tempête des Sept Éclairs frappe sept fois.', 'Vrai', 'Faux', '', '', 'Une colère venue du ciel, sept fois.', 'Difficile', 1, 10, 0, 0, 1, 'TrueFalse'),
(31, 'Quel sort lance une boule de feu ? Écris le nom exact.', 'Fireball', '', '', '', 'C\'est le sort de feu le plus basique.', 'Facile', 1, 0, 0, 10, 1, 'ShortAnswer'),
(32, 'Quel sort chasse l\'ombre avec une lumière sacrée ? Écris le nom exact.', 'Holy Light', '', '', '', 'La lumière est sacrée et bénie.', 'Facile', 1, 0, 0, 10, 1, 'ShortAnswer'),
(33, 'Quel sort de type glace transperce l\'air ? Écris le nom exact.', 'Ice Spike', '', '', '', 'Un projectile glacé très rapide.', 'Moyenne', 1, 0, 10, 0, 1, 'ShortAnswer'),
(34, 'Quel sort est une lame invisible faite de vent ? Écris le nom exact.', 'Wind Slash', '', '', '', 'Le vent devient une arme tranchante.', 'Moyenne', 1, 0, 10, 0, 1, 'ShortAnswer'),
(35, 'Quel sort de foudre tombe du ciel avec force ? Écris le nom exact.', 'Lightning Bolt', '', '', '', 'Un éclair frappe depuis les cieux.', 'Difficile', 1, 10, 0, 0, 1, 'ShortAnswer'),
(36, 'La potion Small Health Potion rend de la vie.', 'Vrai', 'Faux', '', '', 'C\'est la plus petite potion de soin.', 'Facile', 2, 0, 0, 10, 1, 'TrueFalse'),
(37, 'La potion Mana Potion rend des points de vie.', 'Faux', 'Vrai', '', '', 'Le mana sert à lancer des sorts.', 'Facile', 2, 0, 0, 10, 1, 'TrueFalse'),
(38, 'La potion Antidote sert à retirer le poison.', 'Vrai', 'Faux', '', '', 'Elle nettoie le corps d\'un mauvais effet.', 'Moyenne', 2, 0, 10, 0, 1, 'TrueFalse'),
(39, 'La potion Strength Potion rend du mana.', 'Faux', 'Vrai', '', '', 'Elle améliore la force, pas la magie.', 'Moyenne', 2, 0, 10, 0, 1, 'TrueFalse'),
(40, 'La potion Mega Mana Potion rend plus de mana que la Mana Potion.', 'Vrai', 'Faux', '', '', 'Mega signifie une version plus puissante.', 'Difficile', 2, 10, 0, 0, 1, 'TrueFalse'),
(41, 'Quelle potion rend un peu de vie ? Écris le nom exact.', 'Small Health Potion', '', '', '', 'La plus petite potion de soin.', 'Facile', 2, 0, 0, 10, 1, 'ShortAnswer'),
(42, 'Quelle potion retire le poison ? Écris le nom exact.', 'Antidote', '', '', '', 'Elle nettoie le corps d\'un effet nocif.', 'Facile', 2, 0, 0, 10, 1, 'ShortAnswer'),
(43, 'Quelle potion rend du mana ? Écris le nom exact.', 'Mana Potion', '', '', '', 'Elle permet de relancer des sorts.', 'Moyenne', 2, 0, 10, 0, 1, 'ShortAnswer'),
(44, 'Quelle potion améliore la force temporairement ? Écris le nom exact.', 'Strength Potion', '', '', '', 'Elle booste les statistiques de combat.', 'Moyenne', 2, 0, 10, 0, 1, 'ShortAnswer'),
(45, 'Quelle potion est la version la plus puissante pour le mana ? Écris le nom exact.', 'Mega Mana Potion', '', '', '', 'Mega indique la plus grande version.', 'Difficile', 2, 10, 0, 0, 1, 'ShortAnswer'),
(46, 'La Dagger est une épée lourde.', 'Faux', 'Vrai', '', '', 'Une dague est légère et rapide.', 'Facile', 3, 0, 0, 10, 1, 'TrueFalse'),
(47, 'La Basic Sword est l\'arme la plus simple du marché.', 'Vrai', 'Faux', '', '', 'Pensée pour les débutants.', 'Facile', 3, 0, 0, 10, 1, 'TrueFalse'),
(48, 'Le Hunter Bow sert à attaquer de près.', 'Faux', 'Vrai', '', '', 'Un arc attaque à distance.', 'Moyenne', 3, 0, 10, 0, 1, 'TrueFalse'),
(49, 'La Knight Blade est une épée de chevalier.', 'Vrai', 'Faux', '', '', 'Portée par les chevaliers.', 'Moyenne', 3, 0, 10, 0, 1, 'TrueFalse'),
(50, 'La War Axe est une arme légère et discrète.', 'Faux', 'Vrai', '', '', 'Une hache de guerre est massive.', 'Difficile', 3, 10, 0, 0, 1, 'TrueFalse'),
(51, 'Quelle est l\'épée la plus simple pour débuter ? Écris le nom exact.', 'Basic Sword', '', '', '', 'Pensée pour les novices.', 'Facile', 3, 0, 0, 10, 1, 'ShortAnswer'),
(52, 'Quelle est la petite lame légère et rapide ? Écris le nom exact.', 'Dagger', '', '', '', 'Une arme courte et discrète.', 'Facile', 3, 0, 0, 10, 1, 'ShortAnswer'),
(53, 'Quelle arme permet d\'attaquer à distance avec des projectiles ? Écris le nom exact.', 'Hunter Bow', '', '', '', 'On la porte sur l\'épaule.', 'Moyenne', 3, 0, 10, 0, 1, 'ShortAnswer'),
(54, 'Quelle épée est portée par les chevaliers ? Écris le nom exact.', 'Knight Blade', '', '', '', 'L\'arme des preux.', 'Moyenne', 3, 0, 10, 0, 1, 'ShortAnswer'),
(55, 'Quelle hache lourde est faite pour frapper fort ? Écris le nom exact.', 'War Axe', '', '', '', 'L\'arme la plus massive.', 'Difficile', 3, 10, 0, 0, 1, 'ShortAnswer'),
(56, 'La Leather Armor est une armure en cuir.', 'Vrai', 'Faux', '', '', 'Leather signifie cuir en anglais.', 'Facile', 4, 0, 0, 10, 1, 'TrueFalse'),
(57, 'La Mage Robe offre plus de défense que la Cuirasse du Bastion Gris.', 'Faux', 'Vrai', '', '', 'Une robe de mage protège peu physiquement.', 'Facile', 4, 0, 0, 10, 1, 'TrueFalse'),
(58, 'Le Shadow Cloak est un manteau lié aux ténèbres.', 'Vrai', 'Faux', '', '', 'Shadow signifie ombre.', 'Moyenne', 4, 0, 10, 0, 1, 'TrueFalse'),
(59, 'La Dragon Scale Armor est fabriquée à partir d\'écailles de dragon.', 'Vrai', 'Faux', '', '', 'Des écailles du grand reptile.', 'Moyenne', 4, 0, 10, 0, 1, 'TrueFalse'),
(60, 'La Cuirasse du Bastion Gris est une armure légère.', 'Faux', 'Vrai', '', '', 'Elle protège comme une forteresse.', 'Difficile', 4, 10, 0, 0, 1, 'TrueFalse'),
(61, 'Quelle armure est fabriquée en cuir ? Écris le nom exact.', 'Leather Armor', '', '', '', 'Un matériau souple et naturel.', 'Facile', 4, 0, 0, 10, 1, 'ShortAnswer'),
(62, 'Quelle robe est portée par les mages ? Écris le nom exact.', 'Mage Robe', '', '', '', 'Le vêtement des lanceurs de sorts.', 'Facile', 4, 0, 0, 10, 1, 'ShortAnswer'),
(63, 'Quel manteau est lié aux ténèbres ? Écris le nom exact.', 'Shadow Cloak', '', '', '', 'Il se fond dans l\'ombre.', 'Moyenne', 4, 0, 10, 0, 1, 'ShortAnswer'),
(64, 'Quelle armure est faite d\'écailles de dragon ? Écris le nom exact.', 'Dragon Scale Armor', '', '', '', 'Des écailles du grand reptile.', 'Moyenne', 4, 0, 10, 0, 1, 'ShortAnswer'),
(65, 'Quelle armure bénie fait fuir l\'ombre ? Écris le nom exact.', 'Voile d\'Acier Sacré', '', '', '', 'L\'ombre fuit quand on l\'avance.', 'Difficile', 4, 10, 0, 0, 1, 'ShortAnswer');

-- --------------------------------------------------------

--
-- Structure de la table `UserRiddles`
--

CREATE TABLE `UserRiddles` (
  `UserRiddleId` int(11) NOT NULL,
  `UserId` int(11) NOT NULL,
  `RiddleId` int(11) NOT NULL,
  `GivenAnswer` varchar(255) DEFAULT NULL,
  `IsSuccess` tinyint(1) NOT NULL,
  `AnsweredAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `UserRiddles`
--

INSERT INTO `UserRiddles` (`UserRiddleId`, `UserId`, `RiddleId`, `GivenAnswer`, `IsSuccess`, `AnsweredAt`) VALUES
(1, 3, 10, 'Fireball', 0, '2026-04-29 17:06:15'),
(2, 3, 10, 'Ice Spike', 0, '2026-04-29 17:06:24'),
(3, 3, 10, 'Fireball', 0, '2026-04-29 17:06:25'),
(4, 3, 15, 'Antidote', 0, '2026-04-29 17:07:59'),
(5, 3, 22, 'War Axe', 0, '2026-04-29 18:53:07'),
(6, 3, 22, 'Basic Sword', 0, '2026-04-29 18:53:11'),
(7, 3, 22, 'Dagger', 1, '2026-04-29 18:53:15'),
(8, 3, 9, 'Ice Spike', 0, '2026-04-29 18:55:28'),
(9, 3, 9, 'Ice Spike', 0, '2026-04-29 18:55:36'),
(10, 3, 9, 'Ice Spike', 0, '2026-04-29 18:55:41'),
(11, 3, 9, 'Holy Light', 0, '2026-04-29 18:55:46'),
(12, 3, 9, 'Fireball', 1, '2026-04-29 18:55:50'),
(13, 3, 12, 'Ice Spike', 0, '2026-04-29 18:56:38'),
(14, 3, 12, 'Tempête des Sept Éclairs', 0, '2026-04-29 18:56:41'),
(15, 3, 12, 'Wind Slash', 1, '2026-04-29 18:56:45'),
(16, 3, 33, 'France', 0, '2026-04-29 18:57:17'),
(17, 3, 33, 'France', 0, '2026-04-29 18:57:23'),
(18, 3, 13, 'Lightning Bolt', 1, '2026-04-29 18:57:46'),
(19, 3, 28, 'Leather Armor', 0, '2026-04-29 19:23:49'),
(20, 3, 28, 'Leather Armor', 0, '2026-04-29 19:23:53'),
(21, 3, 28, 'Leather Armor', 0, '2026-04-29 19:23:58'),
(22, 3, 28, 'Traveler Vest', 1, '2026-04-29 19:24:04'),
(23, 3, 3, 'War Axe', 0, '2026-04-29 23:58:22'),
(24, 3, 3, 'War Axe', 0, '2026-04-29 23:58:29'),
(25, 3, 3, 'Dagger', 0, '2026-04-29 23:58:33'),
(26, 3, 3, 'Basic Sword', 0, '2026-04-29 23:58:36'),
(27, 3, 3, 'Dagger', 0, '2026-04-29 23:58:40'),
(28, 3, 3, 'War Axe', 0, '2026-04-29 23:58:43'),
(29, 3, 3, 'Basic Sword', 0, '2026-04-29 23:58:47'),
(30, 3, 3, 'Dagger', 0, '2026-04-29 23:58:51'),
(31, 3, 3, 'Dagger', 0, '2026-04-29 23:58:55'),
(32, 3, 3, 'Dagger', 0, '2026-04-29 23:58:58'),
(33, 3, 22, 'Basic Sword', 0, '2026-04-30 00:01:32'),
(34, 3, 10, 'Holy Light', 1, '2026-04-30 02:38:37'),
(35, 3, 29, 'Chainmail', 1, '2026-04-30 02:39:15'),
(36, 3, 37, 'Vikings', 0, '2026-04-30 02:39:32'),
(37, 3, 14, 'Earthquake', 1, '2026-04-30 02:39:59'),
(38, 3, 4, 'Cuirasse du Bastion Gris', 1, '2026-04-30 02:41:36'),
(39, 3, 8, 'Tempête des Sept Éclairs', 1, '2026-04-30 02:41:59'),
(40, 3, 6, 'Élixir de l’Aube Claire', 1, '2026-04-30 02:42:31'),
(41, 3, 23, 'Knight Blade', 1, '2026-04-30 13:36:41'),
(42, 3, 12, 'Ice Spike', 0, '2026-04-30 13:37:00'),
(43, 12, 9, 'Fireball', 1, '2026-04-30 17:29:26'),
(44, 12, 13, 'Lightning Bolt', 1, '2026-04-30 17:29:45'),
(45, 12, 10, 'Holy Light', 1, '2026-04-30 17:30:03'),
(46, 3, 14, 'Earthquake', 1, '2026-04-30 17:31:43'),
(47, 3, 9, 'Fireball', 1, '2026-04-30 17:32:06'),
(48, 3, 12, 'Wind Slash', 1, '2026-04-30 17:32:21'),
(49, 3, 25, 'War Axe', 1, '2026-04-30 17:38:30'),
(50, 3, 25, 'War Axe', 1, '2026-04-30 17:38:46'),
(51, 3, 1, 'Lame du Corbeau Noir', 1, '2026-04-30 17:39:13'),
(52, 3, 1, '', 0, '2026-04-30 17:39:20'),
(53, 3, 1, 'Lame du Corbeau Noir', 1, '2026-04-30 17:39:45'),
(54, 3, 25, 'War Axe', 1, '2026-04-30 17:40:04'),
(55, 3, 38, 'Espagne', 1, '2026-04-30 17:40:24'),
(56, 3, 10, 'Holy Light', 1, '2026-04-30 17:42:35'),
(57, 18, 38, 'Espagne', 1, '2026-05-10 13:04:35'),
(58, 18, 43, 'Potion de mana', 0, '2026-05-10 13:05:06'),
(59, 18, 41, 'Health potion', 0, '2026-05-10 13:05:27'),
(60, 18, 29, 'Chainmail', 1, '2026-05-10 13:18:47'),
(61, 18, 63, 'Test', 0, '2026-05-10 13:19:00'),
(62, 18, 30, 'Dragon Scale Armor', 0, '2026-05-10 13:19:12'),
(63, 18, 56, 'Vrai', 1, '2026-05-10 13:19:30'),
(64, 18, 33, 'Vikings', 1, '2026-05-11 18:47:11'),
(65, 18, 32, 'Dragon Scale Armor', 1, '2026-05-11 18:47:23'),
(66, 18, 7, 'Strength Potion', 0, '2026-05-11 18:47:33'),
(67, 18, 7, '', 0, '2026-05-11 18:47:34'),
(68, 18, 7, '', 0, '2026-05-11 18:47:34'),
(69, 18, 7, '', 0, '2026-05-11 18:47:35'),
(70, 18, 47, 'Faux', 0, '2026-05-11 18:54:51'),
(71, 18, 51, 'fdfghfdx', 0, '2026-05-11 18:55:03'),
(72, 18, 8, 'Tempête des Sept Éclairs', 1, '2026-05-11 18:55:13'),
(73, 18, 38, 'Mongols', 0, '2026-05-11 18:55:23'),
(74, 18, 25, 'Hunter Bow', 0, '2026-05-11 20:35:47'),
(75, 18, 14, 'Earthquake', 1, '2026-05-11 20:44:09'),
(76, 18, 3, 'Dagger', 0, '2026-05-11 20:50:40'),
(77, 18, 57, 'Vrai', 1, '2026-05-11 20:51:40'),
(78, 18, 42, 'dfgfhfdgsfzs', 0, '2026-05-11 20:51:53'),
(79, 18, 17, 'Mana Potion', 0, '2026-05-11 20:52:17'),
(80, 18, 33, 'France', 0, '2026-05-11 20:52:34'),
(81, 18, 8, 'Tempête des Sept Éclairs', 1, '2026-05-11 20:52:46'),
(82, 18, 55, 'hfur43h89f', 0, '2026-05-11 20:53:16'),
(83, 18, 39, 'Vrai', 1, '2026-05-12 21:58:02'),
(84, 18, 15, 'Antidote', 0, '2026-05-12 21:58:12'),
(85, 18, 19, 'Strength Potion', 1, '2026-05-12 21:58:22'),
(86, 18, 61, 'fdffdgdf', 0, '2026-05-12 21:58:35'),
(87, 18, 5, 'Mage Robe', 0, '2026-05-12 21:58:47');

-- --------------------------------------------------------

--
-- Structure de la table `UserRiddleStats`
--

CREATE TABLE `UserRiddleStats` (
  `UserId` int(11) NOT NULL,
  `SolvedCount` int(11) NOT NULL DEFAULT 0,
  `FailedCount` int(11) NOT NULL DEFAULT 0,
  `MagicSolvedCount` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `UserRiddleStats`
--

INSERT INTO `UserRiddleStats` (`UserId`, `SolvedCount`, `FailedCount`, `MagicSolvedCount`) VALUES
(3, 22, 31, 10),
(12, 3, 0, 3),
(18, 11, 20, 3);

-- --------------------------------------------------------

--
-- Structure de la table `UserRoadmapProgress`
--

CREATE TABLE `UserRoadmapProgress` (
  `UserId` int(11) NOT NULL,
  `EnigmeId` int(11) NOT NULL,
  `CompletedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `Users`
--

CREATE TABLE `Users` (
  `UserId` int(11) NOT NULL,
  `Alias` varchar(30) NOT NULL,
  `FullName` varchar(80) DEFAULT NULL,
  `Email` varchar(190) DEFAULT NULL,
  `AvatarUrl` varchar(255) DEFAULT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` varchar(20) NOT NULL,
  `Gold` int(11) NOT NULL DEFAULT 1000,
  `Silver` int(11) NOT NULL DEFAULT 1000,
  `Bronze` int(11) NOT NULL DEFAULT 1000,
  `CurrentHP` int(11) NOT NULL DEFAULT 100,
  `MaxHP` int(11) NOT NULL DEFAULT 100,
  `ProfileIsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `ProfileDeletedAt` datetime DEFAULT NULL,
  `IsBanned` tinyint(1) NOT NULL DEFAULT 0,
  `FundsGivenCount` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Users`
--

INSERT INTO `Users` (`UserId`, `Alias`, `FullName`, `Email`, `AvatarUrl`, `Password`, `Role`, `Gold`, `Silver`, `Bronze`, `CurrentHP`, `MaxHP`, `ProfileIsDeleted`, `ProfileDeletedAt`, `IsBanned`, `FundsGivenCount`) VALUES
(3, 'test123', 'Testual deuxième du nom.', 'testual@yopmail.com', 'https://media.tenor.com/hxjNiKbsR9QAAAAe/rat-dumb.png', '$2y$10$0X4W/We1RjcRARt/J0fvmuttALMkud5Y07M6KSVbKOoouQOsd7dFy', 'Admin', 3348, 5538, 8474, 58, 100, 0, NULL, 0, 0),
(4, 'test12345', NULL, NULL, NULL, '$2y$10$2nakr3pseUE6k8pn.UFqY.IsyYW9K/rHHIeiMXSedDQzYSiZ2DZU2', 'Player', 1000, 1044, 1000, 100, 100, 0, NULL, 0, 0),
(5, 'usertest', 'User testosteronisé', 'testos@yopmail.com', 'https://i.redd.it/leave-me-alone-im-trying-to-study-human-v0-px2f8o9j57rb1.jpg?width=3024&format=pjpg&auto=webp&s=2ccd83773d56e0e637e27c7b0e32fb2b94fd810e', '$2y$10$TcAcHuaNOjaBklomyb6ZU.fHcuE5n3trol/30wi8DAT7cAiIi27lS', 'Player', 1000, 1000, 1000, 100, 100, 0, NULL, 0, 0),
(6, 'test1234', NULL, NULL, NULL, '$2y$10$Lmih7GntV0VZXqfnJHwSHuGiM6pv923XVX8MdqE/vFBbC7SABVklu', 'Player', 1000, 1000, 1000, 100, 100, 0, NULL, 0, 0),
(7, 'testofficiel', NULL, NULL, NULL, '$2y$10$4IyQIdBu4k6qS55fDkR.0e81HXp56SOHDEAGEv2rOQa7CBVDsXVba', 'Player', 1067, 1000, 1000, 100, 100, 0, NULL, 0, 0),
(8, 'LucAdmin', NULL, NULL, NULL, '$2y$10$MyAs7PKRHIiI3yx2.pYtvuVoDOwhVZ6XzUFx9k1t52i5FRDGznMbi', 'Admin', 1000, 1000, 1000, 100, 100, 0, NULL, 0, 0),
(9, 'LucUser', NULL, NULL, NULL, '$2y$10$Qker6oR09esyCHMprY5FiO.GPaFsHxsIYgNRWgY80EcwdYjS6MHrq', 'Player', 1067, 1060, 1050, 100, 100, 0, NULL, 0, 0),
(10, 'autretest', NULL, NULL, NULL, '$2y$10$78mZ3300YsfJ9slBK2PlX.1oUOLvjdWIIjUbB57e78N4zB2UeSiZ.', 'Player', 1000, 1000, 1000, 100, 100, 0, NULL, 0, 0),
(11, 'Orisa', NULL, NULL, NULL, '$2y$10$3EZNbbqffxgcvQueRMQ7HOOurYCYTjhmwDwjpVQev5VHBpLf88E2.', 'Player', 1000, 1000, 1000, 100, 100, 0, NULL, 0, 0),
(12, 'TestMage', NULL, NULL, NULL, '$2y$10$L68RUWlP3f5Yev6eKerEQulyvNs1ysZfaVZTJcw06okFeKhPEs3v6', 'Mage', 1010, 1000, 1020, 100, 100, 0, NULL, 0, 0),
(17, 'emailtest', NULL, 'nathanaguiar2006@gmail.com', NULL, '$2y$10$soW77mBvLaFCqAYIrF0SF.bQFthnBsso.LwE3L.cf4lt.6AvlI0oW', 'Admin', 1010, 1010, 1010, 100, 100, 0, NULL, 0, 0),
(18, 'abdouSprint', NULL, 'abdousprint2@yopmail.com', NULL, '$2y$10$pPWmJn6jo0jInowZZCA92eUQmJC9MQCYYhsqqrPTlAsdQMeK9o/CC', 'Admin', 1035, 15, 0, 84, 100, 0, NULL, 0, 0),
(19, 'testUser', NULL, 'testuser@yopmail.com', NULL, '$2y$10$garGUgeXJneTkYdqBks1dOlMxSVunZojJi3SgHo9ngqmlQ6C.2KtO', 'Player', 1000, 1000, 1000, 100, 100, 0, NULL, 0, 0),
(20, 'userInventory', NULL, 'userinventory@yopmail.com', NULL, '$2y$10$S4aBm5kDGUI5IZszkS7.lOhPn40aOX7/n95hisAW3QLW.c7gP653S', 'Player', 270, 640, 845, 100, 100, 0, NULL, 0, 0),
(22, 'abdouEmail', NULL, 'abdouemail@yopmail.com', NULL, '$2y$10$M76.8nI6m79NfdHHstRPPu4tLd88NmGWfk7qyfBRPkQ41lSVLIaa2', 'Player', 1000, 1000, 1000, 100, 100, 0, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Structure de la table `WeaponProperties`
--

CREATE TABLE `WeaponProperties` (
  `ItemId` int(11) NOT NULL,
  `DamageMin` int(11) NOT NULL,
  `DamageMax` int(11) NOT NULL,
  `Durability` int(11) NOT NULL DEFAULT 100,
  `RequiredLevel` int(11) NOT NULL DEFAULT 1,
  `AttackSpeed` decimal(4,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `WeaponProperties`
--

INSERT INTO `WeaponProperties` (`ItemId`, `DamageMin`, `DamageMax`, `Durability`, `RequiredLevel`, `AttackSpeed`) VALUES
(1, 5, 10, 100, 1, '1.00'),
(2, 12, 20, 120, 2, '0.90'),
(66, 10, 20, 100, 1, '1.00');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `ArmorProperties`
--
ALTER TABLE `ArmorProperties`
  ADD PRIMARY KEY (`ItemId`);

--
-- Index pour la table `CartItems`
--
ALTER TABLE `CartItems`
  ADD PRIMARY KEY (`CartItemId`),
  ADD UNIQUE KEY `UQ_CartItems_Cart_Item` (`CartId`,`ItemId`),
  ADD KEY `FK_CartItems_Items` (`ItemId`);

--
-- Index pour la table `Carts`
--
ALTER TABLE `Carts`
  ADD PRIMARY KEY (`CartId`),
  ADD UNIQUE KEY `UQ_Carts_User` (`UserId`);

--
-- Index pour la table `Demandes`
--
ALTER TABLE `Demandes`
  ADD PRIMARY KEY (`DemandeId`),
  ADD KEY `idx_demandes_userid` (`UserId`),
  ADD KEY `idx_demandes_createdat` (`CreatedAt`);

--
-- Index pour la table `EmailVerifications`
--
ALTER TABLE `EmailVerifications`
  ADD PRIMARY KEY (`Email`),
  ADD UNIQUE KEY `uq_email_verification_token` (`Token`);

--
-- Index pour la table `Inventory`
--
ALTER TABLE `Inventory`
  ADD PRIMARY KEY (`InventoryId`),
  ADD UNIQUE KEY `UQ_Inventory_User_Item` (`UserId`,`ItemId`),
  ADD KEY `FK_Inventory_Items` (`ItemId`);

--
-- Index pour la table `Items`
--
ALTER TABLE `Items`
  ADD PRIMARY KEY (`ItemId`),
  ADD UNIQUE KEY `UQ_Items_Name` (`Name`),
  ADD KEY `FK_Items_ItemTypes` (`ItemTypeId`);

--
-- Index pour la table `ItemTypes`
--
ALTER TABLE `ItemTypes`
  ADD PRIMARY KEY (`ItemTypeId`),
  ADD UNIQUE KEY `UQ_ItemTypes_Name` (`Name`);

--
-- Index pour la table `MagicSpellProperties`
--
ALTER TABLE `MagicSpellProperties`
  ADD PRIMARY KEY (`ItemId`);

--
-- Index pour la table `OrderItems`
--
ALTER TABLE `OrderItems`
  ADD PRIMARY KEY (`OrderItemId`),
  ADD KEY `FK_OrderItems_Orders` (`OrderId`),
  ADD KEY `FK_OrderItems_Items` (`ItemId`);

--
-- Index pour la table `Orders`
--
ALTER TABLE `Orders`
  ADD PRIMARY KEY (`OrderId`),
  ADD KEY `FK_Orders_Users` (`UserId`);

--
-- Index pour la table `PasswordResets`
--
ALTER TABLE `PasswordResets`
  ADD PRIMARY KEY (`Token`),
  ADD KEY `Email` (`Email`);

--
-- Index pour la table `PotionProperties`
--
ALTER TABLE `PotionProperties`
  ADD PRIMARY KEY (`ItemId`);

--
-- Index pour la table `Reviews`
--
ALTER TABLE `Reviews`
  ADD PRIMARY KEY (`ReviewId`),
  ADD UNIQUE KEY `UQ_Reviews_User_Item` (`UserId`,`ItemId`),
  ADD KEY `FK_Reviews_Items` (`ItemId`);

--
-- Index pour la table `RiddleCategories`
--
ALTER TABLE `RiddleCategories`
  ADD PRIMARY KEY (`RiddleCategoryId`),
  ADD UNIQUE KEY `UQ_RiddleCategories_Name` (`Name`);

--
-- Index pour la table `UserRiddles`
--
ALTER TABLE `UserRiddles`
  ADD PRIMARY KEY (`UserRiddleId`);

--
-- Index pour la table `UserRiddleStats`
--
ALTER TABLE `UserRiddleStats`
  ADD PRIMARY KEY (`UserId`);

--
-- Index pour la table `UserRoadmapProgress`
--
ALTER TABLE `UserRoadmapProgress`
  ADD PRIMARY KEY (`UserId`,`EnigmeId`);

--
-- Index pour la table `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`UserId`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `CartItems`
--
ALTER TABLE `CartItems`
  MODIFY `CartItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT pour la table `Carts`
--
ALTER TABLE `Carts`
  MODIFY `CartId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `Demandes`
--
ALTER TABLE `Demandes`
  MODIFY `DemandeId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `Inventory`
--
ALTER TABLE `Inventory`
  MODIFY `InventoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT pour la table `Items`
--
ALTER TABLE `Items`
  MODIFY `ItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT pour la table `ItemTypes`
--
ALTER TABLE `ItemTypes`
  MODIFY `ItemTypeId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `OrderItems`
--
ALTER TABLE `OrderItems`
  MODIFY `OrderItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT pour la table `Orders`
--
ALTER TABLE `Orders`
  MODIFY `OrderId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `Reviews`
--
ALTER TABLE `Reviews`
  MODIFY `ReviewId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `RiddleCategories`
--
ALTER TABLE `RiddleCategories`
  MODIFY `RiddleCategoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `UserRiddles`
--
ALTER TABLE `UserRiddles`
  MODIFY `UserRiddleId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT pour la table `Users`
--
ALTER TABLE `Users`
  MODIFY `UserId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `ArmorProperties`
--
ALTER TABLE `ArmorProperties`
  ADD CONSTRAINT `FK_ArmorProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `Items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `CartItems`
--
ALTER TABLE `CartItems`
  ADD CONSTRAINT `FK_CartItems_Carts` FOREIGN KEY (`CartId`) REFERENCES `Carts` (`CartId`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_CartItems_Items` FOREIGN KEY (`ItemId`) REFERENCES `Items` (`ItemId`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `Demandes`
--
ALTER TABLE `Demandes`
  ADD CONSTRAINT `fk_demandes_user` FOREIGN KEY (`UserId`) REFERENCES `Users` (`UserId`) ON DELETE CASCADE;

--
-- Contraintes pour la table `UserRoadmapProgress`
--
ALTER TABLE `UserRoadmapProgress`
  ADD CONSTRAINT `FK_UserRoadmapProgress_Users` FOREIGN KEY (`UserId`) REFERENCES `Users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
