-- phpMyAdmin SQL Dump
-- version 4.9.5deb2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : Dim 26 avr. 2026 à 17:26
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

CREATE DEFINER=`equipe15`@`%` PROCEDURE `sp_RegisterUser` (IN `p_Alias` VARCHAR(30), IN `p_Password` VARCHAR(255))  BEGIN
    IF p_Alias IS NULL OR TRIM(p_Alias) = '' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Alias invalide.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM Users WHERE TRIM(Alias) = TRIM(p_Alias) LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cet alias est deja utilise.';
    ELSE
        INSERT INTO Users (Alias, Password, Role, Gold, Silver, Bronze)
        VALUES (TRIM(p_Alias), p_Password, 'Player', 1000, 1000, 1000);
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
(56, 15, 100, 1),
(57, 15, 100, 1),
(60, 15, 100, 1),
(62, 15, 100, 1),
(65, 15, 100, 1);

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
(1, 3, '2026-04-21 14:16:20');

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
(1, 3, 14, 1),
(2, 3, 42, 1);

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
(1, 'Basic Sword', 'Épée simple pour débutant.', 50, 20, 10, 5, 1, 1, 'Commun', NULL),
(2, 'Knight Blade', 'Épée robuste de chevalier.', 120, 40, 15, 4, 1, 1, 'Rare', NULL),
(11, 'Leather Armor', 'Armure légère en cuir.', 40, 15, 5, 10, 2, 1, 'Commun', NULL),
(14, 'Golden Armor', 'Armure prestigieuse et résistante.', 400, 200, 80, 0, 2, 1, 'Epique', NULL),
(31, 'Fireball', 'Sort de feu basique.', 80, 30, 10, 10, 4, 1, 'Commun', NULL),
(32, 'Bull Battleaxe', 'Hache massive utilisée par les guerriers brutaux.', 180, 60, 20, 5, 1, 1, 'Rare', 'assets/images/armes/bull_battleaxe.png'),
(33, 'Dragon Slayer Longsword', 'Épée légendaire capable d’abattre des dragons.', 800, 300, 150, 1, 1, 1, 'Mythique', 'assets/images/armes/dragon_slayer_longsword.png'),
(34, 'Elf Longsword', 'Lame élégante forgée par les elfes.', 220, 80, 30, 4, 1, 1, 'Rare', 'assets/images/armes/elf_longsword.png'),
(35, 'Elven Knight Bow', 'Arc précis utilisé par les chevaliers elfes.', 260, 90, 35, 3, 1, 1, 'Epique', 'assets/images/armes/elven_knight_bow.png'),
(36, 'Knight Longsword', 'Épée classique des chevaliers humains.', 200, 70, 25, 6, 1, 1, 'Commun', 'assets/images/armes/knight_longsword.png'),
(37, 'Kratos Spear', 'Lance divine inspirée des dieux de la guerre.', 700, 250, 120, 1, 1, 1, 'Legendaire', 'assets/images/armes/kratos_spear.png'),
(38, 'Mage Staff', 'Bâton magique amplifiant les sorts.', 300, 120, 50, 4, 1, 1, 'Epique', 'assets/images/armes/mage_staff.png'),
(39, 'Orc Battleaxe', 'Hache lourde forgée pour la guerre brutale.', 240, 85, 30, 5, 1, 1, 'Rare', 'assets/images/armes/orc_battleaxe.png'),
(40, 'Samurai Katana', 'Katana tranchant d’un maître samouraï.', 500, 200, 90, 2, 1, 1, 'Legendaire', 'assets/images/armes/samurai_katana.png'),
(41, 'Spartan Spear and Shield', 'Arme et bouclier des guerriers spartiates.', 450, 170, 80, 3, 1, 1, 'Epique', 'assets/images/armes/spartan_spear_and_shield.png'),
(42, 'Sultan Scimitar and Shield', 'Arme élégante du désert royal.', 420, 160, 70, 2, 1, 1, 'Epique', 'assets/images/armes/sultan_scimitar_and_shield.png'),
(43, 'Viking Battleaxe', 'Hache redoutable des guerriers nordiques.', 280, 100, 40, 4, 1, 1, 'Rare', 'assets/images/armes/viking_battleaxe.png'),
(46, 'Small Health Potion', 'Restores a small amount of health.', 0, 5, 0, 100, 3, 1, 'Commun', 'assets/images/potions/small_health_potion.png'),
(47, 'Small Mana Potion', 'Restores a small amount of mana.', 0, 5, 0, 100, 3, 1, 'Commun', 'assets/images/potions/small_mana_potion.png'),
(48, 'Medium Health Potion', 'Restores a moderate amount of health.', 0, 10, 0, 100, 3, 1, 'Commun', 'assets/images/potions/medium_health_potion.png'),
(49, 'Medium Mana Potion', 'Restores a moderate amount of mana.', 0, 10, 0, 100, 3, 1, 'Commun', 'assets/images/potions/medium_mana_potion.png'),
(50, 'Large Health Potion', 'Restores a large amount of health.', 1, 0, 0, 100, 3, 1, 'Commun', 'assets/images/potions/large_health_potion.png'),
(51, 'Large Mana Potion', 'Restores a large amount of mana.', 1, 0, 0, 100, 3, 1, 'Commun', 'assets/images/potions/large_mana_potion.png'),
(52, 'Explosion Tome', 'Grimoire ancien libérant une explosion destructrice.', 350, 140, 60, 5, 4, 1, 'Epique', 'assets/images/sorts/explosion_tome.png'),
(53, 'Water Tome', 'Grimoire mystique contrôlant les eaux et les vagues.', 300, 120, 50, 55, 4, 1, 'Rare', 'assets/images/sorts/water_tome.png'),
(54, 'Test', 'fewfwe', 4, 0, 0, 1, 3, 1, 'Commun', NULL),
(56, '453534', '435', 0, 0, 0, 1, 2, 1, 'Commun', 'img/default_item.png'),
(57, 'test444', '44', 12, 0, 0, 1, 2, 1, 'Commun', 'img/default_item.png'),
(60, '43423', 'ee', 0, 0, 0, 1, 2, 1, 'Commun', 'img/default_item.png'),
(62, '324423', '23424', 0, 0, 0, 1, 2, 1, 'Commun', 'img/default_item.png'),
(65, 'straw', '4', 44, 0, 0, 1, 2, 1, 'Commun', 'img/items/item_69eb738a85163.jpg');

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
(2, 1, 42, 1, 420, 160, 70);

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
(1, 3, '2026-04-26 17:22:35', 820, 360, 150);

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
(46, 'Heal', 25, NULL),
(47, 'Mana', 25, NULL),
(48, 'Heal', 50, NULL),
(49, 'Mana', 50, NULL),
(50, 'Heal', 100, NULL),
(51, 'Mana', 100, NULL),
(54, 'Heal', 50, NULL);

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
(2, 3, 42, '5.0', NULL, '2026-04-26 17:23:03');

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
  `HintText` text DEFAULT NULL,
  `Difficulty` varchar(20) NOT NULL,
  `RiddleCategoryId` int(11) NOT NULL,
  `RewardGold` int(11) NOT NULL DEFAULT 0,
  `RewardSilver` int(11) NOT NULL DEFAULT 0,
  `RewardBronze` int(11) NOT NULL DEFAULT 0,
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Riddles`
--

INSERT INTO `Riddles` (`RiddleId`, `QuestionText`, `AnswerText`, `HintText`, `Difficulty`, `RiddleCategoryId`, `RewardGold`, `RewardSilver`, `RewardBronze`, `IsActive`) VALUES
(1, 'Je suis lié à un oiseau obscur et je frappe dans le silence avant d’être vu. Qui suis-je ?', 'Lame du Corbeau Noir', 'Cherche parmi les armes sombres et discrètes.', 'Difficile', 3, 50, 0, 0, 1),
(2, 'Je porte la mémoire des anciens et chacun de mes coups sonne comme un tambour. Qui suis-je ?', 'Marteau des Ancêtres', 'Une arme lourde héritée du passé.', 'Difficile', 3, 50, 0, 0, 1),
(3, 'Je vise de loin, je me cache dans la brume, puis j’atteins toujours ma cible. Qui suis-je ?', 'Arc de Brume-Lune', 'Une arme de précision liée au brouillard.', 'Difficile', 3, 50, 0, 0, 1),
(4, 'Je protège comme une forteresse grise et je résiste à d’innombrables coups. Qui suis-je ?', 'Cuirasse du Bastion Gris', 'Cherche une protection solide comme un mur.', 'Difficile', 4, 50, 0, 0, 1),
(5, 'Je suis de métal saint, et l’ombre fuit quand j’avance. Qui suis-je ?', 'Voile d’Acier Sacré', 'Une armure bénie contre les ténèbres.', 'Difficile', 4, 50, 0, 0, 1),
(6, 'Je viens avec le matin et je rends la vigueur à celui qui me boit. Qui suis-je ?', 'Élixir de l’Aube Claire', 'Une potion liée à la lumière du jour.', 'Difficile', 2, 50, 0, 0, 1),
(7, 'Je refroidis les nerfs et fais taire la peur dans la bataille. Qui suis-je ?', 'Breuvage du Sang-Froid', 'Une potion qui calme le cœur.', 'Difficile', 2, 50, 0, 0, 1),
(8, 'Je suis une colère venue du ciel et je frappe sept fois en lumière. Qui suis-je ?', 'Tempête des Sept Éclairs', 'Un sort ancien lié à la foudre.', 'Difficile', 1, 50, 0, 0, 1),
(9, 'Je suis une petite flamme lancée par la main d’un mage. Qui suis-je ?', 'Fireball', 'Va voir les sorts de feu les plus simples.', 'Facile', 1, 0, 0, 10, 1),
(10, 'Je suis une lumière sacrée qui chasse l’ombre. Qui suis-je ?', 'Holy Light', 'Cherche un sort lumineux et béni.', 'Facile', 1, 0, 0, 10, 1),
(11, 'Je suis un projectile glacé qui transperce l’air. Qui suis-je ?', 'Ice Spike', 'Regarde les sorts liés à la glace.', 'Moyenne', 1, 0, 10, 0, 1),
(12, 'Je suis une lame invisible faite de vent rapide. Qui suis-je ?', 'Wind Slash', 'Cherche un sort rapide associé à l’air.', 'Moyenne', 1, 0, 10, 0, 1),
(13, 'Je tombe du ciel avec une force électrique redoutable. Qui suis-je ?', 'Lightning Bolt', 'Va dans les sorts de foudre.', 'Difficile', 1, 10, 0, 0, 1),
(14, 'Je fais trembler le sol sous les pieds de tous. Qui suis-je ?', 'Earthquake', 'Cherche un sort qui frappe la terre entière.', 'Difficile', 1, 10, 0, 0, 1),
(15, 'Je rends un peu de vie après un combat. Qui suis-je ?', 'Small Health Potion', 'Va voir les petites potions de soin.', 'Facile', 2, 0, 0, 10, 1),
(16, 'Je retire le poison du corps de celui qui me boit. Qui suis-je ?', 'Antidote', 'Cherche une potion contre un mauvais effet.', 'Facile', 2, 0, 0, 10, 1),
(17, 'Je rends une quantité moyenne de vie au joueur. Qui suis-je ?', 'Medium Health Potion', 'Va voir les potions de soin intermédiaires.', 'Moyenne', 2, 0, 10, 0, 1),
(18, 'Je rends du mana pour continuer à lancer des sorts. Qui suis-je ?', 'Mana Potion', 'Cherche une potion liée à la magie.', 'Moyenne', 2, 0, 10, 0, 1),
(19, 'Je donne plus de force pendant un moment. Qui suis-je ?', 'Strength Potion', 'Va voir les potions qui améliorent les statistiques.', 'Difficile', 2, 10, 0, 0, 1),
(20, 'Je rends énormément de mana à celui qui me boit. Qui suis-je ?', 'Mega Mana Potion', 'Cherche la version la plus puissante d’une potion magique.', 'Difficile', 2, 10, 0, 0, 1),
(21, 'Je suis une épée simple pensée pour débuter. Qui suis-je ?', 'Basic Sword', 'Regarde les armes les plus de base.', 'Facile', 3, 0, 0, 10, 1),
(22, 'Je suis une petite lame légère et rapide. Qui suis-je ?', 'Dagger', 'Cherche une arme courte et discrète.', 'Facile', 3, 0, 0, 10, 1),
(23, 'Je suis une épée robuste portée par les chevaliers. Qui suis-je ?', 'Knight Blade', 'Va voir les armes de chevalier.', 'Moyenne', 3, 0, 10, 0, 1),
(24, 'Je suis une arme précise pour attaquer à distance. Qui suis-je ?', 'Hunter Bow', 'Cherche une arme qui lance des projectiles.', 'Moyenne', 3, 0, 10, 0, 1),
(25, 'Je suis une hache lourde faite pour frapper fort. Qui suis-je ?', 'War Axe', 'Va voir les armes les plus massives.', 'Difficile', 3, 10, 0, 0, 1),
(26, 'Je suis une grande épée créée pour tuer les monstres. Qui suis-je ?', 'Dragon Slayer', 'Cherche une arme légendaire très puissante.', 'Difficile', 3, 10, 0, 0, 1),
(27, 'Je suis une armure légère en cuir. Qui suis-je ?', 'Leather Armor', 'Regarde les protections les plus simples.', 'Facile', 4, 0, 0, 10, 1),
(28, 'Je suis une veste légère portée par les aventuriers. Qui suis-je ?', 'Traveler Vest', 'Cherche une protection modeste de voyage.', 'Facile', 4, 0, 0, 10, 1),
(29, 'Je suis une armure faite de mailles de métal. Qui suis-je ?', 'Chainmail', 'Va voir les armures intermédiaires.', 'Moyenne', 4, 0, 10, 0, 1),
(30, 'Je suis une robe conçue pour ceux qui utilisent la magie. Qui suis-je ?', 'Mage Robe', 'Cherche une tenue liée aux mages.', 'Moyenne', 4, 0, 10, 0, 1),
(31, 'Je suis une cape sombre qui protège avec discrétion. Qui suis-je ?', 'Shadow Cloak', 'Va voir les protections furtives.', 'Difficile', 4, 10, 0, 0, 1),
(32, 'Je suis une armure forgée à partir d’écailles rares. Qui suis-je ?', 'Dragon Scale Armor', 'Cherche l’une des protections les plus puissantes.', 'Difficile', 4, 10, 0, 0, 1),
(33, 'Je suis un peuple du nord connu pour mes drakkars, mes raids et mes guerriers redoutés. Qui suis-je ?', 'Vikings', 'Peuple scandinave célèbre du Moyen Âge.', 'Facile', 5, 0, 0, 10, 1),
(34, 'Je suis un grand royaume médiéval souvent associé aux rois, aux châteaux et aux chevaliers. Qui suis-je ?', 'France', 'Un royaume très puissant en Europe médiévale.', 'Facile', 5, 0, 0, 10, 1),
(35, 'Nous sommes des chevaliers chrétiens partis combattre en Terre sainte. Qui sommes-nous ?', 'Croisés', 'Ils participaient aux croisades.', 'Moyenne', 5, 0, 10, 0, 1),
(36, 'Je suis une grande ville souvent vue comme le cœur de l’Empire byzantin. Qui suis-je ?', 'Constantinople', 'Aujourd’hui, cette ville porte un autre nom.', 'Moyenne', 5, 0, 10, 0, 1),
(37, 'Je suis un peuple cavalier venu d’Asie qui a bâti un immense empire sous Gengis Khan. Qui suis-je ?', 'Mongols', 'Empire nomade très vaste.', 'Difficile', 5, 10, 0, 0, 1),
(38, 'Je suis un royaume chrétien de la péninsule ibérique lié à la Reconquista. Qui suis-je ?', 'Espagne', 'Pense à la Reconquista.', 'Difficile', 5, 10, 0, 0, 1);

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
(3, 0, 0, 0);

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
  `ProfileIsDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `ProfileDeletedAt` datetime DEFAULT NULL,
  `IsBanned` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `Users`
