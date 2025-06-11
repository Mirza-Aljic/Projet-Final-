<?php
// Inclusion du fichier de connexion à la base de données
$conn = include '../global/db_connect.php';

// Définition de l'en-tête JSON dès le début
header('Content-Type: application/json');

// Récupération des données d'entrée
$data = json_decode(file_get_contents('php://input'), true) ?? $_REQUEST;

if (!isset($data['id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Le paramètre ID est requis"]);
    exit;
}

$id = (int)$data['id'];

try {
    // Vérifier que la connexion $conn est bien établie
    if (!isset($conn)) {
        throw new PDOException("La connexion à la base de données a échoué");
    }

    // Récupération des noms des tables commençant par 'modele_stocke_ho'
    $stmt = $conn->query("SHOW TABLES LIKE 'modele_stocke_ih%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Aucune table correspondante trouvée."]);
        exit;
    }

    $deletedRows = 0;

    // Suppression de l'entrée dans chaque table trouvée
    foreach ($tables as $table) {
        $sql = "DELETE FROM `$table` WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deletedRows += $stmt->rowCount();
    }

    // Vérifier si au moins une ligne a été supprimée
    if ($deletedRows > 0) {
        echo json_encode(["status" => "success", "message" => "$deletedRows entrée(s) supprimée(s) avec succès."]);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "warning", "message" => "Aucune correspondance trouvée pour l'ID dans les tables."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erreur lors de la suppression : " . $e->getMessage()]);
}