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
    $isAppJSON = stripos($contentType, 'application/json') !== false;

    if (!$isAppJSON) {
        http_response_code(415);
        echo json_encode(["status" => "error", "message" => "Content-Type non supporté. Utilisez application/json."]);
        exit;
    }

    // Lecture des données JSON
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);

    if (empty($data)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Aucune donnée reçue"]);
        exit;
    }

    // Définition des champs attendus
    $fields = [
        'nombre_coups' => ['type' => 'int', 'default' => 0],
        'duree_simulation' => ['type' => 'time', 'default' => 0],
        'modele_simulation' => ['type' => 'varchar', 'default' => 0],
    ];

    $clean_data = [];
    $errors = [];

    foreach ($fields as $field => $config) {
        if (!isset($data[$field])) {
            $clean_data[$field] = $config['default'];
            continue;
        }

        switch ($config['type']) {
            case 'int':
                $value = filter_var($data[$field], FILTER_VALIDATE_INT);
                if ($value === false) {
                    $errors[] = "Le champ '$field' doit être un entier.";
                    $value = $config['default'];
                }
                break;
            case 'float':
                $value = filter_var($data[$field], FILTER_VALIDATE_FLOAT);
                if ($value === false) {
                    $errors[] = "Le champ '$field' doit être un nombre décimal.";
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
        $stmt->bindValue(':duree_simulation', $clean_data['duree_simulation'], PDO::PARAM_INT);
        $stmt->bindValue(':modele_simulation', $clean_data['modele_simulation'], PDO::PARAM_INT);

        $stmt->execute();
        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Données insérées dans Simulation",
            "data" => $clean_data
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        error_log("Erreur DB: " . $e->getMessage());
        echo json_encode([
            "status" => "error",
            "message" => "Erreur lors de l'insertion",
            "details" => $e->getMessage()
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("Erreur système: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Erreur système",
        "details" => $e->getMessage()
    ]);
}
?>
