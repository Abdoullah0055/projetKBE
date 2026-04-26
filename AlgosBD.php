<?php
function get_pdo()
{
    static $pdo = null;

    if ($pdo === null) {
        $host = '158.69.48.109';
        $db = 'dbdarquest15';
        $user = 'equipe15';
        $pass = '7klm98u8';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_CASE => PDO::CASE_LOWER,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            die("Erreur connexion db");
        }
    }

    return $pdo;
}

/**
 * Recupere le cart id d'un utilisateur, sans le creer.
 */
function get_cart_id($user_id)
{
    $pdo = get_pdo();

    $sql = "SELECT CartId FROM Carts WHERE UserId = :user_id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);

    $cart_id = $stmt->fetchColumn();
    return $cart_id ? (int)$cart_id : null;
}

/**
 * Recupere ou cree un panier pour un utilisateur.
 */
function get_or_create_cart_id($user_id)
{
    $pdo = get_pdo();

    $cart_id = get_cart_id($user_id);
    if ($cart_id !== null) {
        return $cart_id;
    }

    try {
        $sql_insert = "INSERT INTO Carts (UserId) VALUES (:user_id)";
        $stmt = $pdo->prepare($sql_insert);
        $stmt->execute([':user_id' => $user_id]);

        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        // Si doublon (race condition), on relit le panier.
        $cart_id = get_cart_id($user_id);
        return $cart_id !== null ? $cart_id : 0;
    }
}

/**
 * Ajouter un item au panier avec validations metier (stock + actif).
 */
function add_to_cart($user_id, $item_id, $quantity)
{
    if ($quantity <= 0) {
        return false;
    }

    $pdo = get_pdo();

    try {
        $pdo->beginTransaction();

        $item_stmt = $pdo->prepare(
            "SELECT ItemId, Stock, IsActive
            FROM Items
            WHERE ItemId = :item_id
            FOR UPDATE"
        );
        $item_stmt->execute([':item_id' => $item_id]);
        $item = $item_stmt->fetch();

        if (!$item || (int)$item['is_active'] !== 1) {
            $pdo->rollBack();
            return false;
        }

        $cart_id = get_or_create_cart_id($user_id);
        if ($cart_id <= 0) {
            $pdo->rollBack();
            return false;
        }

        $sql_check = "SELECT Quantity FROM CartItems WHERE CartId = :cart_id AND ItemId = :item_id";
        $stmt = $pdo->prepare($sql_check);
        $stmt->execute([
            ':cart_id' => $cart_id,
            ':item_id' => $item_id
        ]);

        $existing = $stmt->fetch();
        $existing_qty = $existing ? (int)$existing['quantity'] : 0;
        $new_qty = $existing_qty + (int)$quantity;

        if ($new_qty > (int)$item['stock']) {
            $pdo->rollBack();
            return false;
        }

        if ($existing) {
            $sql_update = "UPDATE CartItems
            SET Quantity = :quantity
            WHERE CartId = :cart_id AND ItemId = :item_id";
            $stmt = $pdo->prepare($sql_update);
            $stmt->execute([
                ':quantity' => $new_qty,
                ':cart_id' => $cart_id,
                ':item_id' => $item_id
            ]);
        } else {
            $sql_insert = "INSERT INTO CartItems (CartId, ItemId, Quantity)
            VALUES (:cart_id, :item_id, :quantity)";
            $stmt = $pdo->prepare($sql_insert);
            $stmt->execute([
                ':cart_id' => $cart_id,
                ':item_id' => $item_id,
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
 * Ajouter un item dans le catalogue.
 */
function add_item($name, $description, $gold, $silver, $bronze, $amount, $item_type_id, $is_active)
{
    $pdo = get_pdo();

    try {
        $sql = "INSERT INTO Items
            (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, IsActive)
            VALUES
            (:name, :description, :gold, :silver, :bronze, :amount, :item_type_id, :is_active)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':gold' => $gold,
            ':silver' => $silver,
            ':bronze' => $bronze,
            ':amount' => $amount,
            ':item_type_id' => $item_type_id,
            ':is_active' => $is_active
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Modifie la quantite d'un item du panier.
 * Si new_quantity <= 0, l'item est supprime.
 */
function modify_item_quantity_cart($user_id, $item_id, $new_quantity)
{
    $pdo = get_pdo();

    try {
        $pdo->beginTransaction();

        $cart_id = get_cart_id($user_id);
        if ($cart_id === null) {
            $pdo->rollBack();
            return false;
        }

        if ((int)$new_quantity <= 0) {
        $sql = "DELETE FROM CartItems WHERE CartId = :cart_id AND ItemId = :item_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cart_id' => $cart_id,
            ':item_id' => $item_id
        ]);
        $pdo->commit();
        return true;
    }

    $item_stmt = $pdo->prepare(
"SELECT ItemId, Stock, IsActive
            FROM Items
            WHERE ItemId = :item_id
            FOR UPDATE"
        );
        $item_stmt->execute([':item_id' => $item_id]);
        $item = $item_stmt->fetch();

        if (!$item || (int)$item['is_active'] !== 1 || (int)$new_quantity > (int)$item['stock']) {
            $pdo->rollBack();
            return false;
        }

$sql = "UPDATE CartItems
            SET Quantity = :quantity
            WHERE CartId = :cart_id AND ItemId = :item_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':quantity' => (int)$new_quantity,
            ':cart_id'   => $cart_id,
            ':item_id'   => $item_id
        ]);

        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            return false;
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
function remove_from_cart($user_id, $item_id)
{
    $pdo = get_pdo();

    try {
        $pdo->beginTransaction();

        $cart_id = get_cart_id($user_id);
        if ($cart_id === null) {
            $pdo->rollBack();
            return false;
        }

    $sql = "DELETE FROM CartItems WHERE CartId = :cart_id AND ItemId = :item_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cart_id' => $cart_id,
        ':item_id' => $item_id
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

function get_active_riddle_by_id($riddle_id)
{
    if ($riddle_id <= 0) {
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
FROM Riddles r
WHERE r.RiddleId = :riddle_id
AND r.IsActive = 1
        LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':riddle_id' => $riddle_id,
    ]);

    $riddle = $stmt->fetch();

    return $riddle ?: null;
}

function get_random_active_riddle($category_id, $difficulty)
{
    if ($category_id <= 0 || !is_string($difficulty) || $difficulty === '') {
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
FROM Riddles r
WHERE r.RiddleCategoryId = :category_id
AND r.Difficulty = :difficulty
AND r.IsActive = 1
        ORDER BY RAND()
        LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':category_id' => $category_id,
        ':difficulty' => $difficulty,
    ]);

    $riddle = $stmt->fetch();

    return $riddle ?: null;
}