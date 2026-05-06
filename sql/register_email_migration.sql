DELIMITER //

DROP PROCEDURE IF EXISTS sp_RegisterUser //
CREATE PROCEDURE sp_RegisterUser(
    IN p_Alias VARCHAR(30),
    IN p_Password VARCHAR(255),
    IN p_Email VARCHAR(190)
)
BEGIN
    DECLARE v_alias VARCHAR(30);
    DECLARE v_email VARCHAR(190);

    SET v_alias = TRIM(CONVERT(p_Alias USING utf8mb4)) COLLATE utf8mb4_unicode_ci;
    SET v_email = LOWER(TRIM(CONVERT(p_Email USING utf8mb4))) COLLATE utf8mb4_unicode_ci;

    IF v_alias IS NULL OR v_alias = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Alias invalide.';
    END IF;

    IF p_Password IS NULL OR TRIM(p_Password) = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mot de passe invalide.';
    END IF;

    IF v_email IS NULL OR v_email = '' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Email invalide.';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM Users
        WHERE TRIM(Alias) COLLATE utf8mb4_unicode_ci = v_alias COLLATE utf8mb4_unicode_ci
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet alias est deja utilise.';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM Users
        WHERE LOWER(TRIM(Email)) COLLATE utf8mb4_unicode_ci = v_email COLLATE utf8mb4_unicode_ci
        LIMIT 1
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet email est deja utilise.';
    END IF;

    INSERT INTO Users (Alias, Email, Password, Role, Gold, Silver, Bronze, CurrentHP, MaxHP, FundsGivenCount)
    VALUES (v_alias, v_email, p_Password, 'Player', 1000, 1000, 1000, 100, 100, 0);
END //

DELIMITER ;
