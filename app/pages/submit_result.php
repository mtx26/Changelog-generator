<?php
    $project_id = $_GET['id'] ?? 'inconnu';
    $last_version = $_GET['v1'] ?? 'inconnu';
    $new_version = $_GET['v2'] ?? 'inconnu';
    main($project_id, $last_version, $new_version);


    function main($project_id, $last_version, $new_version) {
        $API_project_versions = get_project_versions($project_id);

        $API_project_versions = json_decode($API_project_versions, true);

        $versions_dependencies = extract_dependencies_for_two_versions($API_project_versions, $last_version, $new_version);
        echo "<pre>";
        print_r($API_project_versions);
        print_r($versions_dependencies);
        echo "</pre>";
        //add_info($versions_dependencies);       

    }

    function get_project_versions($project_id){

        $apiUrl = "https://api.modrinth.com/v2/project/$project_id/version";
        $ch = curl_init($apiUrl);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ton_token",
            "Content-Type: application/json",
            "User-Agent: mtx26/changelog_generator/1.0.0 (mtx_26@outlook.be)",
        ]);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../cacert.pem');

        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $message = "Erreur : ".curl_errno($ch);
            echo $message;
            curl_close($ch);
            return NULL;
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                $message = "Erreur html: ".$httpCode;
                echo $message;
                curl_close($ch);
                return NULL;
            } else {
                curl_close($ch);
                return $response;
            }
        }

    }


    function extract_dependencies_for_two_versions($API_project_versions, $last_version, $new_version) {
        $versions_dependencies = [
            'last' => [
                'version' => $last_version,
                'dependencies' => []
            ],
            'new' => [
                'version' => $new_version,
                'dependencies' => []
            ]
        ];
    
        foreach ($API_project_versions as $project_version){
            $modpack_version = $project_version['version_number'];

            if ($modpack_version == $last_version) {
                if (isset($project_version['dependencies'])) {
                    $list_dependencies = $project_version['dependencies'];
                } else {
                    $list_dependencies = [];
                }
                $versions_dependencies["last"]["dependencies"] = array_merge($versions_dependencies["last"]["dependencies"], $list_dependencies);
            } elseif ($modpack_version == $new_version) {
                if (isset($project_version['dependencies'])) {
                    $list_dependencies = $project_version['dependencies'];
                } else {
                    $list_dependencies = [];
                }
                $versions_dependencies["new"]["dependencies"] = array_merge($versions_dependencies["new"]["dependencies"], $list_dependencies);
            }
        }
        if (empty($versions_dependencies['last']['dependencies']) || empty($versions_dependencies['new']['dependencies'])) {
            echo "Erreur : Pas de dépendance trouvée";
            return null;
        }
        return $versions_dependencies;
    
    }
?>

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