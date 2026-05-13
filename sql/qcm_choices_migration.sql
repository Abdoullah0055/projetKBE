-- ============================================================
-- Migration : Ajout des mauvaises réponses QCM (4 choix)
-- Date : 2026-04-28
-- Description : Ajoute 3 colonnes WrongAnswer à la table Riddles
--   pour le système QCM à 4 choix (1 bonne + 3 mauvaises).
--   Les mauvaises réponses des 38 énigmes existantes sont
--   peuplées avec des réponses d'énigmes de la même catégorie.
-- ============================================================

-- Étape 1 : Ajouter les 3 colonnes
ALTER TABLE Riddles
  ADD COLUMN WrongAnswer1 varchar(255) NOT NULL DEFAULT '' AFTER AnswerText,
  ADD COLUMN WrongAnswer2 varchar(255) NOT NULL DEFAULT '' AFTER WrongAnswer1,
  ADD COLUMN WrongAnswer3 varchar(255) NOT NULL DEFAULT '' AFTER WrongAnswer2;

-- Étape 2 : Peupler les mauvaises réponses pour les énigmes existantes

-- Catégorie 1 : Magie (RiddleId 8-14)
UPDATE Riddles SET WrongAnswer1 = 'Fireball', WrongAnswer2 = 'Holy Light', WrongAnswer3 = 'Ice Spike' WHERE RiddleId = 8;
UPDATE Riddles SET WrongAnswer1 = 'Holy Light', WrongAnswer2 = 'Ice Spike', WrongAnswer3 = 'Wind Slash' WHERE RiddleId = 9;
UPDATE Riddles SET WrongAnswer1 = 'Fireball', WrongAnswer2 = 'Ice Spike', WrongAnswer3 = 'Lightning Bolt' WHERE RiddleId = 10;
UPDATE Riddles SET WrongAnswer1 = 'Wind Slash', WrongAnswer2 = 'Lightning Bolt', WrongAnswer3 = 'Earthquake' WHERE RiddleId = 11;
UPDATE Riddles SET WrongAnswer1 = 'Ice Spike', WrongAnswer2 = 'Lightning Bolt', WrongAnswer3 = 'Tempête des Sept Éclairs' WHERE RiddleId = 12;
UPDATE Riddles SET WrongAnswer1 = 'Earthquake', WrongAnswer2 = 'Fireball', WrongAnswer3 = 'Holy Light' WHERE RiddleId = 13;
UPDATE Riddles SET WrongAnswer1 = 'Lightning Bolt', WrongAnswer2 = 'Wind Slash', WrongAnswer3 = 'Ice Spike' WHERE RiddleId = 14;

-- Catégorie 2 : Potions (RiddleId 6,7,15-20)
UPDATE Riddles SET WrongAnswer1 = 'Small Health Potion', WrongAnswer2 = 'Antidote', WrongAnswer3 = 'Medium Health Potion' WHERE RiddleId = 6;
UPDATE Riddles SET WrongAnswer1 = 'Mana Potion', WrongAnswer2 = 'Strength Potion', WrongAnswer3 = 'Mega Mana Potion' WHERE RiddleId = 7;
UPDATE Riddles SET WrongAnswer1 = 'Antidote', WrongAnswer2 = 'Medium Health Potion', WrongAnswer3 = 'Mana Potion' WHERE RiddleId = 15;
UPDATE Riddles SET WrongAnswer1 = 'Small Health Potion', WrongAnswer2 = 'Medium Health Potion', WrongAnswer3 = 'Strength Potion' WHERE RiddleId = 16;
UPDATE Riddles SET WrongAnswer1 = 'Small Health Potion', WrongAnswer2 = 'Mana Potion', WrongAnswer3 = 'Strength Potion' WHERE RiddleId = 17;
UPDATE Riddles SET WrongAnswer1 = 'Medium Health Potion', WrongAnswer2 = 'Strength Potion', WrongAnswer3 = 'Mega Mana Potion' WHERE RiddleId = 18;
UPDATE Riddles SET WrongAnswer1 = 'Mana Potion', WrongAnswer2 = 'Mega Mana Potion', WrongAnswer3 = 'Medium Health Potion' WHERE RiddleId = 19;
UPDATE Riddles SET WrongAnswer1 = 'Strength Potion', WrongAnswer2 = 'Mana Potion', WrongAnswer3 = 'Medium Health Potion' WHERE RiddleId = 20;

