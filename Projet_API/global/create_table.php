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
    
    // Validation des données requises
    $required = ['table_name', 'columns'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(["error" => "Champ '$field' manquant"]);
            exit;
        }
    }

    // Nettoyage des entrées
    $table_name = preg_replace('/[^a-zA-Z0-9_]/', '', $data['table_name']);
    if (empty($table_name)) {
        http_response_code(400);
        echo json_encode(["error" => "Nom de table invalide"]);
        exit;
    }

    // Types de colonnes autorisés
    $valid_types = [
        'INT' => 'INT',
        'VARCHAR' => 'VARCHAR(255)',
        'TEXT' => 'TEXT',
        'FLOAT' => 'FLOAT',
        'BOOLEAN' => 'TINYINT(1)',
        'DATETIME' => 'DATETIME',
        'TIMESTAMP' => 'TIMESTAMP'
    ];

    // Construction sécurisée du SQL
    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
        id INT AUTO_INCREMENT PRIMARY KEY";

    foreach ($data['columns'] as $column) {
        if (empty($column['name'])) continue;
        
        $col_name = preg_replace('/[^a-zA-Z0-9_]/', '', $column['name']);
        if (empty($col_name)) continue;
        
        $col_type = $valid_types[strtoupper($column['type'])] ?? 'VARCHAR(255)';
        
        $sql .= ", `$col_name` $col_type NOT NULL";
    }

    $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Exécution avec journalisation
    $conn->exec($sql);
    error_log("Table créée : $table_name");

    // Réponse de succès
    echo json_encode([
        "success" => true,
        "table" => $table_name,
        "sql" => $sql // Optionnel - pour le débogage
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur création table: " . $e->getMessage());
    echo json_encode([
        "error" => "Erreur de base de données",
        "code" => $e->getCode()
    ]);
}
?>