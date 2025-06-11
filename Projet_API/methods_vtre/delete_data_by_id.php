<?php
// Inclusion du fichier de connexion à la base de données
include 'D:\Xampp\htdocs\website\global\db_connect.php';

// Vérification si une requête POST a été envoyée
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification et récupération de l'ID
    $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
    
    if ($id !== null) {
        // Récupération des noms des tables commençant par 'modele_stocke'
        $stmt = $conn->query("SHOW TABLES LIKE 'modele_stocke_ho%'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
        if (!empty($tables)) {
            foreach ($tables as $table) {
                // Requête SQL pour supprimer l'entrée correspondante dans chaque table
                $sql = "DELETE FROM $table WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
            }
            echo "Donnée supprimée avec succès dans toutes les tables concernées.";
        } else {
            echo "Aucune table correspondante trouvée.";
        }
    } else {
        echo "ID invalide.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<style>
        /* Style pour placer le bouton en haut à gauche */
        .back-button {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #f0f0f0;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            color: black;
            border-radius: 5px;
        }
        .back-button:hover {
            background-color: #dcdcdc;
        }
        
        /* Ajout d'un padding en haut pour éviter le chevauchement */
        .content {
            margin-top: 60px; /* Ajuste cette valeur si nécessaire */
            text-align: center;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer une donnée</title>
</head>
<body>
<a href="/website/methods_msh/index.php" class="back-button">⬅ Retour</a>

    <h1>Supprimer une donnée des tables modele_stocke*</h1>
    <form method="POST" action="">
        <label for="id">ID de l'entrée à supprimer :</label>
        <input type="number" id="id" name="id" required>
        <br>
        <button type="submit">Supprimer</button>
    </form>
</body>
</html>
