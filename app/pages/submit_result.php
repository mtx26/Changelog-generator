<?php
    require_once '../vendor/autoload.php';
    use GuzzleHttp\Client;
    use GuzzleHttp\Pool;
    use GuzzleHttp\Psr7\Request;
    use GuzzleHttp\Psr7\Response;
    use GuzzleHttp\Exception\RequestException;
    use Psr\Http\Message\ResponseInterface;
    use GuzzleHttp\Promise\PromiseInterface;
    use GuzzleHttp\Promise;

    header('Content-Type: text/html; charset=utf-8');

    $project_id = $_GET['id'] ?? 'inconnu';
    $last_version = $_GET['v1'] ?? 'inconnu';
    $new_version = $_GET['v2'] ?? 'inconnu';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container bg-white mt-4 rounded shadow pb-3">
        <h1 class="text-center fw-bold mb-4">Soumission réussie</h1>
        <div class="progress" role="progressbar" aria-label="Danger striped example" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="height: 20px;">
            <div class="progress-bar progress-bar-striped" id="progress" style="width: 0%"></div>
        </div>
        <div class="mb-4">
            <h2 class="text-center fw-bold mb-3">Changelog</h2>
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <button id="button_changelog" class="btn btn-primary" onclick="copyText('changelog', 'button_changelog')">Copier le code</button>
                </div>
                <div class="card-body">
                    <pre id="changelog"></pre>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h2 class="fw-bold">Version <?php echo htmlspecialchars($last_version); ?></h2>
                        <button id="button_last_version_file" class="btn btn-primary" onclick="copyText('last_version_file', 'button_last_version_file')">Copier le code</button>
                    </div>
                    <div class="card-body">
                        <pre id="last_version_file" style="max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h2 class="fw-bold">Version <?php echo htmlspecialchars($new_version); ?></h2>
                        <button id="button_new_version_file" class="btn btn-primary" onclick="copyText('new_version_file', 'button_new_version_file')">Copier le code</button>
                    </div>
                    <div class="card-body">
                        <pre id="new_version_file" style="max-height: 300px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="../assets/js/script.js"></script>
