<?php
// Inclusion du fichier de connexion à la base de données
include 'D:\Xampp\htdocs\website\global\db_connect.php';

// Vérification si une requête POST a été envoyée
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification et récupération de l'ID
    $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
    
    if ($id !== null) {
        try {
            // Récupération des noms des tables commençant par 'ih'
            $stmt = $conn->query("SHOW TABLES LIKE 'modele_stocke_ih%'");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($tables)) {
                // Suppression de l'entrée dans chaque table correspondante
                foreach ($tables as $table) {
                    $sql = "DELETE FROM $table WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                }
                $message = "Donnée supprimée avec succès dans toutes les tables concernées.";
            } else {
                $message = "Aucune table correspondante trouvée.";
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $message = "ID invalide.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer une donnée</title>
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

        /* Style pour le contenu principal */
        .content {
            margin-top: 60px; /* Ajustez cette valeur si nécessaire */
            text-align: center;
        }

        /* Style pour le formulaire */
        form {
            display: inline-block;
            text-align: left;
            margin-top: 20px;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        input {
            margin-bottom: 10px;
            padding: 5px;
            width: 100%;
        }
        button {
            background-color: #dc3545; /* Rouge pour indiquer une action de suppression */
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #c82333;
        }

        /* Style pour les messages */
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            color: white;
            background-color: #28a745; /* Vert pour succès */
        }
        .message.error {
            background-color: #dc3545; /* Rouge pour erreur */
        }
    </style>
</head>
<body>
    <!-- Bouton de retour -->
    <a href="/website/methods_robot/index.php" class="back-button">⬅ Retour</a>

    <div class="content">
        <h1>Supprimer une donnée de la table robot</h1>
        <form method="POST" action="">
            <label for="id">ID de l'entrée à supprimer :</label>
            <input type="number" id="id" name="id" required>
            <br>
            <button type="submit">Supprimer</button>
        </form>

        <!-- Affichage des messages -->
        <?php if (!empty($message)) : ?>
            <div class="message <?php echo (strpos($message, 'Erreur') !== false || strpos($message, 'invalide') !== false) ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>