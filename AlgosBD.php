<?php

function get_pdo()
{
    static $pdo = null;

    if ($pdo === null) {
        $host = '127.0.0.1';
        $db = 'projetKBE';
        $user = 'root';
        $pass = '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            die("Erreur connexion DB");
        }
    }

    return $pdo;
}

/**
 * RÃ©cupÃ¨re ou crÃ©e un panier pour un utilisateur
 */
function get_or_create_cart_id($userId)
{
    $pdo = get_pdo();

    // Essayer de rÃ©cupÃ©rer
    $sql = "SELECT CartId FROM carts WHERE UserId = :userId LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':userId' => $userId]);
    $cartId = $stmt->fetchColumn();

    if ($cartId) {
        return $cartId;
    }

    // Sinon crÃ©er
    try {
        $sqlInsert = "INSERT INTO Carts (UserId) VALUES (:userId)";
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([':userId' => $userId]);

        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        // Si doublon (race condition), on rÃ©cupÃ¨re Ã  nouveau
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchColumn();
    }
}

/**
 * Ajouter un item au panier avec validations metier (stock + actif).
 */
function add_to_cart($userId, $itemId, $quantity)
{
    if ($quantity <= 0) {
        return false;
    }

    $pdo = get_pdo();

    try {
        $pdo->beginTransaction();

        $itemStmt = $pdo->prepare(
            "SELECT ItemId, Stock, IsActive
             FROM Items
             WHERE ItemId = :itemId
             FOR UPDATE"
        );
        $itemStmt->execute([':itemId' => $itemId]);
        $item = $itemStmt->fetch();

        // VÃ©rifier si l'item existe dÃ©jÃ 
        $sqlCheck = "SELECT Quantity FROM cartItems 
                     WHERE CartId = :cartId AND ItemId = :itemId";
        $stmt = $pdo->prepare($sqlCheck);
        $stmt->execute([
            ':cartId' => $cartId,
            ':itemId' => $itemId
        ]);

        $existing = $stmt->fetch();
        $existingQty = $existing ? (int)$existing['Quantity'] : 0;
        $newQty = $existingQty + (int)$quantity;

        if ($existing) {
            // Update quantitÃ©
            $sqlUpdate = "UPDATE cartItems 
                          SET Quantity = Quantity + :quantity 
                          WHERE CartId = :cartId AND ItemId = :itemId";
        } else {
            // Insert
            $sqlUpdate = "INSERT INTO cartItems (CartId, ItemId, Quantity) 
                          VALUES (:cartId, :itemId, :quantity)";
        }

        if ($existing) {
            $sqlUpdate = "UPDATE CartItems
                          SET Quantity = :quantity
                          WHERE CartId = :cartId AND ItemId = :itemId";
            $stmt = $pdo->prepare($sqlUpdate);
            $stmt->execute([
                ':quantity' => $newQty,
                ':cartId' => $cartId,
                ':itemId' => $itemId
            ]);
        } else {
            $sqlInsert = "INSERT INTO CartItems (CartId, ItemId, Quantity)
                          VALUES (:cartId, :itemId, :quantity)";
            $stmt = $pdo->prepare($sqlInsert);
            $stmt->execute([
                ':cartId' => $cartId,
                ':itemId' => $itemId,
                ':quantity' => (int)$quantity
            ]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

/**
 * Ajouter un item (inchangÃ© mais nettoyÃ©)
 */
function add_item($name, $description, $gold, $silver, $bronze, $amount, $itemTypeId, $isActive)
{
    $pdo = get_pdo();

    try {
        $sql = "INSERT INTO Items
                (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, IsActive)
                VALUES
                (:name, :description, :gold, :silver, :bronze, :amount, :itemTypeId, :isActive)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':gold' => $gold,
            ':silver' => $silver,
            ':bronze' => $bronze,
            ':amount' => $amount,
            ':itemTypeId' => $itemTypeId,
            ':isActive' => $isActive
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Modifie la quantitÃ© d'un item spÃ©cifique dans le panier.
 * Si la nouvelle quantitÃ© est <= 0, l'item est retirÃ© du panier.
 */
function modify_item_quantity_cart($userId, $itemId, $newQuantity)
{
    $pdo = get_pdo();

    try {
        $pdo->beginTransaction();

        // 1. RÃ©cupÃ©rer le CartId de l'utilisateur
        $cartId = get_or_create_cart_id($userId);

        if ($newQuantity <= 0) {
            // Si la quantitÃ© est nulle ou nÃ©gative, on supprime l'entrÃ©e
            $sql = "DELETE FROM cartItems WHERE CartId = :cartId AND ItemId = :itemId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cartId' => $cartId,
                ':itemId' => $itemId
            ]);
        } else {
            // Sinon, on met Ã  jour avec la valeur exacte fournie
            $sql = "UPDATE cartItems 
                    SET Quantity = :quantity 
                    WHERE CartId = :cartId AND ItemId = :itemId";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':quantity' => $newQuantity,
                ':cartId'   => $cartId,
                ':itemId'   => $itemId
            ]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

/**
 * Supprime un item du panier sans creer de panier fantome.
 */
function remove_from_cart($userId, $itemId)
{
    $pdo = get_pdo();

    try {
        $pdo->beginTransaction();

        $cartId = get_cart_id($userId);
        if ($cartId === null) {
            $pdo->rollBack();
            return false;
        }

        $sql = "DELETE FROM CartItems WHERE CartId = :cartId AND ItemId = :itemId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cartId' => $cartId,
            ':itemId' => $itemId
        ]);

        $pdo->commit();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return false;
    }
}

function get_active_riddle_by_id($riddleId)
{
    if ($riddleId <= 0) {
        return null;
    }

    $pdo = get_pdo();
    $sql = "SELECT
                r.RiddleId AS id,
                r.QuestionText AS question_text,
                r.AnswerText AS answer_text,
                COALESCE(r.HintText, '') AS hint_text,
                r.Difficulty AS difficulty,
                                r.RiddleCategoryId AS category_id,
                                r.RewardGold AS reward_gold,
                                r.RewardSilver AS reward_silver,
                                r.RewardBronze AS reward_bronze
            FROM riddles r
            WHERE r.RiddleId = :riddleId
              AND r.IsActive = 1
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':riddleId' => $riddleId,
    ]);

    $riddle = $stmt->fetch();

    return $riddle ?: null;
}

function get_random_active_riddle($categoryId, $difficulty)
{
    if ($categoryId <= 0 || !is_string($difficulty) || $difficulty === '') {
        return null;
    }

    $pdo = get_pdo();
    $sql = "SELECT
                r.RiddleId AS id,
                r.QuestionText AS question_text,
                r.AnswerText AS answer_text,
                COALESCE(r.HintText, '') AS hint_text,
                r.Difficulty AS difficulty,
                                r.RiddleCategoryId AS category_id,
                                r.RewardGold AS reward_gold,
                                r.RewardSilver AS reward_silver,
                                r.RewardBronze AS reward_bronze
            FROM riddles r
            WHERE r.RiddleCategoryId = :categoryId
              AND r.Difficulty = :difficulty
              AND r.IsActive = 1
            ORDER BY RAND()
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':categoryId' => $categoryId,
        ':difficulty' => $difficulty,
    ]);

    $riddle = $stmt->fetch();

    return $riddle ?: null;
}


