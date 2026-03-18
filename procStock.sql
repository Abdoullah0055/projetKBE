DELIMITER //


-- =========================
-- US-01 : Création de compte
-- =========================
CREATE PROCEDURE sp_RegisterUser(
    IN p_Alias VARCHAR(30),
    IN p_Password VARCHAR(255)
)
BEGIN
    -- On force la collation pour la comparaison de l'alias
    IF EXISTS (SELECT 1 FROM Users WHERE Alias COLLATE utf8mb4_unicode_ci = p_Alias COLLATE utf8mb4_unicode_ci) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet alias est déjà utilisé.';
        
    ELSE
        INSERT INTO Users (Alias, Password, Role, Gold, Silver, Bronze)
        VALUES (p_Alias, p_Password, 'Joueur', 1000, 1000, 1000); 
    END IF;
END //

-- =========================
-- US-02 : Connexion 
-- =========================
CREATE PROCEDURE sp_GetUserByAlias(
    IN p_Alias VARCHAR(30)
)   
BEGIN
    -- On force la collation ici aussi
    SELECT UserId, Alias, Password, Role, Gold, Silver, Bronze
    FROM Users
    WHERE Alias COLLATE utf8mb4_unicode_ci = p_Alias COLLATE utf8mb4_unicode_ci;
END //

DELIMITER ;