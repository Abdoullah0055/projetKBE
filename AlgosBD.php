<?php
require_once __DIR__ . '/includes/business_logic.php';
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
        error_log("[get_or_create_cart_id] INSERT Carts failed: " . $e->getMessage());
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

        if (!$item || (int)$item['isactive'] != 1) {
            error_log("[add_to_cart] Item $item_id introuvable ou inactif. item=" . json_encode($item));
            $pdo->rollBack();
            return false;
        }

        $cart_id = get_or_create_cart_id($user_id);
        if ($cart_id <= 0) {
            error_log("[add_to_cart] Impossible de recuperer/creer le panier pour user_id=$user_id. cart_id=$cart_id");
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
            error_log("[add_to_cart] Stock insuffisant pour item $item_id. new_qty=$new_qty, stock={$item['stock']}");
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
        error_log("[add_to_cart] PDOException: " . $e->getMessage());
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

        if (!$item || (int)$item['isactive'] != 1 || (int)$new_quantity > (int)$item['stock']) {
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
 COALESCE(r.HintText, '') AS hint_text,
 r.WrongAnswer1 AS wrong_answer1,
 r.WrongAnswer2 AS wrong_answer2,
 r.WrongAnswer3 AS wrong_answer3,
 r.RiddleType AS riddle_type,
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
 COALESCE(r.HintText, '') AS hint_text,
 r.WrongAnswer1 AS wrong_answer1,
 r.WrongAnswer2 AS wrong_answer2,
 r.WrongAnswer3 AS wrong_answer3,
 r.RiddleType AS riddle_type,
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
    $stmt->bindValue(':category_id', (int)$category_id, PDO::PARAM_INT);
    $stmt->bindValue(':difficulty', $difficulty, PDO::PARAM_STR);
    $stmt->execute();

    $riddle = $stmt->fetch();

    return $riddle ?: null;
}

function get_riddle_answer_text(int $riddle_id): ?string
{
    if ($riddle_id <= 0) {
        return null;
    }

    $pdo = get_pdo();
    $sql = "SELECT r.AnswerText FROM Riddles r WHERE r.RiddleId = :riddle_id AND r.IsActive = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':riddle_id' => $riddle_id]);

    $result = $stmt->fetchColumn();

    return $result !== false ? (string) $result : null;
}

function deduct_hp(int $user_id, int $amount): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE Users SET CurrentHP = GREATEST(CurrentHP - ?, 0) WHERE UserId = ?");
    return $stmt->execute([$amount, $user_id]);
}

function heal_hp(int $user_id, int $amount): int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE Users SET CurrentHP = LEAST(CurrentHP + ?, MaxHP) WHERE UserId = ?");
    $stmt->execute([$amount, $user_id]);
    $stmt2 = $pdo->prepare("SELECT CurrentHP FROM Users WHERE UserId = ?");
    $stmt2->execute([$user_id]);
    return (int)$stmt2->fetchColumn();
}

function get_user_hp(int $user_id): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT CurrentHP, MaxHP FROM Users WHERE UserId = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    return $row ? ['current' => (int)$row['currenthp'], 'max' => (int)$row['maxhp']] : ['current' => 100, 'max' => 100];
}

function credit_user_currency(int $user_id, int $gold, int $silver, int $bronze): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("UPDATE Users SET Gold = Gold + :gold, Silver = Silver + :silver, Bronze = Bronze + :bronze WHERE UserId = :userId");
    return $stmt->execute(['gold' => $gold, 'silver' => $silver, 'bronze' => $bronze, 'userId' => $user_id]);
}

function record_riddle_attempt(int $user_id, int $riddle_id, string $given_answer, bool $is_success): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("INSERT INTO UserRiddles (UserId, RiddleId, GivenAnswer, IsSuccess) VALUES (:userId, :riddleId, :givenAnswer, :isSuccess)");
    $stmt->execute([
        'userId' => $user_id,
        'riddleId' => $riddle_id,
        'givenAnswer' => $given_answer,
        'isSuccess' => $is_success ? 1 : 0
    ]);
}

