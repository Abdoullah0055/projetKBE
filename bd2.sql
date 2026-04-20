-- ========================================================
-- 1) Ajouter la colonne RewardItemId dans riddles
-- ========================================================
ALTER TABLE riddles
ADD COLUMN RewardItemId INT NULL AFTER RewardBronze;

ALTER TABLE riddles
ADD CONSTRAINT FK_Riddles_RewardItem
FOREIGN KEY (RewardItemId) REFERENCES items(ItemId)
ON DELETE SET NULL
ON UPDATE CASCADE;


-- ========================================================
-- 2) Ajouter les 8 items fantasy dans items
-- ========================================================
INSERT INTO items
(Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, IsActive)
VALUES
('Lame du Corbeau Noir',
 'Épée silencieuse forgée dans un métal sombre. On raconte que son porteur frappe avant même que l’ennemi remarque l’ombre derrière lui.',
 500, 200, 100, 1, 1, 1),

('Marteau des Ancêtres',
 'Arme lourde transmise de génération en génération. Chaque coup résonne comme les tambours d’une guerre ancienne.',
 650, 240, 120, 1, 1, 1),

('Arc de Brume-Lune',
 'Un arc fin gravé de symboles argentés. Les flèches tirées semblent disparaître dans la brume avant de retrouver leur cible.',
 580, 220, 110, 1, 1, 1),

('Cuirasse du Bastion Gris',
 'Armure robuste portée autrefois par les gardiens du nord. Elle demeure solide même sous mille assauts.',
 700, 260, 130, 1, 2, 1),

('Voile d’Acier Sacré',
 'Protection bénie par les prêtres du royaume. Les ténèbres semblent reculer lorsqu’elle approche.',
 760, 280, 140, 1, 2, 1),

('Élixir de l’Aube Claire',
 'Potion lumineuse conservée dans une fiole dorée. Elle ranime les forces comme un nouveau matin.',
 120, 50, 25, 10, 3, 1),

('Breuvage du Sang-Froid',
 'Liquide sombre aux reflets bleutés. Il calme le cœur et ralentit la peur en plein combat.',
 130, 55, 30, 10, 3, 1),

('Tempête des Sept Éclairs',
 'Sort ancien inscrit dans un grimoire interdit. Il invoque plusieurs traits de foudre tombant en cascade.',
 820, 350, 180, 1, 4, 1);


-- ========================================================
-- 3) Ajouter les propriétés spécifiques
-- ========================================================

-- Armes
INSERT INTO weaponproperties
(ItemId, DamageMin, DamageMax, Durability, RequiredLevel, AttackSpeed)
VALUES
((SELECT ItemId FROM items WHERE Name = 'Lame du Corbeau Noir'), 45, 70, 180, 10, 1.35),
((SELECT ItemId FROM items WHERE Name = 'Marteau des Ancêtres'), 65, 105, 260, 12, 0.75),
((SELECT ItemId FROM items WHERE Name = 'Arc de Brume-Lune'), 40, 78, 170, 11, 1.45);

-- Armures
INSERT INTO armorproperties
(ItemId, Defense, Durability, RequiredLevel)
VALUES
((SELECT ItemId FROM items WHERE Name = 'Cuirasse du Bastion Gris'), 52, 280, 10),
((SELECT ItemId FROM items WHERE Name = 'Voile d’Acier Sacré'), 60, 230, 12);

-- Potions
INSERT INTO potionproperties
(ItemId, EffectType, EffectValue, DurationSeconds)
VALUES
((SELECT ItemId FROM items WHERE Name = 'Élixir de l’Aube Claire'), 'Heal', 120, NULL),
((SELECT ItemId FROM items WHERE Name = 'Breuvage du Sang-Froid'), 'Calm', 1, 15);

-- Sort
INSERT INTO magicspellproperties
(ItemId, SpellDamage, ManaCost, ElementType, RequiredLevel, CooldownSeconds)
VALUES
((SELECT ItemId FROM items WHERE Name = 'Tempête des Sept Éclairs'), 140, 45, 'Lightning', 15, 12);


-- ========================================================
-- 4) Lier les 8 énigmes spéciales à leurs items
-- ========================================================
UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = 'Lame du Corbeau Noir')
WHERE RiddleId = 1;

UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = 'Marteau des Ancêtres')
WHERE RiddleId = 2;

UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = 'Arc de Brume-Lune')
WHERE RiddleId = 3;

UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = 'Cuirasse du Bastion Gris')
WHERE RiddleId = 4;

UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = "Voile d'Acier Sacré")
WHERE RiddleId = 5;

UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = "Élixir de l'Aube Claire")
WHERE RiddleId = 6;

UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = 'Breuvage du Sang-Froid')
WHERE RiddleId = 7;

UPDATE riddles
SET RewardItemId = (SELECT ItemId FROM items WHERE Name = 'Tempête des Sept Éclairs')
WHERE RiddleId = 8;


-- ========================================================
-- 5) Procédure : répondre à une énigme et recevoir l’item
-- ========================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS sp_SubmitRiddleAnswer $$
CREATE PROCEDURE sp_SubmitRiddleAnswer (
    IN p_UserId INT,
    IN p_RiddleId INT,
    IN p_GivenAnswer VARCHAR(255)
)
proc: BEGIN
    DECLARE v_AnswerText VARCHAR(255);
    DECLARE v_IsSuccess TINYINT DEFAULT 0;
    DECLARE v_RewardGold INT DEFAULT 0;
    DECLARE v_RewardSilver INT DEFAULT 0;
    DECLARE v_RewardBronze INT DEFAULT 0;
    DECLARE v_RewardItemId INT DEFAULT NULL;
    DECLARE v_CategoryId INT DEFAULT NULL;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_UserId IS NULL OR p_UserId <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Utilisateur invalide.';
    END IF;

    IF p_RiddleId IS NULL OR p_RiddleId <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Énigme invalide.';
    END IF;

    START TRANSACTION;

    SELECT AnswerText, RewardGold, RewardSilver, RewardBronze, RewardItemId, RiddleCategoryId
    INTO v_AnswerText, v_RewardGold, v_RewardSilver, v_RewardBronze, v_RewardItemId, v_CategoryId
    FROM riddles
    WHERE RiddleId = p_RiddleId
      AND IsActive = 1
    FOR UPDATE;

    IF v_AnswerText IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Énigme introuvable ou inactive.';
    END IF;

    IF TRIM(LOWER(p_GivenAnswer)) = TRIM(LOWER(v_AnswerText)) THEN
        SET v_IsSuccess = 1;
    END IF;

    INSERT INTO userriddles (UserId, RiddleId, GivenAnswer, IsSuccess)
    VALUES (p_UserId, p_RiddleId, p_GivenAnswer, v_IsSuccess);

    IF v_IsSuccess = 1 THEN

        UPDATE users
        SET Gold = Gold + v_RewardGold,
            Silver = Silver + v_RewardSilver,
            Bronze = Bronze + v_RewardBronze
        WHERE UserId = p_UserId;

        IF v_RewardItemId IS NOT NULL THEN
            INSERT INTO inventory (UserId, ItemId, Quantity)
            VALUES (p_UserId, v_RewardItemId, 1)
            ON DUPLICATE KEY UPDATE Quantity = Quantity + 1;
        END IF;

        INSERT INTO userriddlestats (UserId, SolvedCount, FailedCount, MagicSolvedCount)
        VALUES (
            p_UserId,
            1,
            0,
            CASE WHEN v_CategoryId = 1 THEN 1 ELSE 0 END
        )
        ON DUPLICATE KEY UPDATE
            SolvedCount = SolvedCount + 1,
            MagicSolvedCount = MagicSolvedCount + CASE WHEN v_CategoryId = 1 THEN 1 ELSE 0 END;

    ELSE

        INSERT INTO userriddlestats (UserId, SolvedCount, FailedCount, MagicSolvedCount)
        VALUES (p_UserId, 0, 1, 0)
        ON DUPLICATE KEY UPDATE
            FailedCount = FailedCount + 1;

    END IF;

    COMMIT;

    SELECT
        v_IsSuccess AS IsSuccess,
        v_RewardGold AS RewardGold,
        v_RewardSilver AS RewardSilver,
        v_RewardBronze AS RewardBronze,
        v_RewardItemId AS RewardItemId;
END $$

DELIMITER ;