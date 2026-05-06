ALTER TABLE Demandes
    ADD COLUMN Status VARCHAR(16) NOT NULL DEFAULT 'Pending',
    ADD COLUMN ProcessedAt DATETIME NULL;

UPDATE Demandes
SET Status = 'Pending'
WHERE Status IS NULL OR Status = '';
