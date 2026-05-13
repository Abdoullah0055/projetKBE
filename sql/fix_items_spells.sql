INSERT INTO Items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, Rarity, IsActive)
VALUES ('Ice Spike Tome', 'Grimoire de projectiles glacés.', 90, 40, 10, 8, 4, 'Rare', 1);
INSERT INTO MagicSpellProperties (ItemId, SpellDamage, ManaCost, ElementType, RequiredLevel, CooldownSeconds)
VALUES (LAST_INSERT_ID(), 35, 18, 'Ice', 2, 3);

INSERT INTO Items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, Rarity, IsActive)
VALUES ('Wind Slash Scroll', 'Parchemin de lames de vent.', 70, 25, 5, 10, 4, 'Commun', 1);
INSERT INTO MagicSpellProperties (ItemId, SpellDamage, ManaCost, ElementType, RequiredLevel, CooldownSeconds)
VALUES (LAST_INSERT_ID(), 25, 12, 'Wind', 1, 2);
