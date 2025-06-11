<?php
define('API_INCLUDED', true);
require '../global/db_connect.php';

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
    // Vérification du Content-Type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    $isFormData = stripos($contentType, 'multipart/form-data') !== false;
    $isUrlEncoded = stripos($contentType, 'application/x-www-form-urlencoded') !== false;
    $isAppJSON = stripos($contentType, 'application/json') !== false;

    if (!$isFormData && !$isUrlEncoded && !$isAppJSON) {
        http_response_code(415);
        echo json_encode(["status" => "error", "message" => "Content-Type non supporté. Utilisez form-data ou x-www-form-urlencoded ou application/json."]);
        exit;
    }

    // Lecture des données
    if ($isAppJSON) {
        $json_data = file_get_contents("php://input");
    } else {
        // Gestion des données form-data ou urlencoded
        $json_data = json_encode($_POST);
    }

    if (empty($json_data)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Aucune donnée reçue"]);
        exit;
    }

    $data = json_decode($json_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            "status" => "error", 
            "message" => "JSON invalide",
            "details" => json_last_error_msg()
        ]);
        exit;
    }

    // Définition et validation des champs
    $fields = [
        'distance_parcourue' => ['type' => 'float', 'default' => 0.0, 'required' => true],
        'duree' => ['type' => 'string', 'default' => '0.0', 'required' => true],
        'vitesse' => ['type' => 'float', 'default' => 0.0, 'required' => true],
        'nb_coups' => ['type' => 'int', 'default' => 0, 'required' => true],
        'nb_droite' => ['type' => 'int', 'default' => 0, 'required' => false],
        'nb_gauche' => ['type' => 'int', 'default' => 0, 'required' => false],
        'nb_avancees' => ['type' => 'int', 'default' => 0, 'required' => false],
        'nb_reculs' => ['type' => 'int', 'default' => 0, 'required' => false]
    ];

    $clean_data = [];
    $errors = [];

    foreach ($fields as $field => $config) {
        if (!isset($data[$field])) {
            if ($config['required']) {
                $errors[] = "Le champ $field est obligatoire";
            }
            $clean_data[$field] = $config['default'];
            continue;
        }

        switch ($config['type']) {
            case 'float':
                $value = filter_var($data[$field], FILTER_VALIDATE_FLOAT);
                if ($value === false) {
                    $errors[] = "Le champ $field doit être un nombre décimal";
                    $value = $config['default'];
                }
                $clean_data[$field] = $value;
                break;
                
            case 'int':
                $value = filter_var($data[$field], FILTER_VALIDATE_INT);
                if ($value === false) {
                    $errors[] = "Le champ $field doit être un entier";
                    $value = $config['default'];
                }
                $clean_data[$field] = $value;
                break;
                
            case 'string':
                $clean_data[$field] = (string)$data[$field];
                break;
                
            default:
                $clean_data[$field] = $config['default'];
        }
    }

    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Validation des données échouée",
            "errors" => $errors
        ]);
        exit;
    }

    // Requête SQL avec gestion des erreurs
    try {
        $pdo->beginTransaction();
        
        $sql = "INSERT INTO robot (
                    distance_parcourue, 
                    duree, 
                    vitesse, 
                    nombre_coups, 
                    nombre_droite, 
                    nombre_gauche, 
                    nombre_avance, 
                    nombre_recule
                ) VALUES (
                    :distance_parcourue, 
                    :duree, 
                    :vitesse, 
                    :nb_coups, 
                    :nb_droite, 
                    :nb_gauche, 
                    :nb_avancees, 
                    :nb_reculs
                )";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindValue(':distance_parcourue', $clean_data['distance_parcourue'], PDO::PARAM_STR);
        $stmt->bindValue(':duree', $clean_data['duree'], PDO::PARAM_STR);
        $stmt->bindValue(':vitesse', $clean_data['vitesse'], PDO::PARAM_STR);
        $stmt->bindValue(':nb_coups', $clean_data['nb_coups'], PDO::PARAM_INT);
        $stmt->bindValue(':nb_droite', $clean_data['nb_droite'], PDO::PARAM_INT);
        $stmt->bindValue(':nb_gauche', $clean_data['nb_gauche'], PDO::PARAM_INT);
        $stmt->bindValue(':nb_avancees', $clean_data['nb_avancees'], PDO::PARAM_INT);
        $stmt->bindValue(':nb_reculs', $clean_data['nb_reculs'], PDO::PARAM_INT);
        
        if (!$stmt->execute()) {
            throw new PDOException("Erreur lors de l'exécution de la requête");
        }
        
        $pdo->commit();
        
        echo json_encode([
            "status" => "success",
            "message" => "Données enregistrées avec succès",
            "data" => $clean_data,
            "inserted_id" => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        error_log("Database error: " . $e->getMessage());
        echo json_encode([
            "status" => "error",
            "message" => "Erreur lors de l'enregistrement des données",
            "error_details" => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("System error: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Erreur système",
        "error_details" => $e->getMessage()
    ]);
}