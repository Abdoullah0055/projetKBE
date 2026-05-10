-- 1. Ajout des colonnes nécessaires
ALTER TABLE Users ADD COLUMN FundsGivenCount INT DEFAULT 0;
ALTER TABLE Riddles ADD COLUMN RiddleType VARCHAR(50) DEFAULT 'MultipleChoice';

-- 1b. Update existing rows that had the old default 'Text'
UPDATE Riddles SET RiddleType = 'MultipleChoice' WHERE RiddleType = 'Text' OR RiddleType IS NULL;

-- 1c. Add Comment column to Reviews if not exists
ALTER TABLE Reviews ADD COLUMN Comment TEXT DEFAULT NULL;

-- 2. Table pour la réinitialisation de mot de passe
CREATE TABLE IF NOT EXISTS PasswordResets (
    Email VARCHAR(190) NOT NULL,
    Token VARCHAR(255) NOT NULL,
    ExpiresAt DATETIME NOT NULL,
    PRIMARY KEY (Token),
    INDEX (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Mise à jour de sp_RegisterUser pour 3 paramètres
DROP PROCEDURE IF EXISTS sp_RegisterUser;
DELIMITER //
CREATE PROCEDURE sp_RegisterUser(
    IN p_Alias VARCHAR(30),
    IN p_Password VARCHAR(255),
    IN p_Email VARCHAR(190)
)
BEGIN
    IF EXISTS (SELECT 1 FROM Users WHERE Alias = p_Alias) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet alias est deja utilise.';
    ELSEIF EXISTS (SELECT 1 FROM Users WHERE Email = p_Email) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ce courriel est deja utilise.';
    ELSE
        INSERT INTO Users (Alias, Password, Email, Role, Gold, Silver, Bronze, CurrentHP, MaxHP)
        VALUES (p_Alias, p_Password, p_Email, 'Player', 1000, 1000, 1000, 100, 100);
    END IF;
END //
DELIMITER ;