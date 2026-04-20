-- --------------------------------------------------------
-- Initialisation de la base de données
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `projetkbe` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `projetkbe`;

-- --------------------------------------------------------
-- Suppression des tables existantes (ordre respectant les contraintes)
-- --------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `userriddles`;
DROP TABLE IF EXISTS `userriddlestats`;
DROP TABLE IF EXISTS `riddles`;
DROP TABLE IF EXISTS `riddlecategories`;

DROP TABLE IF EXISTS `weaponproperties`;
DROP TABLE IF EXISTS `armorproperties`;
DROP TABLE IF EXISTS `potionproperties`;
DROP TABLE IF EXISTS `magicspellproperties`;

DROP TABLE IF EXISTS `cartitems`;
DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `orderitems`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `inventory`;
DROP TABLE IF EXISTS `reviews`;

DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `itemtypes`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;
-- --------------------------------------------------------
-- Structure de la table `users`
-- --------------------------------------------------------
CREATE TABLE `users` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Structure de la table `itemtypes`
-- --------------------------------------------------------
CREATE TABLE `itemtypes` (
  `ItemTypeId` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`ItemTypeId`),
  UNIQUE KEY `UQ_ItemTypes_Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Structure de la table `items`
-- --------------------------------------------------------
CREATE TABLE `items` (
  `ItemId` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(80) NOT NULL,
  `Description` text,
  `PriceGold` int NOT NULL DEFAULT '0',
  `PriceSilver` int NOT NULL DEFAULT '0',
  `PriceBronze` int NOT NULL DEFAULT '0',
  `Stock` int NOT NULL DEFAULT '0',
  `ItemTypeId` int NOT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ItemId`),
  UNIQUE KEY `UQ_Items_Name` (`Name`),
  KEY `FK_Items_ItemTypes` (`ItemTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Tables de propriétés spécifiques
-- --------------------------------------------------------
CREATE TABLE `weaponproperties` (
  `ItemId` int NOT NULL,
  `DamageMin` int NOT NULL,
  `DamageMax` int NOT NULL,
  `Durability` int NOT NULL DEFAULT '100',
  `RequiredLevel` int NOT NULL DEFAULT '1',
  `AttackSpeed` decimal(4,2) NOT NULL DEFAULT '1.00',
  PRIMARY KEY (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `armorproperties` (
  `ItemId` int NOT NULL,
  `Defense` int NOT NULL,
  `Durability` int NOT NULL DEFAULT '100',
  `RequiredLevel` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `potionproperties` (
  `ItemId` int NOT NULL,
  `EffectType` varchar(50) NOT NULL,
  `EffectValue` int NOT NULL,
  `DurationSeconds` int DEFAULT NULL,
  PRIMARY KEY (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `magicspellproperties` (
  `ItemId` int NOT NULL,
  `SpellDamage` int NOT NULL DEFAULT '0',
  `ManaCost` int NOT NULL,
  `ElementType` varchar(30) NOT NULL,
  `RequiredLevel` int NOT NULL DEFAULT '1',
  `CooldownSeconds` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Paniers et Commandes
-- --------------------------------------------------------
CREATE TABLE `carts` (
  `CartId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CartId`),
  UNIQUE KEY `UQ_Carts_User` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `cartitems` (
  `CartItemId` int NOT NULL AUTO_INCREMENT,
  `CartId` int NOT NULL,
  `ItemId` int NOT NULL,
  `Quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`CartItemId`),
  UNIQUE KEY `UQ_CartItems_Cart_Item` (`CartId`,`ItemId`),
  KEY `FK_CartItems_Items` (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `orders` (
  `OrderId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `OrderDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `TotalGold` int NOT NULL DEFAULT '0',
  `TotalSilver` int NOT NULL DEFAULT '0',
  `TotalBronze` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`OrderId`),
  KEY `FK_Orders_Users` (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `orderitems` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Inventaire et Avis
-- --------------------------------------------------------
CREATE TABLE `inventory` (
  `InventoryId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `ItemId` int NOT NULL,
  `Quantity` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`InventoryId`),
  UNIQUE KEY `UQ_Inventory_User_Item` (`UserId`,`ItemId`),
  KEY `FK_Inventory_Items` (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `reviews` (
  `ReviewId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `ItemId` int NOT NULL,
  `Rating` decimal(2,1) NOT NULL,
  `Comment` text,
  `CreatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReviewId`),
  UNIQUE KEY `UQ_Reviews_User_Item` (`UserId`,`ItemId`),
  KEY `FK_Reviews_Items` (`ItemId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Tables des énigmes
-- --------------------------------------------------------

CREATE TABLE `riddlecategories` (
  `RiddleCategoryId` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`RiddleCategoryId`),
  UNIQUE KEY `UQ_RiddleCategories_Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `riddles` (
  `RiddleId` int NOT NULL AUTO_INCREMENT,
  `QuestionText` text NOT NULL,
  `AnswerText` varchar(255) NOT NULL,
  `HintText` text DEFAULT NULL,
  `Difficulty` varchar(20) NOT NULL,
  `RiddleCategoryId` int NOT NULL,
  `RewardGold` int NOT NULL DEFAULT '0',
  `RewardSilver` int NOT NULL DEFAULT '0',
  `RewardBronze` int NOT NULL DEFAULT '0',
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`RiddleId`),
  KEY `FK_Riddles_RiddleCategories` (`RiddleCategoryId`),
  CONSTRAINT `CHK_Riddles_Difficulty`
    CHECK (`Difficulty` IN ('Facile', 'Moyenne', 'Difficile'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `userriddles` (
  `UserRiddleId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `RiddleId` int NOT NULL,
  `GivenAnswer` varchar(255) DEFAULT NULL,
  `IsSuccess` tinyint(1) NOT NULL,
  `AnsweredAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserRiddleId`),
  KEY `FK_UserRiddles_Users` (`UserId`),
  KEY `FK_UserRiddles_Riddles` (`RiddleId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `userriddlestats` (
  `UserId` int NOT NULL,
  `SolvedCount` int NOT NULL DEFAULT '0',
  `FailedCount` int NOT NULL DEFAULT '0',
  `MagicSolvedCount` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------
-- Insertion des données initiales
-- --------------------------------------------------------

INSERT INTO `itemtypes` (`ItemTypeId`, `Name`) VALUES
(1, 'Weapon'), (2, 'Armor'), (3, 'Potion'), (4, 'MagicSpell');

INSERT INTO `items` (`ItemId`, `Name`, `Description`, `PriceGold`, `PriceSilver`, `PriceBronze`, `Stock`, `ItemTypeId`, `IsActive`) VALUES
(1, 'Basic Sword', 'Épée simple pour débutant.', 50, 20, 10, 5, 1, 1),
(2, 'Knight Blade', 'Épée robuste de chevalier.', 120, 40, 15, 4, 1, 1),
(11, 'Leather Armor', 'Armure légère en cuir.', 40, 15, 5, 10, 2, 1),
(14, 'Golden Armor', 'Armure prestigieuse et résistante.', 400, 200, 80, 1, 2, 1),
(21, 'Small Health Potion', 'Restaure un peu de vie.', 10, 5, 2, 50, 3, 1),
(31, 'Fireball', 'Sort de feu basique.', 80, 30, 10, 10, 4, 1);

INSERT INTO `weaponproperties` (`ItemId`, `DamageMin`, `DamageMax`, `Durability`, `RequiredLevel`, `AttackSpeed`) VALUES
(1, 5, 10, 100, 1, 1.00),
(2, 12, 20, 120, 2, 0.90);

INSERT INTO `armorproperties` (`ItemId`, `Defense`, `Durability`, `RequiredLevel`) VALUES
(11, 8, 100, 1),
(14, 35, 200, 5);

INSERT INTO `potionproperties` (`ItemId`, `EffectType`, `EffectValue`, `DurationSeconds`) VALUES
(21, 'Heal', 25, NULL);

INSERT INTO `magicspellproperties` (`ItemId`, `SpellDamage`, `ManaCost`, `ElementType`, `RequiredLevel`, `CooldownSeconds`) VALUES
(31, 30, 15, 'Fire', 1, 3);

INSERT INTO `users` (`UserId`, `Alias`, `FullName`, `Email`, `AvatarUrl`, `Password`, `Role`, `Gold`, `Silver`, `Bronze`) VALUES
(3, 'test123', 'Testual', 'testual@yopmail.com', NULL, '$2y$10$0X4W/We1RjcRARt/J0fvmuttALMkud5Y07M6KSVbKOoouQOsd7dFy', 'Player', 13575, 61813, 85434);

-- --------------------------------------------------------
-- 8 énigmes spéciales à 50 gold
-- --------------------------------------------------------
INSERT INTO `riddlecategories` (`RiddleCategoryId`, `Name`) VALUES
(1, 'Magie'),
(2, 'Potions'),
(3, 'Armes'),
(4, 'Armures'),
(5, 'Autres');

INSERT INTO `riddles`
(`RiddleId`,`QuestionText`,`AnswerText`,`HintText`,`Difficulty`,`RiddleCategoryId`,`RewardGold`,`RewardSilver`,`RewardBronze`,`IsActive`)
VALUES
(1,
'Je suis lié à un oiseau obscur et je frappe dans le silence avant d'être vu. Qui suis-je ?',
'Lame du Corbeau Noir',
'Cherche parmi les armes sombres et discrètes.',
'Difficile',
3,
50,0,0,1),

(2,
'Je porte la mémoire des anciens et chacun de mes coups sonne comme un tambour. Qui suis-je ?',
'Marteau des Ancêtres',
'Une arme lourde héritée du passé.',
'Difficile',
3,
50,0,0,1),

(3,
'Je vise de loin, je me cache dans la brume, puis j'atteins toujours ma cible. Qui suis-je ?',
'Arc de Brume-Lune',
'Une arme de précision liée au brouillard.',
'Difficile',
3,
50,0,0,1),

(4,
'Je protège comme une forteresse grise et je résiste à d'innombrables coups. Qui suis-je ?',
'Cuirasse du Bastion Gris',
'Cherche une protection solide comme un mur.',
'Difficile',
4,
50,0,0,1),

(5,
'Je suis de métal saint, et l'ombre fuit quand j'avance. Qui suis-je ?',
'Voile d'Acier Sacré',
'Une armure bénie contre les ténèbres.',
'Difficile',
4,
50,0,0,1),

(6,
'Je viens avec le matin et je rends la vigueur à celui qui me boit. Qui suis-je ?',
'Élixir de l'Aube Claire',
'Une potion liée à la lumière du jour.',
'Difficile',
2,
50,0,0,1),

(7,
'Je refroidis les nerfs et fais taire la peur dans la bataille. Qui suis-je ?',
'Breuvage du Sang-Froid',
'Une potion qui calme le cœur.',
'Difficile',
2,
50,0,0,1),

(8,
'Je suis une colère venue du ciel et je frappe sept fois en lumière. Qui suis-je ?',
'Tempête des Sept Éclairs',
'Un sort ancien lié à la foudre.',
'Difficile',
1,
50,0,0,1);

-- --------------------------------------------------------
-- Magie : 6 énigmes
-- --------------------------------------------------------

INSERT INTO `riddles`
(`RiddleId`,`QuestionText`,`AnswerText`,`HintText`,`Difficulty`,`RiddleCategoryId`,`RewardGold`,`RewardSilver`,`RewardBronze`,`IsActive`)
VALUES
(9,
'Je suis une petite flamme lancée par la main d'un mage. Qui suis-je ?',
'Fireball',
'Va voir les sorts de feu les plus simples.',
'Facile',
1,
0,0,10,1),

(10,
'Je suis une lumière sacrée qui chasse l'ombre. Qui suis-je ?',
'Holy Light',
'Cherche un sort lumineux et béni.',
'Facile',
1,
0,0,10,1),

(11,
'Je suis un projectile glacé qui transperce l'air. Qui suis-je ?',
'Ice Spike',
'Regarde les sorts liés à la glace.',
'Moyenne',
1,
0,10,0,1),

(12,
'Je suis une lame invisible faite de vent rapide. Qui suis-je ?',
'Wind Slash',
'Cherche un sort rapide associé à l'air.',
'Moyenne',
1,
0,10,0,1),

(13,
'Je tombe du ciel avec une force électrique redoutable. Qui suis-je ?',
'Lightning Bolt',
'Va dans les sorts de foudre.',
'Difficile',
1,
10,0,0,1),

(14,
'Je fais trembler le sol sous les pieds de tous. Qui suis-je ?',
'Earthquake',
'Cherche un sort qui frappe la terre entière.',
'Difficile',
1,
10,0,0,1);

-- --------------------------------------------------------
-- Potions : 6 énigmes
-- --------------------------------------------------------

INSERT INTO `riddles`
(`RiddleId`,`QuestionText`,`AnswerText`,`HintText`,`Difficulty`,`RiddleCategoryId`,`RewardGold`,`RewardSilver`,`RewardBronze`,`IsActive`)
VALUES
(15,
'Je rends un peu de vie après un combat. Qui suis-je ?',
'Small Health Potion',
'Va voir les petites potions de soin.',
'Facile',
2,
0,0,10,1),

(16,
'Je retire le poison du corps de celui qui me boit. Qui suis-je ?',
'Antidote',
'Cherche une potion contre un mauvais effet.',
'Facile',
2,
0,0,10,1),

(17,
'Je rends une quantité moyenne de vie au joueur. Qui suis-je ?',
'Medium Health Potion',
'Va voir les potions de soin intermédiaires.',
'Moyenne',
2,
0,10,0,1),

(18,
'Je rends du mana pour continuer à lancer des sorts. Qui suis-je ?',
'Mana Potion',
'Cherche une potion liée à la magie.',
'Moyenne',
2,
0,10,0,1),

(19,
'Je donne plus de force pendant un moment. Qui suis-je ?',
'Strength Potion',
'Va voir les potions qui améliorent les statistiques.',
'Difficile',
2,
10,0,0,1),

(20,
'Je rends énormément de mana à celui qui me boit. Qui suis-je ?',
'Mega Mana Potion',
'Cherche la version la plus puissante d'une potion magique.',
'Difficile',
2,
10,0,0,1);

-- --------------------------------------------------------
-- Armes : 6 énigmes
-- --------------------------------------------------------

INSERT INTO `riddles`
(`RiddleId`,`QuestionText`,`AnswerText`,`HintText`,`Difficulty`,`RiddleCategoryId`,`RewardGold`,`RewardSilver`,`RewardBronze`,`IsActive`)
VALUES
(21,
'Je suis une épée simple pensée pour débuter. Qui suis-je ?',
'Basic Sword',
'Regarde les armes les plus de base.',
'Facile',
3,
0,0,10,1),

(22,
'Je suis une petite lame légère et rapide. Qui suis-je ?',
'Dagger',
'Cherche une arme courte et discrète.',
'Facile',
3,
0,0,10,1),

(23,
'Je suis une épée robuste portée par les chevaliers. Qui suis-je ?',
'Knight Blade',
'Va voir les armes de chevalier.',
'Moyenne',
3,
0,10,0,1),

(24,
'Je suis une arme précise pour attaquer à distance. Qui suis-je ?',
'Hunter Bow',
'Cherche une arme qui lance des projectiles.',
'Moyenne',
3,
0,10,0,1),

(25,
'Je suis une hache lourde faite pour frapper fort. Qui suis-je ?',
'War Axe',
'Va voir les armes les plus massives.',
'Difficile',
3,
10,0,0,1),

(26,
'Je suis une grande épée créée pour tuer les monstres. Qui suis-je ?',
'Dragon Slayer',
'Cherche une arme légendaire très puissante.',
'Difficile',
3,
10,0,0,1);

-- --------------------------------------------------------
-- Armures : 6 énigmes
-- --------------------------------------------------------

INSERT INTO `riddles`
(`RiddleId`,`QuestionText`,`AnswerText`,`HintText`,`Difficulty`,`RiddleCategoryId`,`RewardGold`,`RewardSilver`,`RewardBronze`,`IsActive`)
VALUES
(27,
'Je suis une armure légère en cuir. Qui suis-je ?',
'Leather Armor',
'Regarde les protections les plus simples.',
'Facile',
4,
0,0,10,1),

(28,
'Je suis une veste légère portée par les aventuriers. Qui suis-je ?',
'Traveler Vest',
'Cherche une protection modeste de voyage.',
'Facile',
4,
0,0,10,1),

(29,
'Je suis une armure faite de mailles de métal. Qui suis-je ?',
'Chainmail',
'Va voir les armures intermédiaires.',
'Moyenne',
4,
0,10,0,1),

(30,
'Je suis une robe conçue pour ceux qui utilisent la magie. Qui suis-je ?',
'Mage Robe',
'Cherche une tenue liée aux mages.',
'Moyenne',
4,
0,10,0,1),

(31,
'Je suis une cape sombre qui protège avec discrétion. Qui suis-je ?',
'Shadow Cloak',
'Va voir les protections furtives.',
'Difficile',
4,
10,0,0,1),

(32,
'Je suis une armure forgée à partir d'écailles rares. Qui suis-je ?',
'Dragon Scale Armor',
'Cherche l'une des protections les plus puissantes.',
'Difficile',
4,
10,0,0,1);

-- --------------------------------------------------------
-- Autres : 6 énigmes
-- --------------------------------------------------------

INSERT INTO `riddles`
(`RiddleId`,`QuestionText`,`AnswerText`,`HintText`,`Difficulty`,`RiddleCategoryId`,`RewardGold`,`RewardSilver`,`RewardBronze`,`IsActive`)
VALUES
(33,
'Je suis un peuple du nord connu pour mes drakkars, mes raids et mes guerriers redoutés. Qui suis-je ?',
'Vikings',
'Peuple scandinave célèbre du Moyen Âge.',
'Facile',
5,
0,0,10,1),

(34,
'Je suis un grand royaume médiéval souvent associé aux rois, aux châteaux et aux chevaliers. Qui suis-je ?',
'France',
'Un royaume très puissant en Europe médiévale.',
'Facile',
5,
0,0,10,1),

(35,
'Nous sommes des chevaliers chrétiens partis combattre en Terre sainte. Qui sommes-nous ?',
'Croisés',
'Ils participaient aux croisades.',
'Moyenne',
5,
0,10,0,1),

(36,
'Je suis une grande ville souvent vue comme le cœur de l'Empire byzantin. Qui suis-je ?',
'Constantinople',
'Aujourd'hui, cette ville porte un autre nom.',
'Moyenne',
5,
0,10,0,1),

(37,
'Je suis un peuple cavalier venu d'Asie qui a bâti un immense empire sous Gengis Khan. Qui suis-je ?',
'Mongols',
'Empire nomade très vaste.',
'Difficile',
5,
10,0,0,1),

(38,
'Je suis un royaume chrétien de la péninsule ibérique lié à la Reconquista. Qui suis-je ?',
'Espagne',
'Pense à la Reconquista.',
'Difficile',
5,
10,0,0,1);
-- --------------------------------------------------------
-- Stats initiales
-- --------------------------------------------------------

INSERT INTO `userriddlestats` (`UserId`, `SolvedCount`, `FailedCount`, `MagicSolvedCount`) VALUES
(3, 0, 0, 0);

-- --------------------------------------------------------
-- Ajout des contraintes (Foreign Keys)
-- --------------------------------------------------------
ALTER TABLE `items` ADD CONSTRAINT `FK_Items_ItemTypes` FOREIGN KEY (`ItemTypeId`) REFERENCES `itemtypes` (`ItemTypeId`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `weaponproperties` ADD CONSTRAINT `FK_WeaponProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `armorproperties` ADD CONSTRAINT `FK_ArmorProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `potionproperties` ADD CONSTRAINT `FK_PotionProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `magicspellproperties` ADD CONSTRAINT `FK_MagicSpellProperties_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `carts` ADD CONSTRAINT `FK_Carts_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `cartitems` ADD CONSTRAINT `FK_CartItems_Carts` FOREIGN KEY (`CartId`) REFERENCES `carts` (`CartId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `cartitems` ADD CONSTRAINT `FK_CartItems_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `orders` ADD CONSTRAINT `FK_Orders_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `orderitems` ADD CONSTRAINT `FK_OrderItems_Orders` FOREIGN KEY (`OrderId`) REFERENCES `orders` (`OrderId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `orderitems` ADD CONSTRAINT `FK_OrderItems_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `inventory` ADD CONSTRAINT `FK_Inventory_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `inventory` ADD CONSTRAINT `FK_Inventory_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE `reviews` ADD CONSTRAINT `FK_Reviews_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `reviews` ADD CONSTRAINT `FK_Reviews_Items` FOREIGN KEY (`ItemId`) REFERENCES `items` (`ItemId`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `riddles`
ADD CONSTRAINT `FK_Riddles_RiddleCategories`
FOREIGN KEY (`RiddleCategoryId`) REFERENCES `riddlecategories` (`RiddleCategoryId`)
ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `userriddles`
ADD CONSTRAINT `FK_UserRiddles_Users`
FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `userriddles`
ADD CONSTRAINT `FK_UserRiddles_Riddles`
FOREIGN KEY (`RiddleId`) REFERENCES `riddles` (`RiddleId`)
ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `userriddlestats`
ADD CONSTRAINT `FK_UserRiddleStats_Users`
FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`)
ON DELETE CASCADE ON UPDATE CASCADE;
-- --------------------------------------------------------
-- Procédures Stockées
-- --------------------------------------------------------
DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_DeleteUserAccount`$$
CREATE PROCEDURE `sp_DeleteUserAccount` (IN `p_UserId` INT)
proc: BEGIN
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
    SELECT COUNT(*) INTO v_user_exists FROM Users WHERE UserId = p_UserId FOR UPDATE;
    IF v_user_exists = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Compte introuvable.';
    END IF;

    DELETE FROM OrderItems WHERE OrderId IN (SELECT OrderId FROM Orders WHERE UserId = p_UserId);
    DELETE FROM Orders WHERE UserId = p_UserId;
    DELETE FROM Carts WHERE UserId = p_UserId;
    DELETE FROM Reviews WHERE UserId = p_UserId;
    DELETE FROM Inventory WHERE UserId = p_UserId;
    DELETE FROM Users WHERE UserId = p_UserId;

    IF ROW_COUNT() <> 1 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Suppression du compte echouee.';
    END IF;
    COMMIT;
END$$

DROP PROCEDURE IF EXISTS `sp_GetUserByAlias`$$
CREATE PROCEDURE `sp_GetUserByAlias` (IN `p_Alias` VARCHAR(30))
BEGIN
    SELECT UserId, Alias, Password, Role, Gold, Silver, Bronze, IsBanned
    FROM Users
    WHERE TRIM(Alias) COLLATE utf8mb4_unicode_ci = TRIM(p_Alias) COLLATE utf8mb4_unicode_ci
    LIMIT 1;
END$$

DROP PROCEDURE IF EXISTS `sp_RegisterUser`$$
CREATE PROCEDURE `sp_RegisterUser` (IN `p_Alias` VARCHAR(30), IN `p_Password` VARCHAR(255))
BEGIN
    IF p_Alias IS NULL OR TRIM(p_Alias) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alias invalide.';
    END IF;

    IF EXISTS (SELECT 1 FROM Users WHERE TRIM(Alias) = TRIM(p_Alias) LIMIT 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet alias est deja utilise.';
    ELSE
        INSERT INTO Users (Alias, Password, Role, Gold, Silver, Bronze)
        VALUES (TRIM(p_Alias), p_Password, 'Player', 1000, 1000, 1000);
    END IF;
END$$

DELIMITER ;