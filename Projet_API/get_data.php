<?php
require __DIR__ . '/../global/db_connect.php';

try {
    $table = 'Simulation';
    $sql = "SELECT * FROM " . $table;
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($rows, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}