<?php
    // Chargement des dépendances nécessaires
    require_once '../vendor/autoload.php';
    use GuzzleHttp\Client;
    use GuzzleHttp\Pool;
    use GuzzleHttp\Psr7\Request;
    use GuzzleHttp\Psr7\Response;
    use GuzzleHttp\Exception\RequestException;
    use Psr\Http\Message\ResponseInterface;
    use GuzzleHttp\Promise\PromiseInterface;
    use GuzzleHttp\Promise;

    // Définit le type de contenu de la réponse
    header('Content-Type: text/html; charset=utf-8');

    // Récupère les paramètres de requête ou utilise des valeurs par défaut
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
    <div class="container mt-4">
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
                        <pre id="last_version_file"></pre>
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
                        <pre id="new_version_file"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="../assets/js/script.js"></script>
</body>
</html>
<?php
    // Vérifie si les paramètres requis sont présents
    if (isset($_GET['id']) && isset($_GET['v1']) && isset($_GET['v2'])) {
        // Récupère les versions du projet via l'API
        $API_project_versions = get_project_versions($project_id);
        if ($API_project_versions === false) {
            die("Erreur lors de la récupération des versions du projet");
        }

        // Récupère les dépendances des versions
        $versions_dependencies = getVersions(json_decode($API_project_versions, true), $last_version, $new_version);
        $result = getName($versions_dependencies);
        $changelog = generateChangelog($result);

        // Prépare les données JSON pour les versions
        $last_version_json = json_encode($versions_dependencies['last'], JSON_PRETTY_PRINT);
        $new_version_json = json_encode($versions_dependencies['new'], JSON_PRETTY_PRINT);

        // Met à jour le contenu de la page avec les données JSON
        echo "<script>\n            document.getElementById('changelog').textContent = " . json_encode($changelog) . ";\n            document.getElementById('last_version_file').textContent = " . json_encode($last_version_json) . ";\n            document.getElementById('new_version_file').textContent = " . json_encode($new_version_json) . ";\n        </script>";
    }

    // Fonction pour récupérer les versions du projet
    function get_project_versions($project_id) {
        // Définit l'URL de l'API pour récupérer les versions du projet
        $apiUrl = "https://api.modrinth.com/v2/project/$project_id/version";
        $ch = curl_init($apiUrl);

        // Configure les options de cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ton_token",
            "Content-Type: application/json",
            "User-Agent: mtx26/changelog_generator/1.0.0 (mtx_26@outlook.be)",
        ]);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../cacert.pem');

        // Exécute la requête cURL
        $response = curl_exec($ch);

        // Vérifie les erreurs de cURL
        if (curl_errno($ch)) {
            echo "Erreur : ".curl_errno($ch);
            curl_close($ch);
            return NULL;
        }

        // Vérifie le code de réponse HTTP
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            echo "Erreur html: ".$httpCode;
            curl_close($ch);
            return NULL;
        }

        // Ferme la session cURL et retourne la réponse
        curl_close($ch);
        return $response;
    }

    // Fonction pour récupérer les dépendances des versions
    function getVersions($API_project_versions, $last_version, $new_version) {
        // Initialise les dépendances pour les versions
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

        // Récupère les versions du projet
        foreach ($API_project_versions as $project_version) {
            $modpack_version = $project_version['version_number'];

            // Vérifie si la version correspond à la dernière ou à la nouvelle
            if ($modpack_version == $last_version || $modpack_version == $new_version) {
                $list_dependencies = $project_version['dependencies'] ?? [];
                $key = $modpack_version == $last_version ? 'last' : 'new';
                $versions_dependencies[$key]['dependencies'] = array_merge($versions_dependencies[$key]['dependencies'], $list_dependencies);
            }
        }

        // Vérifie si des dépendances ont été trouvées
        if (empty($versions_dependencies['last']['dependencies']) || empty($versions_dependencies['new']['dependencies'])) {
            echo "Erreur : Pas de dépendance trouvée";
            return null;
        }
        return $versions_dependencies;
    }

    // Fonction pour récupérer et mettre à jour les dépendances
    function getName($versions_dependencies) {
        // Fonction pour récupérer et mettre à jour les dépendances
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
                        } else {
                            // Met à jour directement le nom dans versions_dependencies
                            $filename = str_replace('.jar', '', $dependency['file_name']);
                            $versions_dependencies[$versionKey]['dependencies'][$depKey]['name'] = $filename;
                        }
                    }
                }
            }

            // Pool pour gérer les requêtes avec un maximum de $concurrency simultanées
            $pool = new Pool($client, $requests, [
                'concurrency' => $concurrency,
                'fulfilled' => function ($response, $index) use (&$versions_dependencies, $metaDataMap, &$completedRequests, $totalRequests) {
                    // Traite la réponse réussie
                    $meta = $metaDataMap[$index];
                    $data = json_decode($response->getBody(), true);

                    // Met à jour les dépendances selon le type
                    if ($meta['type'] === 'project') {
                        $versions_dependencies[$meta['versionKey']]['dependencies'][$meta['depKey']]['name'] = $data['title'] ?? 'Nom introuvable';
                    } else if ($meta['type'] === 'version') {
                        $versions_dependencies[$meta['versionKey']]['dependencies'][$meta['depKey']]['version_number'] = $data['version_number'] ?? '';
                    }

                    // Met à jour la barre de progression
                    $completedRequests++;
                    $percentage = round(($completedRequests / $totalRequests) * 100);
                    echo "<script>\n                        document.getElementById('progress').style.width = '$percentage%';\n                        document.getElementById('progress').textContent = '$percentage%';\n                    </script>";
                    flush();
                },
                'rejected' => function ($reason, $index) use (&$completedRequests, $totalRequests, $metaDataMap, $client, $requests, &$versions_dependencies) {
                    // Gère les erreurs de requêtes
                    if ($reason instanceof \GuzzleHttp\Exception\ClientException && $reason->getResponse()->getStatusCode() === 429) {
                        // Gère le cas de limite de requêtes
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
                                        $versions_dependencies[$meta['versionKey']]['dependencies'][$meta['depKey']]['name'] = $data['title'] ?? 'Nom introuvable';
                                    } else if ($meta['type'] === 'version') {
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
                    echo "<script>\n                        document.getElementById('progress').style.width = '100%';\n                        document.getElementById('progress').textContent = '100%';\n                    </script>";
                    flush();
                },
            ]);

            // Exécute les requêtes
            $pool->promise()->wait();
        }

        // Retourne la structure mise à jour
        return $versions_dependencies;
    }

    // Fonction pour générer le changelog
    function generateChangelog($data) {
        $lastDependencies = $data['last'];
        $newDependencies = $data['new'];

        // Indexe les dépendances par projet
        $lastIndexed = [];
        $newIndexed = [];
        $removedProjectsWithNullId = [];
        $addedProjectsWithNullId = [];

        foreach ($lastDependencies['dependencies'] as $dep) {
            $projectId = $dep['project_id'];
            if ($projectId === null) {
                $removedProjectsWithNullId[$dep['name']] = $dep;
            } else {
                $lastIndexed[$projectId] = $dep;
            }
        }

        foreach ($newDependencies['dependencies'] as $dep) {
            $projectId = $dep['project_id'];
            if ($projectId === null) {
                $addedProjectsWithNullId[$dep['name']] = $dep;
            } else {
                $newIndexed[$projectId] = $dep;
            }
        }

        // Génère le changelog en fonction des dépendances ajoutées, supprimées et mises à jour
        $changelog = "";

        // Ajoute les projets ajoutés au changelog
        $addedProjects = array_diff(array_keys($newIndexed), array_keys($lastIndexed));
        if (!empty($addedProjects)) {
            $changelog .= "## Added\n";
            foreach ($addedProjects as $projectId) {
                $changelog .= "- **" . $newIndexed[$projectId]['name'] . ":** " . $newIndexed[$projectId]['version_number'] . "\n";
            }
        }

        // Ajoute les projets supprimés au changelog
        $removedProjects = array_diff(array_keys($lastIndexed), array_keys($newIndexed));
        if (!empty($removedProjects)) {
            $changelog .= "## Removed\n";
            foreach ($removedProjects as $projectId) {
                $changelog .= "- **" . $lastIndexed[$projectId]['name'] . "**\n";
            }
        }

        // Ajoute les projets mis à jour au changelog
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

    // Fonction pour générer le contenu du fichier de version
    function generateVersionFile($indexedData) {
        $output = "";
        foreach ($indexedData as $projectId => $data) {
            $output .= $data['name'] . ": " . $data['version_number'] . "\n";
        }
        return $output;
    }
?>