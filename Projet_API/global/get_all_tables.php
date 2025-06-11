<?php
define('API_INCLUDED', true);
$pdo = require __DIR__.'/db_connect.php';

// Vérification que la connexion PDO est valide
if (!($pdo instanceof PDO)) {
    http_response_code(500);
    die(json_encode(["error" => "Connexion à la base de données invalide"]));
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1:5500');

try {
    // 1. Récupération sécurisée des tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Gestion des requêtes pour une table spécifique
    if (isset($_GET['table'])) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']);
        
        if (!in_array($table, $tables)) {
            http_response_code(404);
            echo json_encode([
                "error" => "Table non trouvée",
                "available_tables" => $tables
            ]);
            exit;
        }
        
        // Requête préparée pour plus de sécurité
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` LIMIT 100");
        $stmt->execute();
        
        echo json_encode([
            "table" => $table,
            "count" => $stmt->rowCount(),
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // 3. Récupération optimisée avec gestion de la mémoire
    $result = [];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` LIMIT 10");
        $stmt->execute();
        $result[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ajout de métadonnées utiles
    $response = [
        "timestamp" => date('c'),
        "total_tables" => count($tables),
        "data" => $result
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("Database Error [".date('Y-m-d H:i:s')."]: ".$e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Erreur de base de données",
        "code" => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log("General Error: ".$e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Erreur interne du serveur"]);
}