-- Fix potion heal values and prices
-- Small Health/Mana: 3 PV, 5 Bronze
-- Medium Health/Mana: 5 PV, 5 Silver
-- Large Health/Mana: 7 PV, 10 Silver

UPDATE PotionProperties SET EffectValue = 3 WHERE ItemId IN (46, 47);
UPDATE PotionProperties SET EffectValue = 5 WHERE ItemId IN (48, 49);
UPDATE PotionProperties SET EffectValue = 7 WHERE ItemId IN (50, 51);

UPDATE Items SET PriceGold = 0, PriceSilver = 0, PriceBronze = 5 WHERE ItemId IN (46, 47);
UPDATE Items SET PriceGold = 0, PriceSilver = 5, PriceBronze = 0 WHERE ItemId IN (48, 49);
UPDATE Items SET PriceGold = 0, PriceSilver = 10, PriceBronze = 0 WHERE ItemId IN (50, 51);
