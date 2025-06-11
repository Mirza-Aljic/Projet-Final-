<?php
declare(strict_types=1);
error_reporting(E_ALL);

// 1. Gestion CORS complète
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 3600");
header("Vary: Origin");

// Réponse immédiate pour les requêtes OPTIONS (pré-vol)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// 2. Chargement et validation du .env
$envPath = __DIR__.'/.env';
if (!file_exists($envPath)) {
    http_response_code(500);
    die(json_encode(["error" => "Configuration manquante", "details" => "Fichier .env introuvable"]));
}

$envVars = parse_ini_file($envPath);
if ($envVars === false) {
    http_response_code(500);
    die(json_encode(["error" => "Configuration invalide", "details" => "Erreur de lecture du .env"]));
}

// 3. Vérification des variables requises
$requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($requiredVars as $var) {
    if (empty($envVars[$var])) {
        http_response_code(500);
        die(json_encode(["error" => "Configuration incomplète", "details" => "Variable $var manquante"]));
    }
}

// 4. Connexion à la base de données avec gestion SSL
try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ];

    // Ajout des options SSL si un certificat est spécifié
    if (!empty($envVars['SSL_CERT'])) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $envVars['SSL_CERT'];
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    $dsn = "mysql:host={$envVars['DB_HOST']};dbname={$envVars['DB_NAME']};charset=utf8mb4";
    $pdo = new PDO($dsn, $envVars['DB_USER'], $envVars['DB_PASS'], $options);

    // Test de la connexion
    $pdo->query("SELECT 1")->fetch();

    return $pdo; // Correction de la variable (était $do)

} catch (PDOException $e) {
    error_log("DB Connection Error: ".$e->getMessage());
    http_response_code(500);
    die(json_encode([
        "error" => "Erreur de base de données",
        "details" => "Impossible de se connecter au serveur",
        "technical" => $e->getMessage()
    ]));
} catch (Throwable $e) {
    http_response_code(500);
    die(json_encode([
        "error" => "Erreur inattendue",
        "details" => $e->getMessage()
    ]));
}