function increment_riddle_stats(int $user_id, bool $is_success, bool $is_magic = false): void
{
    $pdo = get_pdo();
    $exists = $pdo->prepare("SELECT UserId FROM UserRiddleStats WHERE UserId = ?");
    $exists->execute([$user_id]);
    if (!$exists->fetch()) {
        $insert = $pdo->prepare("INSERT INTO UserRiddleStats (UserId, SolvedCount, FailedCount, MagicSolvedCount) VALUES (?, 0, 0, 0)");
        $insert->execute([$user_id]);
    }

    if ($is_success) {
        $sql = "UPDATE UserRiddleStats SET SolvedCount = SolvedCount + 1";
        if ($is_magic) {
            $sql .= ", MagicSolvedCount = MagicSolvedCount + 1";
        }
        $sql .= " WHERE UserId = ?";
    } else {
        $sql = "UPDATE UserRiddleStats SET FailedCount = FailedCount + 1 WHERE UserId = ?";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
}

function check_and_promote_mage(int $user_id): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT MagicSolvedCount FROM UserRiddleStats WHERE UserId = ?");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row && (int)$row['magicsolvedcount'] >= 3) {
        $upd = $pdo->prepare("UPDATE Users SET Role = 'Mage' WHERE UserId = ? AND Role = 'Player'");
        $upd->execute([$user_id]);
        return $upd->rowCount() > 0;
    }
    return false;
}

function get_difficult_streak(int $user_id): int
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("
        SELECT r.Difficulty, ur.IsSuccess
        FROM UserRiddles ur
        JOIN Riddles r ON r.RiddleId = ur.RiddleId
        WHERE ur.UserId = :userId
        ORDER BY ur.AnsweredAt DESC
    ");
    $stmt->execute(['userId' => $user_id]);
    $streak = 0;
    while ($row = $stmt->fetch()) {
        if ($row['difficulty'] === 'Difficile' && (int)$row['issuccess'] === 1) {
            $streak++;
        } else {
            break;
        }
    }
    return $streak;
}

function credit_streak_bonus(int $user_id): bool
{
    $streak = get_difficult_streak($user_id);
    if ($streak >= 3 && $streak % 3 === 0) {
        return credit_user_currency($user_id, 100, 0, 0);
    }
    return false;
}

function get_user_riddle_stats(int $user_id): array
{
    $pdo = get_pdo();
    $stats = [
        'solved_count' => 0,
        'failed_count' => 0,
        'magic_solved_count' => 0,
        'facile_solved' => 0,
        'facile_total' => 0,
        'moyenne_solved' => 0,
        'moyenne_total' => 0,
        'difficile_solved' => 0,
        'difficile_total' => 0,
        'category_stats' => [],
    ];

    $statsStmt = $pdo->prepare("SELECT SolvedCount, FailedCount, MagicSolvedCount FROM UserRiddleStats WHERE UserId = ?");
    $statsStmt->execute([$user_id]);
    $statsRow = $statsStmt->fetch();
    if ($statsRow) {
        $stats['solved_count'] = (int)$statsRow['solvedcount'];
        $stats['failed_count'] = (int)$statsRow['failedcount'];
        $stats['magic_solved_count'] = (int)$statsRow['magicsolvedcount'];
    }

    $totalStmt = $pdo->prepare("SELECT Difficulty, COUNT(*) as cnt FROM Riddles WHERE IsActive = 1 GROUP BY Difficulty");
    $totalStmt->execute();
    while ($tRow = $totalStmt->fetch()) {
        $key = strtolower($tRow['difficulty']) . '_total';
        if (isset($stats[$key])) {
            $stats[$key] = (int)$tRow['cnt'];
        }
    }

    $solvedStmt = $pdo->prepare("
        SELECT r.Difficulty, COUNT(*) as cnt
        FROM UserRiddles ur
        JOIN Riddles r ON r.RiddleId = ur.RiddleId
        WHERE ur.UserId = ? AND ur.IsSuccess = 1
        GROUP BY r.Difficulty
    ");
    $solvedStmt->execute([$user_id]);
    while ($sRow = $solvedStmt->fetch()) {
        $key = strtolower($sRow['difficulty']) . '_solved';
        if (isset($stats[$key])) {
            $stats[$key] = (int)$sRow['cnt'];
        }
    }

    $categoryStmt = $pdo->prepare("
        SELECT
            c.Name AS category_name,
            COUNT(r.RiddleId) AS total_count,
            SUM(CASE WHEN ur.UserRiddleId IS NOT NULL AND ur.IsSuccess = 1 THEN 1 ELSE 0 END) AS solved_count,
            SUM(CASE WHEN ur.UserRiddleId IS NOT NULL AND ur.IsSuccess = 0 THEN 1 ELSE 0 END) AS failed_count
        FROM RiddleCategories c
        LEFT JOIN Riddles r
            ON r.RiddleCategoryId = c.RiddleCategoryId
           AND r.IsActive = 1
        LEFT JOIN UserRiddles ur
            ON ur.RiddleId = r.RiddleId
           AND ur.UserId = :userId
        GROUP BY c.RiddleCategoryId, c.Name
        ORDER BY c.Name ASC
    ");
    $categoryStmt->execute(['userId' => $user_id]);
    while ($catRow = $categoryStmt->fetch()) {
        $total = (int)($catRow['total_count'] ?? 0);
        $solved = (int)($catRow['solved_count'] ?? 0);
        $failed = (int)($catRow['failed_count'] ?? 0);

        $stats['category_stats'][] = [
            'category' => (string)($catRow['category_name'] ?? 'Inconnu'),
            'total' => $total,
            'solved' => $solved,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($solved / $total) * 100, 2) : 0.0,
        ];
    }

    return $stats;
}

function is_riddle_magic_category(int $riddle_id): bool
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT RiddleCategoryId FROM Riddles WHERE RiddleId = ?");
    $stmt->execute([$riddle_id]);
    $row = $stmt->fetch();
    return $row && (int)$row['riddlecategoryid'] === 1;
}

