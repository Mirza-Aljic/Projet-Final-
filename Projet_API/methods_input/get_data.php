<?php
declare(strict_types=1);
error_reporting(E_ALL);

define('API_INCLUDED', true);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://127.0.0.1:5500');
header('Access-Control-Allow-Methods: GET');

// 1. Database connection with verification
try {
    $pdo = require __DIR__.'/../global/db_connect.php';
    if (!($pdo instanceof PDO)) {
        throw new RuntimeException('Invalid database connection');
    }
} catch (Throwable $e) {
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'Connection error',
        'details' => 'Unable to connect to database'
    ]));
}

try {
    // 2. Table name validation and security
    $table = 'input';
    
    // More strict validation than before
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $table)) {
        throw new InvalidArgumentException("Invalid table name format");
    }

    // 3. Prepared query with limit for safety
    $limit = 1000; // Security limit
    $query = $pdo->prepare("SELECT * FROM `".str_replace('`', '``', $table)."` LIMIT :limit");
    $query->bindValue(':limit', $limit, PDO::PARAM_INT);
    
    if (!$query->execute()) {
        throw new RuntimeException("Query execution failed");
    }

    // 4. Fetch results
    $data = $query->fetchAll(PDO::FETCH_ASSOC);
    $count = $query->rowCount();

    // 5. JSON response with success status
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => $count,
        'table' => $table
    ], JSON_PRETTY_PRINT);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Validation error',
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    $errorDetails = [
        'success' => false,
        'error' => 'Database error',
        'code' => $e->getCode(),
        'message' => $e->getMessage()
    ];
    
    // Add specific suggestions for certain error codes
    if ($e->getCode() === '42S02') { // Table doesn't exist
        $errorDetails['suggestion'] = 'Verify the table name exists';
    }
    
    http_response_code(500);
    echo json_encode($errorDetails);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unexpected error',
        'details' => $e->getMessage()
    ]);
}