<?php
define('API_INCLUDED', true);
include __DIR__ . '/../global/db_connect.php';

header('Content-Type: application/json');

try {
    // Check HTTP method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée. Utilisez POST."]);
        exit;
    }

    // Get content type and handle both form-data and x-www-form-urlencoded
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $isFormData = stripos($contentType, 'multipart/form-data') !== false;
    $isUrlEncoded = stripos($contentType, 'application/x-www-form-urlencoded') !== false;
    $isAppJSON = stripos($contentType, 'application/json') !== false;

    if (!$isFormData && !$isUrlEncoded && !$isAppJSON) {
        http_response_code(415);
        echo json_encode(["status" => "error", "message" => "Content-Type non supporté. Utilisez form-data ou x-www-form-urlencoded ou application/json."]);
        exit;
    }

    // Field definitions
    $fields = [
        'up' => ['type' => 'int', 'required' => true],
        'down' => ['type' => 'int', 'required' => true],
        'left' => ['type' => 'int', 'required' => true],  // Changed from 'left_' to match form data
        'right' => ['type' => 'int', 'required' => true], // Changed from 'right_' to match form data
        'X' => ['type' => 'int', 'required' => true],
        'Y' => ['type' => 'int', 'required' => true]
    ];

    // Data cleaning and validation
    $clean_data = [];
    foreach ($fields as $field => $config) {
        if (!isset($_POST[$field])) {
            if ($config['required']) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Champ '$field' manquant"]);
                exit;
            }
            $clean_data[$field] = null;
            continue;
        }

        switch ($config['type']) {
            case 'int':
                $clean_data[$field] = filter_var($_POST[$field], FILTER_VALIDATE_INT);
                if ($clean_data[$field] === false) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Valeur invalide pour '$field'"]);
                    exit;
                }
                break;
            case 'float':
                $clean_data[$field] = filter_var($_POST[$field], FILTER_VALIDATE_FLOAT);
                if ($clean_data[$field] === false) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Valeur invalide pour '$field'"]);
                    exit;
                }
                break;
            default:
                $clean_data[$field] = htmlspecialchars($_POST[$field]);
        }
    }

    // Prepared SQL query - note the column names match your database
    $sql = "INSERT INTO input (up, down, left_, right_, X, Y) 
            VALUES (:up, :down, :left_, :right_, :X, :Y)";
    $stmt = $conn->prepare($sql);

    // Parameter binding - note the underscore in left_ and right_
    $stmt->bindParam(':up', $clean_data['up'], PDO::PARAM_INT);
    $stmt->bindParam(':down', $clean_data['down'], PDO::PARAM_INT);
    $stmt->bindParam(':left_', $clean_data['left'], PDO::PARAM_INT);  // Map form 'left' to column 'left_'
    $stmt->bindParam(':right_', $clean_data['right'], PDO::PARAM_INT); // Map form 'right' to column 'right_'
    $stmt->bindParam(':X', $clean_data['X'], PDO::PARAM_INT);
    $stmt->bindParam(':Y', $clean_data['Y'], PDO::PARAM_INT);

    // Execution
    $stmt->execute();
    error_log("Données insérées dans la table input: " . json_encode($clean_data));

    // Success response
    echo json_encode([
        "status" => "success",
        "message" => "Données enregistrées avec succès",
        "data" => $clean_data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur base de données: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Erreur de base de données",
        "code" => $e->getCode()
    ]);
}
?>