</body>
</html>
<?php
    if (isset($_GET['id']) && isset($_GET['v1']) && isset($_GET['v2'])) {
        $API_project_versions = get_project_versions($project_id);
        if ($API_project_versions === false) {
            die("Erreur lors de la récupération des versions du projet");
        }

        $versions_dependencies = getVersions(json_decode($API_project_versions, true), $last_version, $new_version);
        $result = getName($versions_dependencies);
        $changelog = generateChangelog($result);

        // Préparer les données JSON
        $last_version_json = json_encode($result['last'], JSON_PRETTY_PRINT);
        $new_version_json = json_encode($result['new'], JSON_PRETTY_PRINT);

        // Mettre à jour le contenu avec JavaScript
        echo "<script>
            document.getElementById('changelog').textContent = " . json_encode($changelog) . ";
            document.getElementById('last_version_file').textContent = " . json_encode($last_version_json) . ";
            document.getElementById('new_version_file').textContent = " . json_encode($new_version_json) . ";
        </script>";
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

    function getVersions($API_project_versions, $last_version, $new_version) {
        // Ajout de logs pour le débogage
        error_log('Versions récupérées : ' . implode(', ', array_keys($API_project_versions)));
        error_log('Last version : ' . $last_version);
        error_log('New version : ' . $new_version);

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
        // Récupérer les versions du projet
        foreach ($API_project_versions as $project_version){
            $modpack_version = $project_version['version_number'];
            error_log('Vérification de la version modpack : ' . $modpack_version);
            if ($modpack_version == $last_version) {

                if (isset($project_version['dependencies'])) {
                    $list_dependencies = $project_version['dependencies'];
                    error_log('Dépendances trouvées pour la dernière version : ' . print_r($list_dependencies, true));
                } else {
                    $list_dependencies = [];
                }
                $versions_dependencies["last"]["dependencies"] = array_merge($versions_dependencies["last"]["dependencies"], $list_dependencies);
            } elseif ($modpack_version == $new_version) {
                if (isset($project_version['dependencies'])) {
                    $list_dependencies = $project_version['dependencies'];
                    error_log('Dépendances trouvées pour la nouvelle version : ' . print_r($list_dependencies, true));
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

    function getName($versions_dependencies) {

    
        function fetchAndUpdateDependencies(&$versions_dependencies, $concurrency = 2) {
            $client = new Client([
                'verify' => false, // Désactive la vérification SSL
            ]);

            // Générateur de requêtes avec métadonnées pour identifier chaque dépendance
            function requests(&$versions_dependencies) {
                foreach ($versions_dependencies as $versionKey => $versionData) {
                    foreach ($versionData['dependencies'] as $depKey => $dependency) {
                        $project_id = $dependency['project_id'];
                        if (!empty($project_id)) {
                            // Requête pour le projet (nom)
                            yield [
                                'request' => new Request('GET', 'https://api.modrinth.com/v2/project/' . $project_id),
                                'meta' => [
                                    'versionKey' => $versionKey, 
                                    'depKey' => $depKey,
                                    'type' => 'project'
                                ]
                            ];
                            
                            // Requête pour la version (version_number)
                            yield [
                                'request' => new Request('GET', 'https://api.modrinth.com/v2/version/' . $dependency['version_id']),
                                'meta' => [
                                    'versionKey' => $versionKey, 
                                    'depKey' => $depKey,
                                    'type' => 'version'
                                ]
                            ];
                        }
                        else {
                            $filename = str_replace('.jar', '', $dependency['file_name']);
                            // Met à jour directement le nom dans versions_dependencies
                            $versions_dependencies[$versionKey]['dependencies'][$depKey]['name'] = $filename;
                        }
                    }
                }
                
            }

            $requests = [];
            $metaDataMap = [];

            foreach (requests($versions_dependencies) as $index => $item) {
                $requests[$index] = $item['request'];
                $metaDataMap[$index] = $item['meta'];
            }

            $totalRequests = count($requests);
            $completedRequests = 0;


            // Pool pour gérer les requêtes avec un maximum de $concurrency simultanées
            $pool = new Pool($client, $requests, [
                'concurrency' => 5, // 5 requêtes simultanées
                'fulfilled' => function ($response, $index) use (&$versions_dependencies, $metaDataMap, &$completedRequests, $totalRequests) {
                    $meta = $metaDataMap[$index];
                    $data = json_decode($response->getBody(), true);

                    if ($meta['type'] === 'project') {
                        if (!isset($data['title'])) {
                            echo "<div style='color: red; margin: 5px 0;'>❌ Erreur: Pas de titre pour le projet " . $meta['depKey'] . "</div>";
                        }
                        $versions_dependencies[$meta['versionKey']]['dependencies'][$meta['depKey']]['name'] = $data['title'] ?? 'Nom introuvable';
                    } else if ($meta['type'] === 'version') {
                        if (!isset($data['version_number'])) {
                            echo "<div style='color: red; margin: 5px 0;'>❌ Erreur: Pas de version_number pour " . $meta['depKey'] . "</div>";
                        }
                        $versions_dependencies[$meta['versionKey']]['dependencies'][$meta['depKey']]['version_number'] = $data['version_number'] ?? '';
                    }

                    $completedRequests++;
                    $percentage = round(($completedRequests / $totalRequests) * 100);
                    echo "<script>
                        document.getElementById('progress').style.width = '$percentage%';
                        document.getElementById('progress').textContent = '$percentage%';
                    </script>";
                    flush();
                },
       'rejected' => function ($reason, $index) use (&$completedRequests, $totalRequests, $metaDataMap, $client, $requests, &$versions_dependencies) {
                    if ($reason instanceof \GuzzleHttp\Exception\ClientException && $reason->getResponse()->getStatusCode() === 429) {
                        $response = json_decode($reason->getResponse()->getBody(), true);
                        if (isset($response['description'])) {
                            echo "<div style='color: orange; margin: 5px 0;'>⚠️ Rate limit - Attente: " . $response['description'] . "</div>";
                            // Extraire le temps d'attente du message
                            if (preg_match('/wait (\d+) milliseconds/', $response['description'], $matches)) {
                                $waitTime = intval($matches[1]) + 50; // Petite marge de 50ms
                                usleep($waitTime * 1000);
                                try {
                                    $response = $client->send($requests[$index]);
                                    // Si la requête réussit, traiter la réponse comme dans fulfilled
                                    $meta = $metaDataMap[$index];
                                    $data = json_decode($response->getBody(), true);
                                    if ($meta['type'] === 'project') {
                                        if (!isset($data['title'])) {
                                            echo "<div style='color: red; margin: 5px 0;'>❌ Erreur: Pas de titre pour le projet " . $meta['depKey'] . "</div>";
                                        }
                                        $versions_dependencies[$meta['versionKey']]['dependencies'][$meta['depKey']]['name'] = $data['title'] ?? 'Nom introuvable';
                                    } else if ($meta['type'] === 'version') {
                                        if (!isset($data['version_number'])) {
                                            echo "<div style='color: red; margin: 5px 0;'>❌ Erreur: Pas de version_number pour " . $meta['depKey'] . "</div>";
                                        }
                                        $versions_dependencies[$meta['versionKey']]['dependencies'][$meta['depKey']]['version_number'] = $data['version_number'] ?? '';
                                    }
                                    return;
                                } catch (\Exception $e) {
                                    echo "<div style='color: red; margin: 5px 0;'>❌ Erreur après retry: " . $e->getMessage() . " pour " . $metaDataMap[$index]['depKey'] . "</div>";
                                }
                            }
                        }
                    } else {
                                // Afficher l'erreur originale si ce n'est pas une erreur 429
                        echo "<div style='color: red; margin: 5px 0;'>❌ Erreur: " . $reason->getMessage() . " pour " . $metaDataMap[$index]['depKey'] . "</div>";
                    }
                    
                    // Si ce n'est pas une erreur 429 ou si le retry a échoué
                    $completedRequests++;
                    echo "<script>
                        document.getElementById('progress').style.width = '100%';
                        document.getElementById('progress').textContent = '100%';
                    </script>";
                    flush();
                },
            ]);
    
            // Exécuter les requêtes
 $pool->promise()->wait();
        }
    
        // Lancer la récupération des informations et mise à jour de $versions_dependencies
        fetchAndUpdateDependencies($versions_dependencies, 5);
    
        // Retourne la structure mise à jour
        return $versions_dependencies;
    }


    function generateChangelog($data) {
        $lastDependencies = $data['last'];
        $newDependencies = $data['new'];
    
        $lastIndexed = [];
        $newIndexed = [];
        $removedProjectsWithNullId = [];
        $addedProjectsWithNullId = [];
    
        foreach ($lastDependencies['dependencies'] as $dep) {
            $projectId = $dep['project_id'];
            if ($projectId === null) {
                $name = $dep['name'];
                $removedProjectsWithNullId[$name] = $dep;
            } else {
     $lastIndexed[$projectId] = $dep;
            }
        }
    
        foreach ($newDependencies['dependencies'] as $dep) {
            $projectId = $dep['project_id'];
            if ($projectId === null) {
                $name = $dep['name'];
                $addedProjectsWithNullId[$name] = $dep;
            } else {
                $newIndexed[$projectId] = $dep;
            }
        }
    
        $toRemoveFromRemoved = [];
        $toRemoveFromAdded = [];
    
        foreach ($removedProjectsWithNullId as $name => $dep) {
            if (isset($addedProjectsWithNullId[$name])) {
                $toRemoveFromRemoved[] = $name;
                $toRemoveFromAdded[] = $name;
       }
        }
    
        foreach ($toRemoveFromRemoved as $name) {
            unset($removedProjectsWithNullId[$name]);
        }
    
        foreach ($toRemoveFromAdded as $name) {
            unset($addedProjectsWithNullId[$name]);
        }
    
        $changelog = "";
    
        // Added projects
        $addedProjects = array_diff(array_keys($newIndexed), array_keys($lastIndexed));
        if (!empty($addedProjects)) {
            $changelog .= "## Added\n";
            foreach ($addedProjects as $projectId) {
                $changelog .= "- **" . $newIndexed[$projectId]['name'] . ":** " . $newIndexed[$projectId]['version_number'] . "\n";
            }
        }
    
        if (empty($addedProjects) && !empty($addedProjectsWithNullId)) {
            $changelog .= "## Added\n";
            foreach ($addedProjectsWithNullId as $name => $dep) {
                $changelog .= "- **$name**\n";
            }
        }
    
        // Removed projects
        $removedProjects = array_diff(array_keys($lastIndexed), array_keys($newIndexed));
        if (!empty($removedProjects)) {
            $changelog .= "## Removed\n";
            foreach ($removedProjects as $projectId) {
                $changelog .= "- **" . $lastIndexed[$projectId]['name'] . "**\n";
            }
        }
    
        if (empty($removedProjects) && !empty($removedProjectsWithNullId)) {
            $changelog .= "## Removed\n";
            foreach ($removedProjectsWithNullId as $name => $dep) {
                $changelog .= "- **$name**\n";
                 }
        }
    
        // Updated projects
        $updatedProjects = array_filter(array_keys($lastIndexed), function ($projectId) use ($lastIndexed, $newIndexed) {
            return isset($newIndexed[$projectId]) && $lastIndexed[$projectId]['version_number'] !== $newIndexed[$projectId]['version_number'];
        });
    
        if (!empty($updatedProjects)) {
            $changelog .= "## Updated\n";
            foreach ($updatedProjects as $projectId) {
                $changelog .= "- **" . $lastIndexed[$projectId]['name'] . "**\n";
                $changelog .= "   - Old Version: " . $lastIndexed[$projectId]['version_number'] . "\n";
                $changelog .= "   - New Version: " . $newIndexed[$projectId]['version_number'] . "\n";
            }
        }
    
        return $changelog;
    }


    function generateVersionFile($indexedData) {
        $output = "";
        foreach ($indexedData as $projectId => $data) {
            $output .= $data['name'] . ": " . $data['version_number'] . "\n";
        }
        return $output;
    }
?>