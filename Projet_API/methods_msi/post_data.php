<?php
define('API_INCLUDED', true);
require '../global/db_connect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');

// Set memory limits explicitly
ini_set('memory_limit', '128M');

try {
    // Check request method
    $is_post_request = (php_sapi_name() === 'cli') ? true : ($_SERVER['REQUEST_METHOD'] === 'POST');
    
    if (!$is_post_request) {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
        exit;
    }

    // Check Content-Type for web requests
    if (php_sapi_name() !== 'cli' && (!isset($_SERVER['CONTENT_TYPE']) || $_SERVER['CONTENT_TYPE'] !== 'application/json')) {
        http_response_code(415);
        echo json_encode(["status" => "error", "message" => "Unsupported Content-Type. Use application/json."]);
        exit;
    }

    // Get input data
    $json_data = (php_sapi_name() === 'cli') ? '{
        "input_index": 0,
        "hidden_index": 0,
        "weight": 0
    }' : file_get_contents("php://input");

    // Validate JSON
    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
        exit;
    }

    // Define and validate fields
    $clean_data = [
        'input_index' => isset($data['input_index']) ? filter_var($data['input_index'], FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) : 0,
        'hidden_index' => isset($data['hidden_index']) ? filter_var($data['hidden_index'], FILTER_VALIDATE_INT, ['options' => ['default' => 0]]) : 0,
        'weight' => isset($data['weight']) ? filter_var($data['weight'], FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]) : 0.0
    ];

    // Prepare and execute SQL
    $sql = "INSERT INTO modele_stocke_ih (input_index, hidden_index, weight)
            VALUES (:input_index, :hidden_index, :weight)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':input_index', $clean_data['input_index'], PDO::PARAM_INT);
    $stmt->bindParam(':hidden_index', $clean_data['hidden_index'], PDO::PARAM_INT);
    $stmt->bindParam(':weight', $clean_data['weight']);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Data saved successfully"
        ]);
    } else {
        throw new PDOException("Failed to execute statement");
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Database error",
        "details" => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "An error occurred",
        "details" => $e->getMessage()
    ]);
}