--

INSERT INTO `Users` (`UserId`, `Alias`, `FullName`, `Email`, `AvatarUrl`, `Password`, `Role`, `Gold`, `Silver`, `Bronze`, `ProfileIsDeleted`, `ProfileDeletedAt`, `IsBanned`) VALUES
(3, 'test123', 'Testual', 'testual@yopmail.com', NULL, '$2y$10$0X4W/We1RjcRARt/J0fvmuttALMkud5Y07M6KSVbKOoouQOsd7dFy', 'Admin', 12755, 61453, 85284, 0, NULL, 0);

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
(2, 12, 20, 120, 2, '0.90');

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
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `CartItems`
--
ALTER TABLE `CartItems`
  MODIFY `CartItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `Carts`
--
ALTER TABLE `Carts`
  MODIFY `CartId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `Inventory`
--
ALTER TABLE `Inventory`
  MODIFY `InventoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `Items`
--
ALTER TABLE `Items`
  MODIFY `ItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT pour la table `ItemTypes`
--
ALTER TABLE `ItemTypes`
  MODIFY `ItemTypeId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `OrderItems`
--
ALTER TABLE `OrderItems`
  MODIFY `OrderItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `Orders`
--
ALTER TABLE `Orders`
  MODIFY `OrderId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `Reviews`
--
ALTER TABLE `Reviews`
  MODIFY `ReviewId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `RiddleCategories`
--
ALTER TABLE `RiddleCategories`
  MODIFY `RiddleCategoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
