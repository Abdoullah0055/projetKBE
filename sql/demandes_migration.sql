CREATE TABLE IF NOT EXISTS Demandes (
    DemandeId INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_demandes_user
        FOREIGN KEY (UserId) REFERENCES Users(UserId)
        ON DELETE CASCADE
);

CREATE INDEX idx_demandes_userid ON Demandes(UserId);
CREATE INDEX idx_demandes_createdat ON Demandes(CreatedAt);
