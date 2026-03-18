<?php
function get_pdo()
{
    $host = ''; // 127.0.0.1 si la BD et l'application sont sur le même serveur
    $db = ''; // nom de la base de données
    $user = '';
    $pass = '';
    $charset = '';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        // echo "Connexion établie";
    } catch (\PDOException $e) {
        $pdo = false;
        die("ERREUR PAS REUSSI A SE CONNECTER AU PDO");
        //throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
    return $pdo;
}
