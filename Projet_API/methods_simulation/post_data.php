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
        'x' => ['type' => 'int', 'default' => 0],
        'y' => ['type' => 'int', 'default' => 0],
        'esc' => ['type' => 'int', 'default' => 0],
        'up' => ['type' => 'int', 'default' => 0],
        'down' => ['type' => 'int', 'default' => 0],
        'right_r' => ['type' => 'int', 'default' => 0],
        'left_l' => ['type' => 'int', 'default' => 0],
        'tag' => ['type' => 'float', 'default' => 0.0],
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

        $sql = "INSERT INTO values_training_robot_ex (
                    x, y, esc, up, down, right_r, left_l, tag
                ) VALUES (
                    :x, :y, :esc, :up, :down, :right_r, :left_l, :tag
                )";

        $stmt = $pdo->prepare($sql);

        $stmt->bindValue(':x', $clean_data['x'], PDO::PARAM_INT);
        $stmt->bindValue(':y', $clean_data['y'], PDO::PARAM_INT);
        $stmt->bindValue(':esc', $clean_data['esc'], PDO::PARAM_INT);
        $stmt->bindValue(':up', $clean_data['up'], PDO::PARAM_INT);
        $stmt->bindValue(':down', $clean_data['down'], PDO::PARAM_INT);
        $stmt->bindValue(':right_r', $clean_data['right_r'], PDO::PARAM_INT);
        $stmt->bindValue(':left_l', $clean_data['left_l'], PDO::PARAM_INT);
        $stmt->bindValue(':tag', $clean_data['tag'], PDO::PARAM_STR); // float traité comme string

        $stmt->execute();
        $pdo->commit();

        echo json_encode([
            "status" => "success",
            "message" => "Données insérées dans values_training_robot_ex",
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
