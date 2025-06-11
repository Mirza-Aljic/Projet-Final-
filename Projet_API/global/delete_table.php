<?php
define('API_INCLUDED', true); // Nécessaire pour db_connect.php
require __DIR__.'/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST'); // Cohérence CORS

try {
    // Vérification méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Méthode non autorisée. Utilisez POST."]);
        exit;
    }

    // Vérification Content-Type
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        http_response_code(415);
        echo json_encode(["error" => "Content-Type non supporté. Utilisez application/json."]);
        exit;
    }

    // Lecture et validation du JSON
    $json = file_get_contents('php://input');
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["error" => "JSON invalide"]);
        exit;
    }

    $data = json_decode($json, true);
    
    // Validation du nom de table
    if (empty($data['table_name'])) {
        http_response_code(400);
        echo json_encode(["error" => "Nom de table manquant"]);
        exit;
    }

    // Nettoyage du nom de table
    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $data['table_name']);
    if (empty($table_name)) {
        http_response_code(400);
        echo json_encode(["error" => "Nom de table invalide"]);
        exit;
    }

    // Vérification existence table
    $stmt = $conn->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table_name]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Table `$table_name` introuvable"]);
        exit;
    }

    // Suppression sécurisée
    $conn->exec("DROP TABLE `$table_name`");
    error_log("Table supprimée : $table_name"); // Journalisation

    // Réponse de succès
    echo json_encode([
        "success" => true,
        "message" => "Table `$table_name` supprimée avec succès"
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur suppression table: " . $e->getMessage());
    echo json_encode([
        "error" => "Erreur de base de données",
        "code" => $e->getCode()
    ]);
}