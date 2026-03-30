CREATE TABLE Users (
  UserId   INT AUTO_INCREMENT PRIMARY KEY,
  Alias    VARCHAR(30)  NOT NULL,
  Password VARCHAR(255) NOT NULL,
  Role     VARCHAR(20)  NOT NULL,
  Gold     INT NOT NULL DEFAULT 1000,
  Silver   INT NOT NULL DEFAULT 1000,
  Bronze   INT NOT NULL DEFAULT 1000,

  CONSTRAINT UQ_Users_Alias UNIQUE (Alias),
  CONSTRAINT CHK_Users_Role CHECK (Role IN ('Player', 'Mage', 'Admin')),
  CONSTRAINT CHK_Users_Gold CHECK (Gold >= 0),
  CONSTRAINT CHK_Users_Silver CHECK (Silver >= 0),
  CONSTRAINT CHK_Users_Bronze CHECK (Bronze >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE ItemTypes (
  ItemTypeId INT AUTO_INCREMENT PRIMARY KEY,
  Name       VARCHAR(50) NOT NULL,

  CONSTRAINT UQ_ItemTypes_Name UNIQUE (Name),
  CONSTRAINT CHK_ItemTypes_Name CHECK (Name IN ('Weapon', 'Armor', 'Potion', 'MagicSpell'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE Items (
  ItemId       INT AUTO_INCREMENT PRIMARY KEY,
  Name         VARCHAR(80) NOT NULL,
  Description  TEXT NULL,
  PriceGold    INT NOT NULL DEFAULT 0,
  PriceSilver  INT NOT NULL DEFAULT 0,
  PriceBronze  INT NOT NULL DEFAULT 0,
  Stock        INT NOT NULL DEFAULT 0,
  ItemTypeId   INT NOT NULL,
  IsActive     BOOLEAN NOT NULL DEFAULT TRUE,

  CONSTRAINT UQ_Items_Name UNIQUE (Name),

  CONSTRAINT CHK_Items_Prices CHECK (
    PriceGold >= 0 AND PriceSilver >= 0 AND PriceBronze >= 0
  ),

  CONSTRAINT CHK_Items_Stock CHECK (Stock >= 0),

  CONSTRAINT FK_Items_ItemTypes
    FOREIGN KEY (ItemTypeId) REFERENCES ItemTypes(ItemTypeId)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE WeaponProperties (
  ItemId         INT PRIMARY KEY,
  DamageMin      INT NOT NULL,
  DamageMax      INT NOT NULL,
  Durability     INT NOT NULL DEFAULT 100,
  RequiredLevel  INT NOT NULL DEFAULT 1,
  AttackSpeed    DECIMAL(4,2) NOT NULL DEFAULT 1.00,

  CONSTRAINT CHK_Weapon_Damage CHECK (
    DamageMin >= 0 AND DamageMax >= DamageMin
  ),
  CONSTRAINT CHK_Weapon_Durability CHECK (Durability >= 0),
  CONSTRAINT CHK_Weapon_RequiredLevel CHECK (RequiredLevel >= 1),
  CONSTRAINT CHK_Weapon_AttackSpeed CHECK (AttackSpeed > 0),

  CONSTRAINT FK_WeaponProperties_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE ArmorProperties (
  ItemId         INT PRIMARY KEY,
  Defense        INT NOT NULL,
  Durability     INT NOT NULL DEFAULT 100,
  RequiredLevel  INT NOT NULL DEFAULT 1,

  CONSTRAINT CHK_Armor_Defense CHECK (Defense >= 0),
  CONSTRAINT CHK_Armor_Durability CHECK (Durability >= 0),
  CONSTRAINT CHK_Armor_RequiredLevel CHECK (RequiredLevel >= 1),

  CONSTRAINT FK_ArmorProperties_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE PotionProperties (
  ItemId           INT PRIMARY KEY,
  EffectType       VARCHAR(50) NOT NULL,
  EffectValue      INT NOT NULL,
  DurationSeconds  INT NULL,

  CONSTRAINT CHK_Potion_EffectValue CHECK (EffectValue >= 0),
  CONSTRAINT CHK_Potion_Duration CHECK (
    DurationSeconds IS NULL OR DurationSeconds >= 0
  ),

  CONSTRAINT FK_PotionProperties_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE MagicSpellProperties (
  ItemId          INT PRIMARY KEY,
  SpellDamage     INT NOT NULL DEFAULT 0,
  ManaCost        INT NOT NULL,
  ElementType     VARCHAR(30) NOT NULL,
  RequiredLevel   INT NOT NULL DEFAULT 1,
  CooldownSeconds INT NOT NULL DEFAULT 0,

  CONSTRAINT CHK_Spell_Damage CHECK (SpellDamage >= 0),
  CONSTRAINT CHK_Spell_ManaCost CHECK (ManaCost >= 0),
  CONSTRAINT CHK_Spell_RequiredLevel CHECK (RequiredLevel >= 1),
  CONSTRAINT CHK_Spell_Cooldown CHECK (CooldownSeconds >= 0),

  CONSTRAINT FK_MagicSpellProperties_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE Orders (
  OrderId      INT AUTO_INCREMENT PRIMARY KEY,
  UserId       INT NOT NULL,
  OrderDate    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  TotalGold    INT NOT NULL DEFAULT 0,
  TotalSilver  INT NOT NULL DEFAULT 0,
  TotalBronze  INT NOT NULL DEFAULT 0,

  CONSTRAINT CHK_Orders_Totals CHECK (
    TotalGold >= 0 AND TotalSilver >= 0 AND TotalBronze >= 0
  ),

  CONSTRAINT FK_Orders_Users
    FOREIGN KEY (UserId) REFERENCES Users(UserId)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE OrderItems (
  OrderItemId   INT AUTO_INCREMENT PRIMARY KEY,
  OrderId       INT NOT NULL,
  ItemId        INT NOT NULL,
  Quantity      INT NOT NULL DEFAULT 1,
  PriceGold     INT NOT NULL DEFAULT 0,
  PriceSilver   INT NOT NULL DEFAULT 0,
  PriceBronze   INT NOT NULL DEFAULT 0,

  CONSTRAINT CHK_OrderItems_Quantity CHECK (Quantity > 0),
  CONSTRAINT CHK_OrderItems_Prices CHECK (
    PriceGold >= 0 AND PriceSilver >= 0 AND PriceBronze >= 0
  ),

  CONSTRAINT FK_OrderItems_Orders
    FOREIGN KEY (OrderId) REFERENCES Orders(OrderId)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT FK_OrderItems_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE Inventory (
  InventoryId INT AUTO_INCREMENT PRIMARY KEY,
  UserId      INT NOT NULL,
  ItemId      INT NOT NULL,
  Quantity    INT NOT NULL DEFAULT 1,

  CONSTRAINT CHK_Inventory_Quantity CHECK (Quantity > 0),
  CONSTRAINT UQ_Inventory_User_Item UNIQUE (UserId, ItemId),

  CONSTRAINT FK_Inventory_Users
    FOREIGN KEY (UserId) REFERENCES Users(UserId)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT FK_Inventory_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE Reviews (
  ReviewId   INT AUTO_INCREMENT PRIMARY KEY,
  UserId     INT NOT NULL,
  ItemId     INT NOT NULL,
  Rating     INT NOT NULL,
  Comment    TEXT NULL,
  CreatedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT CHK_Reviews_Rating CHECK (Rating BETWEEN 1 AND 5),
  CONSTRAINT UQ_Reviews_User_Item UNIQUE (UserId, ItemId),

  CONSTRAINT FK_Reviews_Users
    FOREIGN KEY (UserId) REFERENCES Users(UserId)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT FK_Reviews_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE Carts (
  CartId    INT AUTO_INCREMENT PRIMARY KEY,
  UserId    INT NOT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT UQ_Carts_User UNIQUE (UserId),

  CONSTRAINT FK_Carts_Users
    FOREIGN KEY (UserId) REFERENCES Users(UserId)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE CartItems (
  CartItemId INT AUTO_INCREMENT PRIMARY KEY,
  CartId     INT NOT NULL,
  ItemId     INT NOT NULL,
  Quantity   INT NOT NULL DEFAULT 1,

  CONSTRAINT CHK_CartItems_Quantity CHECK (Quantity > 0),
  CONSTRAINT UQ_CartItems_Cart_Item UNIQUE (CartId, ItemId),

  CONSTRAINT FK_CartItems_Carts
    FOREIGN KEY (CartId) REFERENCES Carts(CartId)
    ON UPDATE CASCADE
    ON DELETE CASCADE,

  CONSTRAINT FK_CartItems_Items
    FOREIGN KEY (ItemId) REFERENCES Items(ItemId)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;