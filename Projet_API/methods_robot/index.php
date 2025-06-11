<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affichage des données</title>
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
            margin-top: 60px; /* Ajustement pour le bouton de retour */
            text-align: center;
        }

        /* Style pour les boutons */
        .options-container {
            margin-top: 20px;
        }
        .options-container a {
            text-decoration: none;
            margin: 10px;
        }
        .options-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .options-container button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Bouton retour vers global_index.php -->
    <a href="/website/global/global_index.php" class="back-button">⬅ Index</a>

    <div class="content">
        <h1>Bienvenue sur la table robot, cliquez sur l'option que vous souhaitez</h1>
        <div class="options-container">
            <a href="/website/methods_robot/get_data.php">
                <button>Récupérer les informations</button>
            </a>
            <a href="/website/methods_robot/post_data.php">
                <button>Envoyer des informations</button>
            </a>
            <a href="/website/methods_robot/delete_data_by_id.php">
                <button>Supprimer des informations</button>
            </a>
        </div>
    </div>
</body>
</html>