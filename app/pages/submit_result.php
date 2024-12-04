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
        print_r($versions_dependencies);
        echo "</pre>";

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


function fetch_with_retry($url, $retries = 3, $delay = 1) {
    $headers = [
        'User-Agent' => 'Your User Agent',
        // Ajouter d'autres en-têtes si nécessaire
    ];

    for ($attempt = 0; $attempt < $retries; $attempt++) {
        try {
            $response = make_request($url, $headers);
            $status_code = $response['status_code'];

            if ($status_code == 429 && $attempt < $retries - 1) {
                echo "Limite atteinte, nouvelle tentative dans {$delay} seconde(s)...\n";
                sleep($delay);
                $delay *= 2;  // Augmente progressivement le délai
            } else {
                return json_decode($response['body'], true);
            }
        } catch (Exception $e) {
            throw new Exception("Erreur lors de la requête: " . $e->getMessage());
        }
    }

    throw new Exception("Echec après $retries tentatives.");
}

function make_request($url, $headers) {
    // Utiliser cURL pour envoyer la requête HTTP
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response_body = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['body' => $response_body, 'status_code' => $status_code];
}

function add_name_version_number_to_dependencies($data) {
    $semaphore = 100;  // Limite à 100 requêtes simultanées
    $project_cache = [];
    $version_cache = [];

    // Crée un tableau pour contenir les tâches
    $tasks = [];

    foreach (['last', 'new'] as $version_key) {
        $version_data = $data[$version_key];
        $dependencies = $version_data["dependencies"];

        foreach ($dependencies as &$dependency) {
            $tasks[] = fetch_dependency_info($dependency, $project_cache, $version_cache);
        }
    }

    // Attendre que toutes les tâches soient complétées
    foreach ($tasks as $task) {
        $task();
    }

    return $data;
}

function fetch_dependency_info(&$dependency, &$project_cache, &$version_cache) {
    // Limite à 100 requêtes simultanées
    $project_id = $dependency['project_id'] ?? null;
    $version_id = $dependency['version_id'] ?? null;

    if ($project_id !== null) {
        if (isset($project_cache[$project_id])) {
            list($name, $loaders) = $project_cache[$project_id];
        } else {
            $project_url = "https://api.modrinth.com/v2/project/{$project_id}";
            $project_info = fetch_with_retry($project_url);
            $name = $project_info['title'] ?? "Nom non trouvé";
            $loaders = $project_info['loaders'] ?? "Aucun loaders";
            $project_cache[$project_id] = [$name, $loaders];
        }

        if (isset($version_cache[$version_id])) {
            $version_number = $version_cache[$version_id];
        } else {
            $version_url = "https://api.modrinth.com/v2/version/{$version_id}";
            $version_info = fetch_with_retry($version_url);
            $version_number = $version_info['version_number'] ?? "Numéro de version non trouvé";
            $version_cache[$version_id] = $version_number;
        }

        $dependency['name'] = $name;
        $dependency['loaders'] = $loaders;
        $dependency['version_number'] = $version_number;
    } else {
        $file_name = $dependency['file_name'];
        $dependency['name'] = (substr($file_name, -4) === ".jar") ? substr($file_name, 0, -4) : $file_name;
        $dependency['loaders'] = null;
        $dependency['version_number'] = null;
    }
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