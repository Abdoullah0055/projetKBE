
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riddlecategories` (
  `RiddleCategoryId` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`RiddleCategoryId`),
  UNIQUE KEY `UQ_RiddleCategories_Name` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `riddlecategories` VALUES (3,'Armes'),(4,'Armures'),(5,'Autres'),(1,'Magie'),(2,'Potions');
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riddles` (
  `RiddleId` int NOT NULL AUTO_INCREMENT,
  `QuestionText` text NOT NULL,
  `AnswerText` varchar(255) NOT NULL,
  `HintText` text,
  `Difficulty` varchar(20) NOT NULL,
  `RiddleCategoryId` int NOT NULL,
  `RewardGold` int NOT NULL DEFAULT '0',
  `RewardSilver` int NOT NULL DEFAULT '0',
  `RewardBronze` int NOT NULL DEFAULT '0',
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`RiddleId`),
  KEY `FK_Riddles_RiddleCategories` (`RiddleCategoryId`),
  CONSTRAINT `FK_Riddles_RiddleCategories` FOREIGN KEY (`RiddleCategoryId`) REFERENCES `riddlecategories` (`RiddleCategoryId`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `CHK_Riddles_Difficulty` CHECK ((`Difficulty` in (_utf8mb4'Facile',_utf8mb4'Moyenne',_utf8mb4'Difficile')))
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `riddles` VALUES (1,'Je suis lié à un oiseau obscur et je frappe dans le silence avant d’être vu. Qui suis-je ?','Lame du Corbeau Noir','Cherche parmi les armes sombres et discrètes.','Difficile',3,50,0,0,1),(2,'Je porte la mémoire des anciens et chacun de mes coups sonne comme un tambour. Qui suis-je ?','Marteau des Ancêtres','Une arme lourde héritée du passé.','Difficile',3,50,0,0,1),(3,'Je vise de loin, je me cache dans la brume, puis j’atteins toujours ma cible. Qui suis-je ?','Arc de Brume-Lune','Une arme de précision liée au brouillard.','Difficile',3,50,0,0,1),(4,'Je protège comme une forteresse grise et je résiste à d’innombrables coups. Qui suis-je ?','Cuirasse du Bastion Gris','Cherche une protection solide comme un mur.','Difficile',4,50,0,0,1),(5,'Je suis de métal saint, et l’ombre fuit quand j’avance. Qui suis-je ?','Voile d’Acier Sacré','Une armure bénie contre les ténèbres.','Difficile',4,50,0,0,1),(6,'Je viens avec le matin et je rends la vigueur à celui qui me boit. Qui suis-je ?','Élixir de l’Aube Claire','Une potion liée à la lumière du jour.','Difficile',2,50,0,0,1),(7,'Je refroidis les nerfs et fais taire la peur dans la bataille. Qui suis-je ?','Breuvage du Sang-Froid','Une potion qui calme le cœur.','Difficile',2,50,0,0,1),(8,'Je suis une colère venue du ciel et je frappe sept fois en lumière. Qui suis-je ?','Tempête des Sept Éclairs','Un sort ancien lié à la foudre.','Difficile',1,50,0,0,1),(9,'Je suis une petite flamme lancée par la main d’un mage. Qui suis-je ?','Fireball','Va voir les sorts de feu les plus simples.','Facile',1,0,0,10,1),(10,'Je suis une lumière sacrée qui chasse l’ombre. Qui suis-je ?','Holy Light','Cherche un sort lumineux et béni.','Facile',1,0,0,10,1),(11,'Je suis un projectile glacé qui transperce l’air. Qui suis-je ?','Ice Spike','Regarde les sorts liés à la glace.','Moyenne',1,0,10,0,1),(12,'Je suis une lame invisible faite de vent rapide. Qui suis-je ?','Wind Slash','Cherche un sort rapide associé à l’air.','Moyenne',1,0,10,0,1),(13,'Je tombe du ciel avec une force électrique redoutable. Qui suis-je ?','Lightning Bolt','Va dans les sorts de foudre.','Difficile',1,10,0,0,1),(14,'Je fais trembler le sol sous les pieds de tous. Qui suis-je ?','Earthquake','Cherche un sort qui frappe la terre entière.','Difficile',1,10,0,0,1),(15,'Je rends un peu de vie après un combat. Qui suis-je ?','Small Health Potion','Va voir les petites potions de soin.','Facile',2,0,0,10,1),(16,'Je retire le poison du corps de celui qui me boit. Qui suis-je ?','Antidote','Cherche une potion contre un mauvais effet.','Facile',2,0,0,10,1),(17,'Je rends une quantité moyenne de vie au joueur. Qui suis-je ?','Medium Health Potion','Va voir les potions de soin intermédiaires.','Moyenne',2,0,10,0,1),(18,'Je rends du mana pour continuer à lancer des sorts. Qui suis-je ?','Mana Potion','Cherche une potion liée à la magie.','Moyenne',2,0,10,0,1),(19,'Je donne plus de force pendant un moment. Qui suis-je ?','Strength Potion','Va voir les potions qui améliorent les statistiques.','Difficile',2,10,0,0,1),(20,'Je rends énormément de mana à celui qui me boit. Qui suis-je ?','Mega Mana Potion','Cherche la version la plus puissante d’une potion magique.','Difficile',2,10,0,0,1),(21,'Je suis une épée simple pensée pour débuter. Qui suis-je ?','Basic Sword','Regarde les armes les plus de base.','Facile',3,0,0,10,1),(22,'Je suis une petite lame légère et rapide. Qui suis-je ?','Dagger','Cherche une arme courte et discrète.','Facile',3,0,0,10,1),(23,'Je suis une épée robuste portée par les chevaliers. Qui suis-je ?','Knight Blade','Va voir les armes de chevalier.','Moyenne',3,0,10,0,1),(24,'Je suis une arme précise pour attaquer à distance. Qui suis-je ?','Hunter Bow','Cherche une arme qui lance des projectiles.','Moyenne',3,0,10,0,1),(25,'Je suis une hache lourde faite pour frapper fort. Qui suis-je ?','War Axe','Va voir les armes les plus massives.','Difficile',3,10,0,0,1),(26,'Je suis une grande épée créée pour tuer les monstres. Qui suis-je ?','Dragon Slayer','Cherche une arme légendaire très puissante.','Difficile',3,10,0,0,1),(27,'Je suis une armure légère en cuir. Qui suis-je ?','Leather Armor','Regarde les protections les plus simples.','Facile',4,0,0,10,1),(28,'Je suis une veste légère portée par les aventuriers. Qui suis-je ?','Traveler Vest','Cherche une protection modeste de voyage.','Facile',4,0,0,10,1),(29,'Je suis une armure faite de mailles de métal. Qui suis-je ?','Chainmail','Va voir les armures intermédiaires.','Moyenne',4,0,10,0,1),(30,'Je suis une robe conçue pour ceux qui utilisent la magie. Qui suis-je ?','Mage Robe','Cherche une tenue liée aux mages.','Moyenne',4,0,10,0,1),(31,'Je suis une cape sombre qui protège avec discrétion. Qui suis-je ?','Shadow Cloak','Va voir les protections furtives.','Difficile',4,10,0,0,1),(32,'Je suis une armure forgée à partir d’écailles rares. Qui suis-je ?','Dragon Scale Armor','Cherche l’une des protections les plus puissantes.','Difficile',4,10,0,0,1),(33,'Je suis un peuple du nord connu pour mes drakkars, mes raids et mes guerriers redoutés. Qui suis-je ?','Vikings','Peuple scandinave célèbre du Moyen Âge.','Facile',5,0,0,10,1),(34,'Je suis un grand royaume médiéval souvent associé aux rois, aux châteaux et aux chevaliers. Qui suis-je ?','France','Un royaume très puissant en Europe médiévale.','Facile',5,0,0,10,1),(35,'Nous sommes des chevaliers chrétiens partis combattre en Terre sainte. Qui sommes-nous ?','Croisés','Ils participaient aux croisades.','Moyenne',5,0,10,0,1),(36,'Je suis une grande ville souvent vue comme le cœur de l’Empire byzantin. Qui suis-je ?','Constantinople','Aujourd’hui, cette ville porte un autre nom.','Moyenne',5,0,10,0,1),(37,'Je suis un peuple cavalier venu d’Asie qui a bâti un immense empire sous Gengis Khan. Qui suis-je ?','Mongols','Empire nomade très vaste.','Difficile',5,10,0,0,1),(38,'Je suis un royaume chrétien de la péninsule ibérique lié à la Reconquista. Qui suis-je ?','Espagne','Pense à la Reconquista.','Difficile',5,10,0,0,1);
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `userriddles` (
  `UserRiddleId` int NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `RiddleId` int NOT NULL,
  `GivenAnswer` varchar(255) DEFAULT NULL,
  `IsSuccess` tinyint(1) NOT NULL,
  `AnsweredAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserRiddleId`),
  KEY `FK_UserRiddles_Users` (`UserId`),
  KEY `FK_UserRiddles_Riddles` (`RiddleId`),
  CONSTRAINT `FK_UserRiddles_Riddles` FOREIGN KEY (`RiddleId`) REFERENCES `riddles` (`RiddleId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_UserRiddles_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `userriddlestats` (
  `UserId` int NOT NULL,
  `SolvedCount` int NOT NULL DEFAULT '0',
  `FailedCount` int NOT NULL DEFAULT '0',
  `MagicSolvedCount` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserId`),
  CONSTRAINT `FK_UserRiddleStats_Users` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `userriddlestats` VALUES (3,0,0,0);
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