-- Catégorie 3 : Armes (RiddleId 1-3,21-26)
UPDATE Riddles SET WrongAnswer1 = 'Basic Sword', WrongAnswer2 = 'Dagger', WrongAnswer3 = 'Knight Blade' WHERE RiddleId = 1;
UPDATE Riddles SET WrongAnswer1 = 'Hunter Bow', WrongAnswer2 = 'War Axe', WrongAnswer3 = 'Dragon Slayer' WHERE RiddleId = 2;
UPDATE Riddles SET WrongAnswer1 = 'Basic Sword', WrongAnswer2 = 'Dagger', WrongAnswer3 = 'War Axe' WHERE RiddleId = 3;
UPDATE Riddles SET WrongAnswer1 = 'Dagger', WrongAnswer2 = 'Knight Blade', WrongAnswer3 = 'Hunter Bow' WHERE RiddleId = 21;
UPDATE Riddles SET WrongAnswer1 = 'Basic Sword', WrongAnswer2 = 'Knight Blade', WrongAnswer3 = 'War Axe' WHERE RiddleId = 22;
UPDATE Riddles SET WrongAnswer1 = 'Basic Sword', WrongAnswer2 = 'Dagger', WrongAnswer3 = 'Dragon Slayer' WHERE RiddleId = 23;
UPDATE Riddles SET WrongAnswer1 = 'Knight Blade', WrongAnswer2 = 'War Axe', WrongAnswer3 = 'Dragon Slayer' WHERE RiddleId = 24;
UPDATE Riddles SET WrongAnswer1 = 'Hunter Bow', WrongAnswer2 = 'Dragon Slayer', WrongAnswer3 = 'Knight Blade' WHERE RiddleId = 25;
UPDATE Riddles SET WrongAnswer1 = 'War Axe', WrongAnswer2 = 'Hunter Bow', WrongAnswer3 = 'Knight Blade' WHERE RiddleId = 26;

-- Catégorie 4 : Armures (RiddleId 4,5,27-32)
UPDATE Riddles SET WrongAnswer1 = 'Leather Armor', WrongAnswer2 = 'Traveler Vest', WrongAnswer3 = 'Chainmail' WHERE RiddleId = 4;
UPDATE Riddles SET WrongAnswer1 = 'Mage Robe', WrongAnswer2 = 'Shadow Cloak', WrongAnswer3 = 'Dragon Scale Armor' WHERE RiddleId = 5;
UPDATE Riddles SET WrongAnswer1 = 'Traveler Vest', WrongAnswer2 = 'Chainmail', WrongAnswer3 = 'Mage Robe' WHERE RiddleId = 27;
UPDATE Riddles SET WrongAnswer1 = 'Leather Armor', WrongAnswer2 = 'Chainmail', WrongAnswer3 = 'Shadow Cloak' WHERE RiddleId = 28;
UPDATE Riddles SET WrongAnswer1 = 'Leather Armor', WrongAnswer2 = 'Traveler Vest', WrongAnswer3 = 'Dragon Scale Armor' WHERE RiddleId = 29;
UPDATE Riddles SET WrongAnswer1 = 'Chainmail', WrongAnswer2 = 'Shadow Cloak', WrongAnswer3 = 'Dragon Scale Armor' WHERE RiddleId = 30;
UPDATE Riddles SET WrongAnswer1 = 'Mage Robe', WrongAnswer2 = 'Dragon Scale Armor', WrongAnswer3 = 'Chainmail' WHERE RiddleId = 31;
UPDATE Riddles SET WrongAnswer1 = 'Shadow Cloak', WrongAnswer2 = 'Mage Robe', WrongAnswer3 = 'Chainmail' WHERE RiddleId = 32;

-- Catégorie 5 : Autres (RiddleId 33-38)
UPDATE Riddles SET WrongAnswer1 = 'France', WrongAnswer2 = 'Croisés', WrongAnswer3 = 'Mongols' WHERE RiddleId = 33;
UPDATE Riddles SET WrongAnswer1 = 'Vikings', WrongAnswer2 = 'Croisés', WrongAnswer3 = 'Constantinople' WHERE RiddleId = 34;
UPDATE Riddles SET WrongAnswer1 = 'Vikings', WrongAnswer2 = 'Constantinople', WrongAnswer3 = 'Mongols' WHERE RiddleId = 35;
UPDATE Riddles SET WrongAnswer1 = 'France', WrongAnswer2 = 'Croisés', WrongAnswer3 = 'Espagne' WHERE RiddleId = 36;
UPDATE Riddles SET WrongAnswer1 = 'Vikings', WrongAnswer2 = 'Croisés', WrongAnswer3 = 'Espagne' WHERE RiddleId = 37;
UPDATE Riddles SET WrongAnswer1 = 'Mongols', WrongAnswer2 = 'Constantinople', WrongAnswer3 = 'France' WHERE RiddleId = 38;
