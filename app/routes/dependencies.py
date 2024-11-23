import requests
import json
from flask import Flask, request, render_template, Blueprint


dependencies = Blueprint("dependencies", __name__) # Créer un blueprint "dependencies"

@dependencies.route("/")
def index():
    return render_template("index.html") # Afficher la page "index.html"


# Ajouter les dependances dans le fichier "List_dependencies.json"
def add_dependencies(modpack_version, name, version_id, version_number, loaders):
    
    with open('app/data/list_dependencies.json', 'r') as file: # Ouvrir le fichier en mode lecture
        modpack_info = json.load(file) # Charger le contenu du fichier
    
    if modpack_version not in modpack_info: # Verifier si la version du modpack n'est pas dans le fichier

        # Ajouter la version du modpack et ses dependances
        modpack_info[modpack_version] = {
            version_id: {
                'name': name,
                'version_id': version_id,
                'version_number': version_number,
                'loaders': loaders
            }
        }

    else:
        # Ajouter la dependance à la version du modpack déjà existante
        modpack_info[modpack_version][version_id] = {
            
            'name': name,
            'version_id': version_id,
            'version_number': version_number,
            'loaders': loaders

        }

    # Enregistrer la version du modpack et ses dependances dans le fichier "List_dependencies.json"
    with open('app/data/list_dependencies.json', 'w') as file:
        json.dump(modpack_info, file, indent=4)



# Extraire les noms et versions des dependances avec le "version_id"
def get_name_and_version(version_id, modpack_version, dependency, headers):

    if version_id == None: # Verifier si le "version_id" est vide

        # Si le "version_id" est vide, 
        # extraire le "file_name",
        # remplacer le "version_id" et le "name" par le "file_name" moins le .jar

        file_name = dependency.get("file_name", []) # Extraire le "file_name"
        file_name = file_name[:-4] if file_name.endswith(".jar") else file_name # Retirer le .jar du "file_name"

        name = file_name            # Remplacer le "name" par le "file_name"
        version_id = file_name      # Remplacer le "version_id" par le "file_name"     
        version_number = 'none'     # Remplacer le "version_number" par "none"
        loaders = 'none'            # Remplacer le "loaders" par "none"

        add_dependencies(modpack_version, name, version_id, version_number, loaders)


    # Extraire les données de la version pour ceux dont le "version_id" n'est pas vide
    else:
        dependencies_version_url = f"https://api.modrinth.com/v2/version/{version_id}"  # URL de l'API Modrinth pour la version

        reponse = requests.get(dependencies_version_url, headers=headers) # Obtenir les données de l'API pour avoir les infos des dependances

        # Vérifier si la requête a réussi
        if reponse.status_code == 200:
            data_version = reponse.json()
        else:
            print(f"Error: {reponse.status_code}")

        name = data_version.get("name")                         # Extraire le "name"
        version_number = data_version.get("version_number", []) # Extraire la "version_number" (Version de la dependance)
        loaders = data_version.get("loaders", [])               # Extraire le "loaders" (Fabric, Forge, Quilt, Minecraft, etc)

        add_dependencies(modpack_version, name, version_id, version_number, loaders)  # Ajouter les dependances au fichier List_dependencies.json



# Extraire les dependances avec entry
def get_dependencies(dependencies, modpack_version, headers):
    for dependency in dependencies: 

        version_id = dependency.get("version_id", [])   # Extraire l'ID de la version

        get_name_and_version(version_id, modpack_version, dependency, headers) # Extraire le nom, la version_number et le loaders


# Extraire les dependances pour version du modpack
def find_dependencies(data, headers, modpack_versions, counter = 0):
    #Extraire les noms et versions des dependances
    for entry in data:
            
        modpack_version = entry.get("version_number", []) # Extraire les versions du modpack

        # Vérifier si la version du modpack est dans la liste des 2 versions
        if modpack_version in modpack_versions:

            dependencies  =  entry.get("dependencies", []) # Extraire les dependances de la version dans l'API

            get_dependencies(dependencies, modpack_version, headers) # Extraire les dependances

        else: 
            print(f"Error: {modpack_version} not in {modpack_versions}") # Si la version du modpack n'est pas dans la liste
        
        counter += 1  # Incremente le compteur

        # Stopper la boucle une fois les 2 versions trouvées
        if counter >= 2:
            break

    
    print("Fin de la recherche des dependances !")    # Boucle terminée

# Nettoyer le fichier List_dependencies.json    
def clean_file(files):
    with open(files, "w") as file: # Ouvrir le fichier en mode ecriture
        file.write("{}") # Supprimer le contenu
    

# Initialiser le fichier List_dependencies.json
def initialize_id(id, last_version, new_version):

    version_url = f"https://api.modrinth.com/v2/project/{id}/version"    # URL de l'API Modrinth pour la listes des versions

    # En-tete de la requête
    headers = {
        "User-Agent": "mtx26/changelog_generator/1.0.0 (mtx_26@outlook.be)"
    }

    reponse = requests.get(version_url, headers=headers)    # Obtenir les données de l'API

    # Vérifier si la requête a réussi
    if reponse.status_code == 200: 
        data = reponse.json()
        print("Obtention des données de l'API Modrinth réussie !")
    else:
        print(f"Error: {reponse.status_code}")

    modpack_versions = [last_version, new_version]    # Ajouter les 2 versions du modpack dans une liste

    files = "app/data/list_dependencies.json"   # Chemin vers le fichier
    clean_file(files)   # Nettoyer le fichier "List_dependencies.json"

    find_dependencies(data, headers, modpack_versions)


# Recuperer les informations du formulaire dans template/index.html
@dependencies.route("/submit", methods=["POST"]) # Recuperer les informations
def submit():

    last_version = request.form.get("last_version") # Ancienne version du modpack
    new_version = request.form.get("new_version") # Nouvelle version du modpack

    id = request.form.get("id") # ID du modpack

    initialize_id(id, last_version, new_version) # Initialiser le fichier "List_dependencies.json"

    return f"Vous avez entré : {last_version} et {new_version} et {id}"