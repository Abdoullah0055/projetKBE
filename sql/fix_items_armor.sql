INSERT INTO Items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, Rarity, IsActive)
VALUES ('Steel Chestplate', 'Armure de poitrine en acier renforcé.', 30, 50, 0, 10, 2, 'Rare', 1);
INSERT INTO ArmorProperties (ItemId, Defense, Durability, RequiredLevel) VALUES (LAST_INSERT_ID(), 25, 150, 3);

INSERT INTO Items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, Rarity, IsActive)
VALUES ('Iron Helm', 'Casque de fer pour la protection.', 20, 30, 0, 15, 2, 'Commun', 1);
INSERT INTO ArmorProperties (ItemId, Defense, Durability, RequiredLevel) VALUES (LAST_INSERT_ID(), 12, 100, 1);

INSERT INTO Items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, Rarity, IsActive)
VALUES ('Dragon Hide Boots', 'Bottes en peau de dragon, légères et résistantes.', 45, 20, 5, 5, 2, 'Epique', 1);
INSERT INTO ArmorProperties (ItemId, Defense, Durability, RequiredLevel) VALUES (LAST_INSERT_ID(), 30, 200, 5);