function calculate_sell_price(int $item_id): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT i.ItemTypeId, i.PriceGold, i.PriceSilver, i.PriceBronze, i.Rarity FROM Items i WHERE i.ItemId = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();

    if (!$item) {
        return ['gold' => 0, 'silver' => 0, 'bronze' => 0];
    }

    $priceGold = (int)$item['pricegold'];
    $priceSilver = (int)$item['pricesilver'];
    $priceBronze = (int)$item['pricebronze'];

    $multiplier = compute_sell_multiplier((int)$item['itemtypeid'], (string)($item['rarity'] ?? 'commun'));

    return [
        'gold' => (int)floor($priceGold * $multiplier),
        'silver' => (int)floor($priceSilver * $multiplier),
        'bronze' => (int)floor($priceBronze * $multiplier),
        'original_gold' => $priceGold,
        'original_silver' => $priceSilver,
        'original_bronze' => $priceBronze,
        'multiplier' => $multiplier,
    ];
}

function sell_inventory_item(int $user_id, int $item_id): array
{
    $pdo = get_pdo();
    try {
        $pdo->beginTransaction();

        $invStmt = $pdo->prepare("SELECT InventoryId, Quantity FROM Inventory WHERE UserId = :userId AND ItemId = :itemId FOR UPDATE");
        $invStmt->execute(['userId' => $user_id, 'itemId' => $item_id]);
        $invRow = $invStmt->fetch();

        if (!$invRow) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Item non trouve dans l\'inventaire'];
        }

        if ((int)$invRow['quantity'] < 1) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Quantite insuffisante'];
        }

        $sellPrice = calculate_sell_price($item_id);

        $userStmt = $pdo->prepare("SELECT Gold, Silver, Bronze FROM Users WHERE UserId = :userId FOR UPDATE");
        $userStmt->execute(['userId' => $user_id]);
        $userRow = $userStmt->fetch();

        if ((int)$invRow['quantity'] > 1) {
            $updStmt = $pdo->prepare("UPDATE Inventory SET Quantity = Quantity - 1 WHERE UserId = :userId AND ItemId = :itemId");
            $updStmt->execute(['userId' => $user_id, 'itemId' => $item_id]);
        } else {
            $delStmt = $pdo->prepare("DELETE FROM Inventory WHERE UserId = :userId AND ItemId = :itemId");
            $delStmt->execute(['userId' => $user_id, 'itemId' => $item_id]);
        }

        $stockStmt = $pdo->prepare("UPDATE Items SET Stock = Stock + 1 WHERE ItemId = :itemId");
        $stockStmt->execute(['itemId' => $item_id]);

        $creditStmt = $pdo->prepare("UPDATE Users SET Gold = Gold + :gold, Silver = Silver + :silver, Bronze = Bronze + :bronze WHERE UserId = :userId");
        $creditStmt->execute([
            'gold' => $sellPrice['gold'],
            'silver' => $sellPrice['silver'],
            'bronze' => $sellPrice['bronze'],
            'userId' => $user_id
        ]);

        $pdo->commit();

        $newGold = (int)$userRow['gold'] + $sellPrice['gold'];
        $newSilver = (int)$userRow['silver'] + $sellPrice['silver'];
        $newBronze = (int)$userRow['bronze'] + $sellPrice['bronze'];

        return [
            'success' => true,
            'message' => 'Item vendu !',
            'sale_price' => $sellPrice,
            'new_balance' => ['gold' => $newGold, 'silver' => $newSilver, 'bronze' => $newBronze],
            'item_consumed' => ((int)$invRow['quantity'] <= 1)
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Erreur lors de la vente'];
    }
}


