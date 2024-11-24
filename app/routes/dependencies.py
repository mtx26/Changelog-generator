import requests
import json
from flask import Flask, request, render_template, Blueprint


dependencies = Blueprint("dependencies", __name__) # Créer un blueprint "dependencies"

@dependencies.route("/")
def index():
    return render_template("index.html") # Afficher la page "index.html"


def update_dependencies_file(file_path, name, version_id, version_number, loaders):
    try:
        with open(file_path, 'r') as file:
            modpack_info = json.load(file)
    except (FileNotFoundError, json.JSONDecodeError):
            print(f"Erreur : impossible de charger le fichier {file_path}.")
            return {}
        
    dependencies = modpack_info.get("dependencies", [])
    
    dependencies.append({
        'name': name,
        'version_id': version_id,
        'version_number': version_number,
        'loaders': loaders
    })
    
    modpack_info["dependencies"] = dependencies
    
    with open(file_path, 'w') as file:
        json.dump(modpack_info, file, indent=4)



# Ajouter les dependances dans le fichier "List_dependencies.json"
def add_dependencies(name, version_id, version_number, loaders, version):
    file_path = f"app/data/version/{version}_version.json"
    update_dependencies_file(file_path, name, version_id, version_number, loaders)



# Extraire les noms et versions des dependances avec le "version_id"
def get_name_and_version(version_id, dependency, headers, version):

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

        add_dependencies(name, version_id, version_number, loaders, version)


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

        add_dependencies(name, version_id, version_number, loaders, version)  # Ajouter les dependances au fichier List_dependencies.json


# Extraire les dependances avec entry
def get_dependencies(dependencies, headers, version):
    for dependency in dependencies: 

        version_id = dependency.get("version_id", [])   # Extraire l'ID de la version

        get_name_and_version(version_id, dependency, headers, version) # Extraire le nom, la version_number et le loaders


def find_dependencies(data, headers, versions_mapping):
    # `versions_mapping` est un dictionnaire : {"last": last_version, "new": new_version}
    counter = 0

    for entry in data:
        modpack_version = entry.get("version_number", [])
        for version_name, version_list in versions_mapping.items():
            if modpack_version in version_list:
                dependencies = entry.get("dependencies", [])
                get_dependencies(dependencies, headers, version_name)
                counter += 1

    if counter == 2:
        print("Fin de la recherche des dépendances !")
    elif counter > 2:
        print("Erreur de version")

# Nettoyer le fichier List_dependencies.json    
def clean_file(file_path):
    with open(file_path, "w") as file: # Ouvrir le fichier en mode ecriture
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
        print("Version introuvable !")

    clean_file(file_path = "app/data/version/last_version.json")   # Nettoyer le fichier "last_version.json"
    clean_file(file_path = "app/data/version/new_version.json")    # Nettoyer le fichier "new_version.json"

    find_dependencies(data, headers, {"last": last_version, "new": new_version}) # Initialiser le fichier "List_dependencies.json"


# Recuperer les informations du formulaire dans template/index.html
@dependencies.route("/submit", methods=["POST"]) # Recuperer les informations
def submit():

    last_version = request.form.get("last_version") # Ancienne version du modpack
    new_version = request.form.get("new_version") # Nouvelle version du modpack

    id = request.form.get("id") # ID du modpack

    initialize_id(id, last_version, new_version) # Initialiser le fichier "List_dependencies.json"

    return f"Vous avez entré : {last_version} et {new_version} et {id}"