-- Optional test data for new riddle types.

INSERT INTO Riddles (
  QuestionText,
  AnswerText,
  WrongAnswer1,
  WrongAnswer2,
  WrongAnswer3,
  RiddleType,
  HintText,
  Difficulty,
  RiddleCategoryId,
  RewardGold,
  RewardSilver,
  RewardBronze,
  IsActive
)
VALUES
(
  'Le soleil se leve a l\'est. Vrai ou faux ?',
  'Vrai',
  'Faux',
  '',
  '',
  'vrai_faux',
  'Pense a l\'orientation terrestre.',
  'Facile',
  5,
  0,
  0,
  10,
  1
),
(
  'Quel est le metal le plus utilise pour fabriquer des armures ?',
  'acier',
  '',
  '',
  '',
  'phrase_courte',
  'Tu le vois partout en forge medievale.',
  'Moyenne',
  4,
  0,
  10,
  0,
  1
);
