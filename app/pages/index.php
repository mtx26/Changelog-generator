<?php

    $API_project_versions = null;
    $versions_list = null;
    $id = null;

    // Vérifier si la requête est de type POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = "";
        $id = $_POST['id'];
        // Vérifier si l'ID est défini
        if ($id === "") {
                $message = "Veillez entrer un ID.";
                echo "<script>alert('$message');</script>";
        } else {
            
        
            // Récupérer les versions du projet
            $API_project_versions_json = API_search_project($id);    
            // echo json_encode($API_project_versions_json);
            // decoder la réponse JSON
            if ($API_project_versions_json !== null) {

                $API_project_versions_table = json_decode($API_project_versions_json, true);
                $versions_list = get_list_versions($API_project_versions_table);
                $versions_list_json = json_encode($versions_list);
    
                
            }


        }
    }

    // Fonction pour récupérer les versions du projet
    function  API_search_project($project_id){
        // Récupérer les versions du projet + erreur

        $API_project_versions = get_project_versions($project_id);
        // Vérifier si l'API a renvoyé des versions
        if ($API_project_versions !== null) {

            return $API_project_versions;
        // Si l'API n'a pas renvoyé de versions, renvoyer un message d'erreur
        } else {
            return null;
        }


    }

    // Fonction pour récupérer les versions du projet
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

    function get_list_versions($API_project_versions_table){
        $versions_list = [
            "minecraft" => [],
            "fabric" => [],
            "quilt" => [],
            "forge" => [],
            "neoforge" => [],
            "other" => []
        ];

        foreach ($API_project_versions_table as $versions) {

            $version = $versions["version_number"];
            $loaders = $versions["loaders"];

            foreach ($loaders as $loader) {
                if (in_array($loader, ['fabric', 'quilt', 'forge', 'minecraft', 'neoforge'])) {
                    $versions_list[$loader][] = array("version" => $version, "loaders" => $loaders);
                } else {
                    // Si un loader ne correspond pas aux catégories connues, ajouter à "other"
                    $versions_list['other'][] = array("version" => $version, "loaders" => $loaders);
                }
            }
        }
    return $versions_list;

    }

?>


<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="../assets/css/style.css">
        <title>Changelog Generator</title>
    </head>
    <body>
        <div class="container">
            <h1>Changelog Generator</h1>
            <form action="" method="post">
                <div class="withe_box"id="form-container">
                    <label for="id">Project ID:</label>
                    <input type="text" id="id" name="id" value="<?php echo $id; ?>">
                    <button type="submit" >Charger les versions</button>
                </div>
            </form>
            <br>
            <div class="withe_box">
                <div>
                    <label>Anciennes versions :</label>
                    <select class="dropdown" id="version-dropdown1">
                        <option value="" disabled selected>Anciennes versions</option>
                    </select>
                </div>
                <br>
                <div>
                    <label>Nouvelles versions :</label>
                    <select class="dropdown" id="version-dropdown2">
                        <option value="" disabled selected>Nouvelles versions</option>
                    </select>
                </div>
                <button type="button" onclick="getVersion()">Generate Changelog</button>
            </div>

        
        </div>
    </body>
    <script>
        const data = <?php echo $versions_list_json; ?>;

        function fillDropdown(dropdown, data) {
            // Vider l'élément dropdown
            dropdown.innerHTML = '';

            // Ajoute une option par défaut
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.disabled = true;
            defaultOption.selected = true;
            defaultOption.textContent = 'Choisissez une version';
            dropdown.appendChild(defaultOption);

            // Parcourir chaque type de loader dans 'data'
            for (const loaderType in data) {
                // Récupérer la liste des versions pour ce loader
                const versions = data[loaderType];

                // Si la liste de versions n'est pas vide, ajouter les options
                if (versions.length > 0) {
                    // Créer une option de groupe (optgroup) pour chaque type de loader
                    const optgroup = document.createElement('optgroup');
                    optgroup.label = loaderType.charAt(0).toUpperCase() + loaderType.slice(1); // Capitaliser le premier caractère (fabric -> Fabric)

                    versions.forEach(item => {
                        const option = document.createElement('option');
                        const loaders = Array.isArray(item.loaders) && item.loaders.length > 0
                            ? item.loaders.join(', ') // Joindre les loaders avec des virgules
                            : 'Inconnu'; // Si aucun loader n'est défini

                        option.value = item.version; // La valeur de l'option est la version
                        option.textContent = `${item.version} (${loaders})`; // Le texte affiché inclut la version et les loaders

                        optgroup.appendChild(option); // Ajouter l'option à l'optgroup
                    });

                dropdown.appendChild(optgroup); // Ajouter l'optgroup au dropdown
                }
            }
        }
        // Remplir les dropdowns avec les données reçues
        fillDropdown(document.getElementById('version-dropdown1'), data);
        fillDropdown(document.getElementById('version-dropdown2'), data);

        function getVersion() {
            const project_id = document.getElementById('id').value;
            const version1 = document.getElementById('version-dropdown1').value;
            const version2 = document.getElementById('version-dropdown2').value;
            // Effectuer des actions avec les versions choisies
            const url =`submit_result.php?id=${project_id}&v1=${version1}&v2=${version2}`;
            window.location.assign(url);
        }

    </script>
</html>