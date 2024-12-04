<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Résultats</title>
</head>
<body>
    <div class="container">
        <h1>Soumission réussie</h1>
        <div>
            <h2>changelog</h2>
            <div class="code-container">
                <pre id="changelog">changelog</pre>
                <button id="button_changelog" onclick="copyText('changelog', 'button_changelog')">Copier</button>
            </div>
        </div>

        <div class="file-container">
            <div class="file-box">
                <h2>Fichier de l'ancienne version : last_version</h2>
                <div class="code-container">
                    <pre id="last_version_file">last_version_file</pre>
                    <button id="button_last_version_file" onclick="copyText('last_version_file', 'button_last_version_file')">Copier</button>
                </div>
            </div>

            <div class="file-box">
                <h2>Fichier de la nouvelle version : new_version</h2>
                <div class="code-container">
                    <pre id="new_version_file">new_version_file</pre>
                    <button id="button_new_version_file" onclick="copyText('new_version_file', 'button_new_version_file')">Copier</button>
                </div>
            </div>
        </div>
    </div>

    <script src="/../assets/js/script.js"></script>
</body>
</html>
<?php

?>