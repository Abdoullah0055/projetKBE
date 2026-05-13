-- Migration: add rarity support for Items.

SET @items_table_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Items'
);

SET @rarity_col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Items'
    AND COLUMN_NAME = 'Rarity'
);

SET @sql := IF(
  @items_table_exists = 1 AND @rarity_col_exists = 0,
  'ALTER TABLE Items ADD COLUMN Rarity ENUM(''Commun'',''Rare'',''Épique'',''Légendaire'',''Mythique'') NOT NULL DEFAULT ''Commun'' AFTER ItemTypeId',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @can_update_rarity := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'Items'
    AND COLUMN_NAME = 'Rarity'
);

SET @sql := IF(
  @items_table_exists = 1 AND @can_update_rarity = 1,
  'UPDATE Items
   SET Rarity = CASE ItemId
     WHEN 2 THEN ''Rare''
     WHEN 4 THEN ''Rare''
     WHEN 5 THEN ''Épique''
     WHEN 6 THEN ''Rare''
     WHEN 7 THEN ''Épique''
     WHEN 8 THEN ''Épique''
     WHEN 9 THEN ''Épique''
     WHEN 10 THEN ''Légendaire''
     WHEN 12 THEN ''Rare''
     WHEN 13 THEN ''Rare''
     WHEN 14 THEN ''Légendaire''
     WHEN 15 THEN ''Rare''
     WHEN 16 THEN ''Épique''
     WHEN 17 THEN ''Épique''
     WHEN 18 THEN ''Légendaire''
     WHEN 20 THEN ''Mythique''
     WHEN 25 THEN ''Rare''
     WHEN 26 THEN ''Rare''
     WHEN 27 THEN ''Rare''
     WHEN 28 THEN ''Rare''
     WHEN 30 THEN ''Rare''
     WHEN 31 THEN ''Rare''
     WHEN 32 THEN ''Rare''
     WHEN 33 THEN ''Épique''
     WHEN 34 THEN ''Épique''
     WHEN 35 THEN ''Rare''
     WHEN 36 THEN ''Épique''
     WHEN 37 THEN ''Épique''
     WHEN 38 THEN ''Légendaire''
     WHEN 39 THEN ''Rare''
     WHEN 40 THEN ''Épique''
     ELSE Rarity
   END
   WHERE ItemId IN (2,4,5,6,7,8,9,10,12,13,14,15,16,17,18,20,25,26,27,28,30,31,32,33,34,35,36,37,38,39,40)
     AND (Rarity IS NULL OR Rarity = '''' OR Rarity = ''Commun'')',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
  @items_table_exists = 1 AND @can_update_rarity = 1,
  'UPDATE Items
   SET Rarity = CASE
     WHEN Rarity = ''Epique'' THEN ''Épique''
     WHEN Rarity = ''Legendaire'' THEN ''Légendaire''
     ELSE Rarity
   END',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
