-- =========================
-- 1) Users
-- =========================
CREATE TABLE Users (
  UserId   INT AUTO_INCREMENT PRIMARY KEY,
  Alias    VARCHAR(30)  NOT NULL,
  Email    VARCHAR(254) NOT NULL,
  Password VARCHAR(255) NOT NULL,
  Role     VARCHAR(20)  NOT NULL,
  Gold     INT NOT NULL DEFAULT 0,
  Silver   INT NOT NULL DEFAULT 0,
  Bronze   INT NOT NULL DEFAULT 0,

  CONSTRAINT UQ_Users_Alias UNIQUE (Alias),
  CONSTRAINT UQ_Users_Email UNIQUE (Email),

  CONSTRAINT CHK_Users_Gold   CHECK (Gold   >= 0),
  CONSTRAINT CHK_Users_Silver CHECK (Silver >= 0),
  CONSTRAINT CHK_Users_Bronze CHECK (Bronze >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 2) ItemTypes
-- =========================
CREATE TABLE ItemTypes (
  ItemTypeId INT AUTO_INCREMENT PRIMARY KEY,
  Name       VARCHAR(50) NOT NULL,

  CONSTRAINT UQ_ItemTypes_Name UNIQUE (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 3) Items
-- =========================
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

  CONSTRAINT CHK_Items_Prices CHECK (
    PriceGold >= 0 AND PriceSilver >= 0 AND PriceBronze >= 0
  ),
  CONSTRAINT CHK_Items_Stock CHECK (Stock >= 0),

  CONSTRAINT FK_Items_ItemTypes
    FOREIGN KEY (ItemTypeId) REFERENCES ItemTypes(ItemTypeId)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 4) Orders
-- =========================
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

-- =========================
-- 5) Inventory
-- =========================
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

-- =========================
-- 6) Reviews
-- =========================
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

-- =========================
-- 7) Carts
-- =========================
CREATE TABLE Carts (
  CartId    INT AUTO_INCREMENT PRIMARY KEY,
  UserId    INT NOT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, --On pourrait enlever

  CONSTRAINT FK_Carts_Users
    FOREIGN KEY (UserId) REFERENCES Users(UserId)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 8) CartItems
-- =========================
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