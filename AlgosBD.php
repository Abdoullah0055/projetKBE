<?php
function get_pdo()
{
    $host = 'localhost';
    $port = 8889;
    $db = 'projetKBE';
    $user = 'root';
    $pass = 'root';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

    try {
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (\PDOException $e) {
        die("Erreur PDO : " . $e->getMessage());
    }
}

//Faire un console.log en PHP 
function consoleLog($message)
{
    echo "<script>console.log('PHP: " . $message . "');</script>";
}
//Fonction pour ajouter un article dans la table Items 
function add_item($name, $description, $gold, $silver, $bronze, $amount, $itemTypeId, $IsActive)
{
    consoleLog("Début fonction AlgosBD.php/add_item()");
    $pdo = get_pdo();
    if (!$pdo) {
        consoleLog("Erreur de connexion à la base de données dans add_item()");
        return false;
    }
    try {
        $sqlInsert = "insert into items (Name, Description, PriceGold, PriceSilver, PriceBronze, Stock, ItemTypeId, IsActive) values (:name, :description, :gold, :silver, :bronze, :amount, :itemTypeId, :IsActive)";
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([':name' => $name, ':description' => $description, ':gold' => $gold, ':silver' => $silver, ':bronze' => $bronze, ':amount' => $amount, ':itemTypeId' => $itemTypeId, ':IsActive' => $IsActive]);
        consoleLog("Article ajouté avec succès : " . $name);
        return true;
    } catch (PDOException $e) {
        consoleLog("Erreur lors de l'ajout de l'article : " . $e->getMessage());
        return false;
    }
}
