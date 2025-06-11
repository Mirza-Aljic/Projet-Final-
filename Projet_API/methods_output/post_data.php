<?php
define('API_INCLUDED', true); // Consistent with db_connect.php
include 'D:\Xampp\htdocs\website\global\db_connect.php';

header('Content-Type: application/json');

try {
    // Vérification méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée. Utilisez POST."]);
        exit;
    }

    // Vérification Content-Type
    if ($_SERVER['CONTENT_TYPE'] !== 'application/x-www-form-urlencoded' && 
        $_SERVER['CONTENT_TYPE'] !== 'multipart/form-data') {
        http_response_code(415);
        echo json_encode(["status" => "error", "message" => "Content-Type non supporté. Utilisez form-data."]);
        exit;
    }

    // Définition des champs requis avec validation
    $fields = [
        'tag' => ['type' => 'int', 'required' => true],];

    // Nettoyage et validation des données
    $clean_data = [];
    foreach ($fields as $field => $config) {
        if (!isset($_POST[$field]))
        {
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

    // Requête SQL préparée
    $sql = "INSERT INTO output (tag) 
            VALUES (:tag)";
    $stmt = $conn->prepare($sql);

    // Liaison des paramètres avec typage explicite
    $stmt->bindParam(':tag', $clean_data['tag'], PDO::PARAM_INT);

    // Exécution avec journalisation
    $stmt->execute();
    error_log("Données insérées dans output: " . json_encode($clean_data));

    // Réponse JSON de succès
    echo json_encode([
        "status" => "success",
        "message" => "Données enregistrées avec succès",
        "data" => $clean_data
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur base de données: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Erreur de base de données",
        "code" => $e->getCode()
    ]);
    exit;
}
?>