<?php
define('API_INCLUDED', true); // Consistent with db_connect.php
require '../global/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST'); // CORS consistency

// Supprimer la sortie de debug de la connexion
ob_start();

try {
    // Vérification méthode HTTP (web vs CLI)
    $is_post_request = (php_sapi_name() === 'cli') ? true : ($_SERVER['REQUEST_METHOD'] === 'POST');
    
    if (!$is_post_request) {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée. Utilisez POST."]);
        exit;
    }

    // Vérification Content-Type (web only)
    if (php_sapi_name() !== 'cli' && $_SERVER['CONTENT_TYPE'] !== 'application/json') {
        http_response_code(415);
        echo json_encode(["status" => "error", "message" => "Content-Type non supporté. Utilisez application/json."]);
        exit;
    }

    // Lecture des données (CLI vs web)
    $json_data = (php_sapi_name() === 'cli') ? '{
        "x": 0,
        "y": 0,
        "esc": 0,
        "up": 0,
        "down": 0,
        "right_r": 0,
        "left_l": 0,
        "tag": 0,
    }' : file_get_contents("php://input");

    // Validation du JSON
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "JSON invalide"]);
        exit;
    }

    // Définition des champs requis avec valeurs par défaut
    $fields = [
        'x' => ['type' => 'double', 'default' => 0],
        'y' => ['type' => 'double', 'default' => 0.0],
        'esc' => ['type' => 'double', 'default' => 0.0],
        'up' => ['type' => 'double', 'default' => 0.0],
        'down' => ['type' => 'double', 'default' => 0.0],
        'right_r' => ['type' => 'double', 'default' => 0.0],
        'left_l' => ['type' => 'double', 'default' => 0.0],
        'tag'=> ['type'=> 'double', 'default'=> 0],
    ];

    // Nettoyage et validation des données
    $clean_data = [];
    foreach ($fields as $field => $config) {
        if (!isset($data[$field])) {
            $clean_data[$field] = $config['default'];
            continue;
        }

        switch ($config['type']) {
            case 'double':
                $clean_data[$field] = filter_var($data[$field], FILTER_VALIDATE_FLOAT);
                if ($clean_data[$field] === false) {
                    $clean_data[$field] = $config['default'];
                }
                break;
            default:
                $clean_data[$field] = $config['default'];
        }
    }

    // Requête SQL préparée
    $sql = "INSERT INTO values_training_robot_ex (
                x, 
                y,
                esc,
                up,
                down,
                right_r,
                left_l, 
                tag)
                VALUES (
                :x, 
                :y,
                :esc,
                :up,
                :down,
                :right_r,
                :left_l, 
                :tag)";
    
    $stmt = $pdo->prepare($sql);

    // Liaison des paramètres avec typage explicite
    $stmt->bindParam(':x', $clean_data['x'], PDO::PARAM_STR);
    $stmt->bindParam(':y', $clean_data['y'], PDO::PARAM_STR);
    $stmt->bindParam(':esc', $clean_data['esc'], PDO::PARAM_STR);
    $stmt->bindParam(':up', $clean_data['up'], PDO::PARAM_STR);
    $stmt->bindParam(':down', $clean_data['down'], PDO::PARAM_STR);
    $stmt->bindParam(':right_r', $clean_data['right_r'], PDO::PARAM_STR);
    $stmt->bindParam(':left_l', $clean_data['left_l'], PDO::PARAM_STR);
    $stmt->bindParam(':tag', $clean_data['tag'], PDO::PARAM_STR);

    // Exécution avec journalisation
    $stmt->execute();
    error_log("Données insérées dans la table robot");

    // Réponse de succès
    echo json_encode([
        "status" => "success",
        "message" => "Données enregistrées avec succès",
        "data" => $clean_data // Optionnel - pour le débogage
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur base de données: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Erreur de base de données",
        "code" => $e->getCode()
    ]);
} finally {
    ob_end_flush();
}
?>