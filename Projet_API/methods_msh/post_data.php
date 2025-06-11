<?php
define('API_INCLUDED', true);

$db_path = '/home/nathancampanini/www/website/global/db_connect.php'; // Chemin Linux
if (!file_exists($db_path)) {
    $db_path = __DIR__ . '/../../global/db_connect.php'; // Alternative
}
require $db_path;

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

ob_start();

try {
    // Vérification méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit; // Pour les pré-requêtes CORS
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée. Utilisez POST."]);
        exit;
    }

    // Gestion du Content-Type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJson = stripos($contentType, 'application/json') !== false;
    $isFormData = stripos($contentType, 'multipart/form-data') !== false;
    $isUrlEncoded = stripos($contentType, 'application/x-www-form-urlencoded') !== false;

    // Récupération des données selon le Content-Type
    if ($isJson) {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON invalide");
        }
    } elseif ($isFormData || $isUrlEncoded) {
        $data = $_POST;
    } else {
        http_response_code(415);
        echo json_encode([
            "status" => "error", 
            "message" => "Content-Type non supporté. Utilisez application/json, multipart/form-data ou x-www-form-urlencoded."
        ]);
        exit;
    }

    // Validation des données
    $required_fields = [
        'output_index' => ['type' => 'int', 'default' => 0],
        'hidden_index' => ['type' => 'int', 'default' => 0],
        'weight' => ['type' => 'float', 'default' => 0.0]
    ];

    $clean_data = [];
    foreach ($required_fields as $field => $config) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Champ manquant: $field"]);
            exit;
        }

        $value = $data[$field];
        switch ($config['type']) {
            case 'int':
                $clean_data[$field] = filter_var($value, FILTER_VALIDATE_INT);
                break;
            case 'float':
                $clean_data[$field] = filter_var($value, FILTER_VALIDATE_FLOAT);
                break;
            default:
                $clean_data[$field] = $value;
        }

        if ($clean_data[$field] === false) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Valeur invalide pour $field"]);
            exit;
        }
    }

    // Connexion et insertion en base
    $sql = "INSERT INTO modele_stocke_ho (output_index, hidden_index, weight)
            VALUES (:output_index, :hidden_index, :weight)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':output_index', $clean_data['output_index'], PDO::PARAM_INT);
    $stmt->bindValue(':hidden_index', $clean_data['hidden_index'], PDO::PARAM_INT);
    $stmt->bindValue(':weight', $clean_data['weight'], PDO::PARAM_STR);
    
    if (!$stmt->execute()) {
        throw new Exception("Échec de l'insertion en base de données");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Données enregistrées",
        "data" => $clean_data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
        "trace" => $e->getTraceAsString() // À supprimer en production
    ]);
} finally {
    ob_end_flush();
}
?>