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
 * Récupère ou crée un panier pour un utilisateur
 */
function get_or_create_cart_id($userId)
{
    $pdo = get_pdo();

    // Essayer de récupérer
    $sql = "SELECT CartId FROM carts WHERE UserId = :userId LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':userId' => $userId]);
    $cartId = $stmt->fetchColumn();

    if ($cartId) {
        return $cartId;
    }

    // Sinon créer
    try {
        $sqlInsert = "INSERT INTO carts (UserId) VALUES (:userId)";
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([':userId' => $userId]);

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        // Si doublon (race condition), on récupère à nouveau
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchColumn();
    }
}

/**
 * Ajouter un item au panier (avec gestion des doublons)
 */
function add_to_cart($userId, $itemId, $quantity)
{
    if ($quantity <= 0) {
        return false;
    }

    $pdo = get_pdo();

    try {
        $pdo->beginTransaction();

        $cartId = get_or_create_cart_id($userId);

        // Vérifier si l'item existe déjà
        $sqlCheck = "SELECT Quantity FROM cartItems 
                     WHERE CartId = :cartId AND ItemId = :itemId";
        $stmt = $pdo->prepare($sqlCheck);
        $stmt->execute([
            ':cartId' => $cartId,
            ':itemId' => $itemId
        ]);

        $existing = $stmt->fetch();

        if ($existing) {
            // Update quantité
            $sqlUpdate = "UPDATE cartItems 
                          SET Quantity = Quantity + :quantity 
                          WHERE CartId = :cartId AND ItemId = :itemId";
        } else {
            // Insert
            $sqlUpdate = "INSERT INTO cartItems (CartId, ItemId, Quantity) 
                          VALUES (:cartId, :itemId, :quantity)";
        }

        $stmt = $pdo->prepare($sqlUpdate);
        $stmt->execute([
            ':cartId' => $cartId,
            ':itemId' => $itemId,
            ':quantity' => $quantity
        ]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Ajouter un item (inchangé mais nettoyé)
 */
function add_item($name, $description, $gold, $silver, $bronze, $amount, $itemTypeId, $isActive)
{
    $pdo = get_pdo();

    try {
        $sql = "INSERT INTO items 
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
