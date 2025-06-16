<?php
define('API_INCLUDED', true);
require '../global/db_connect.php'; // Adapte le chemin selon ton hébergement

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestion des requêtes OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Vérification méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            "status" => "error", 
            "message" => "Méthode non autorisée. Utilisez POST."
        ]);
        exit;
    }

    // Vérification Content-Type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        http_response_code(415);
        echo json_encode(["status" => "error", "message" => "Content-Type non supporté. Utilisez application/json."]);
        exit;
    }

    // Lecture des données JSON
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);

    if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Données JSON invalides"]);
        exit;
    }

    // Définition des champs attendus
    $fields = [
        'nombre_coups' => ['type' => 'int', 'default' => 0],
        'duree_simulation' => ['type' => 'string', 'default' => "00:00:00"],
        'modele_simulation' => ['type' => 'string', 'default' => ""],
    ];

    $clean_data = [];
    $errors = [];

    foreach ($fields as $field => $config) {
        if (!isset($data[$field])) {
            $clean_data[$field] = $config['default'];
            continue;
        }

        $value = $data[$field];
        
        switch ($config['type']) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    $errors[] = "Le champ '$field' doit être un entier.";
                    $value = $config['default'];
                } else {
                    $value = (int)$value;
                }
                break;
            case 'float':
                if (!is_numeric($value)) {
                    $errors[] = "Le champ '$field' doit être un nombre décimal.";
                    $value = $config['default'];
                } else {
                    $value = (float)$value;
                }
                break;
            case 'string':
                $value = trim((string)$value);
                break;
            case 'time':
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                    $errors[] = "Le champ '$field' doit être au format HH:MM:SS.";
                    $value = $config['default'];
                }
                break;
            default:
                $value = $config['default'];
        }

        $clean_data[$field] = $value;
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Erreur de validation",
            "errors" => $errors
        ]);
        exit;
    }

    // Insertion SQL
    try {
        $pdo->beginTransaction();

        $sql = "INSERT INTO Simulation (
                    nombre_coups, duree_simulation, modele_simulation
                ) VALUES (
                    :nombre_coups, :duree_simulation, :modele_simulation
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':nombre_coups', $clean_data['nombre_coups'], PDO::PARAM_INT);
        $stmt->bindValue(':duree_simulation', $clean_data['duree_simulation'], PDO::PARAM_STR);
        $stmt->bindValue(':modele_simulation', $clean_data['modele_simulation'], PDO::PARAM_STR);

        $stmt->execute();
        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Données insérées avec succès",
            "data" => $clean_data
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        error_log("Erreur DB: " . $e->getMessage());
        echo json_encode([
            "status" => "error",
            "message" => "Erreur lors de l'insertion en base de données",
            "details" => "Une erreur interne est survenue"
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Erreur système: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Erreur système",
        "details" => "Une erreur interne est survenue"
    ]);
}
?>