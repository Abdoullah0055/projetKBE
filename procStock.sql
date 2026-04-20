DELIMITER //

-- MySQL ne supporte pas CREATE OR ALTER PROCEDURE.
-- On reproduit ce comportement avec DROP IF EXISTS + CREATE.
DROP PROCEDURE IF EXISTS sp_RegisterUser //
CREATE PROCEDURE sp_RegisterUser(
    IN p_Alias VARCHAR(30),
    IN p_Password VARCHAR(255)
)
BEGIN
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
END //

DROP PROCEDURE IF EXISTS sp_GetUserByAlias //
CREATE PROCEDURE sp_GetUserByAlias(
    IN p_Alias VARCHAR(30)
)
BEGIN
    DECLARE v_alias VARCHAR(30);

    SET v_alias = TRIM(CONVERT(p_Alias USING utf8mb4)) COLLATE utf8mb4_unicode_ci;

    SELECT UserId, Alias, Password, Role, Gold, Silver, Bronze
    FROM Users
    WHERE TRIM(Alias) COLLATE utf8mb4_unicode_ci = v_alias COLLATE utf8mb4_unicode_ci
    LIMIT 1;
END //

DROP PROCEDURE IF EXISTS sp_DeleteUserAccount //
CREATE PROCEDURE sp_DeleteUserAccount(
    IN p_UserId INT
)
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
END //

DELIMITER ;
