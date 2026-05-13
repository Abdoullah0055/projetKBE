-- Fix: Riddles 1-8 Difficile had RewardGold=50 instead of 10 per spec
UPDATE Riddles SET RewardGold = 10 WHERE RiddleId BETWEEN 1 AND 8 AND Difficulty = 'Difficile';
