-- Procédures Stockées



CREATE PROCEDURE sp_RegisterUser(
    IN p_Alias VARCHAR(30),
    IN p_Email VARCHAR(254),
    IN p_Password VARCHAR(255)
)
BEGIN
    -- Vérification si l'alias ou l'email existe déjà
    IF EXISTS (SELECT 1 FROM Users WHERE Alias = p_Alias) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet alias est déjà utilisé.';
    ELSEIF EXISTS (SELECT 1 FROM Users WHERE Email = p_Email) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cet email est déjà utilisé.';
    ELSE
        -- Insertion du nouvel utilisateur
        -- Par défaut, le rôle est 'Joueur' et le capital initial est mis à 0 (ou selon vos règles)
        INSERT INTO Users (Alias, Email, Password, Role, Gold, Silver, Bronze)
        VALUES (p_Alias, p_Email, p_Password, 'Joueur', 1000, 1000, 1000); 
    END IF;
END 

