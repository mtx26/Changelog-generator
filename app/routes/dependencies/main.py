import requests
import json
from flask import Flask, request, render_template, Blueprint

from app.routes.dependencies.api_interaction import get_project_versions, get_depenencies_versions, get_project_info
from app.routes.dependencies.file_operations import clean_file, write_file, open_file, sort_data


dependencies = Blueprint("dependencies", __name__) # Créer un blueprint "dependencies"

@dependencies.route("/")
def index():
    return render_template("index.html") # Afficher la page "index.html"


# Ecrire dans les fichiers "last_version.json" et "new_version.json les dependances de ces versions
def update_dependencies_file(file_path, name, version_id, project_id, version_number, loaders):
    
    modpack_info = open_file(file_path) # Ouvrir le fichier "file_path"

    dependencies = modpack_info.get("dependencies", []) # Extraire les dependances déja existantes dans le fichier

    dependencies.append({
        'name': name,                       # nom de la dependance
        'version_id': version_id,           # ID de la version de la dependance
        'project_id': project_id,           # ID du projet
        'version_number': version_number,   # version de la dependance
        'loaders': loaders                  # loaders de la dependance
    })
    
    modpack_info["dependencies"] = dependencies
    
    write_file(file_path, modpack_info) # Ecrire dans le fichier "file_path" le contenu "modpack_info"

    
    
        



# Extraire les informations de la dependance
def extract_dependencies_info(version_id, dependency, version_name):
    file_path = f"app/data/version/{version_name}_version.json"

    if version_id == None: # Verifier si le "version_id" est vide

        # Si le "version_id" est vide, 
        # extraire le "file_name",
        # remplacer le "version_id" et le "name" par le "file_name" moins le .jar

        file_name = dependency.get("file_name", []) # Extraire le "file_name"
        file_name = file_name[:-4] if file_name.endswith(".jar") else file_name # Retirer le .jar du "file_name"

        update_dependencies_file(
            file_path,  # Chemin du fichier pour cette version du modpack (file_path)
            file_name,  # Nom de la dependance (name)
            file_name,  # ID de la version de la dependance (version_id)
            "none",     # version du projet de la dependance (project_id)
            "none",     # version de la dependance (version_number)
            "none"      # loaders de la dependance (loaders)
            )


    # Extraire les données de la version de la dependance pour ceux dont le "version_id" n'est pas vide
    else:
        API_dependencies_versions = get_depenencies_versions(version_id)

        project_id = API_dependencies_versions.get("project_id", [])
    
        name =  get_project_info(project_id).get("title", [])
       
        if API_dependencies_versions: # Vérifier si la requête a réussi
            update_dependencies_file(
                file_path,                      # Chemin du fichier pour cette version du modpack (file_path)
                name,                           # Nom de la dependance (name)
                version_id,                     # ID de la version de la dependance (version_id)
                project_id,                     # version du projet de la dependance (project_id)
                API_dependencies_versions.get("version_number", []), # version de la dependance (version_number)
                API_dependencies_versions.get("loaders", []),        # loaders de la dependance (loaders)
                )


# Extraire les dependances 
def get_dependencies(list_dependencies, version_name):
    for dependency in list_dependencies: 

        version_id = dependency.get("version_id", [])   # Extraire l'ID de la version

        extract_dependencies_info(version_id, dependency, version_name) # Extraire le nom, la version_number et le loaders


# Extraire les dependances des versions du modpack : "versions_mapping"
def find_dependencies(Api_project_versions, versions_mapping):
    # "versions_mapping" est un dictionnaire : {"last": last_version, "new": new_version}
    counter = 0

    for project_version in Api_project_versions:

        modpack_version = project_version.get("version_number", []) # Extraire la version
        
        # Parcourt un dictionnaire versions_mapping, où chaque clé (version_name) 
        # correspond à un type de version, et chaque valeur (version)
        #
        # Exemple si versions_mapping = {"last": 1.0.7, "new": 1.0.8} :
        #       - Vertion_name = last , version = 1.0.7
        #       - Vertion_name = new , version = 1.0.8

        for version_name, version in versions_mapping.items():

            if modpack_version in version: # Verifier si la version est dans le dictionnaire
                
                list_dependencies = project_version.get("dependencies", []) # Extraire les dependances de la version
                get_dependencies(list_dependencies, version_name) 
                counter += 1

        # Limiter la recherche, une fois les deux versions trouvées
        if counter == 2:
            print("Fin de la recherche des dépendances !")
            break
        elif counter > 2:
            print("Erreur de version")
            break


# Fonction principale pour la recherche des dependances
def main(project_id, last_version, new_version):
        
    API_project_versions = get_project_versions(project_id)

    if API_project_versions:
        clean_file(file_path = "app/data/version/last_version.json")   # Nettoyer le fichier "last_version.json"
        clean_file(file_path = "app/data/version/new_version.json")    # Nettoyer le fichier "new_version.json"

        find_dependencies(API_project_versions, {"last": last_version, "new": new_version}) # Initialiser le fichier "List_dependencies.json"

    else:
        print("Error: Project not found")


# Fonction de fin et de tris
def finish_last_version():
    last_version_file = open_file(file_path = "app/data/version/last_version.json") # Ouvrir le fichier "last_version.json"
    last_version_file = sort_data(last_version_file, "dependencies", "name") # Trier la variable "last_version_file" par odre alphabétique de la clé "name" sous la clé "key_main"
    clean_file(file_path = "app/data/version/last_version.json")   # Nettoyer le fichier "last_version.json"
    write_file(file_path = "app/data/version/last_version.json", data = last_version_file) # Ecrire le contenu du fichier "last_version.json"
    last_version_file_json = json.dumps(last_version_file, indent=4) # Ecrire le contenu du fichier "last_version.json" dans un JSON

    return last_version_file_json
def finish_new_version():
    new_version_file = open_file(file_path = "app/data/version/new_version.json")  # Ouvrir le fichier "new_version.json"
    new_version_file = sort_data(new_version_file, "dependencies", "name") # Trier la variable "new_version_file" par odre alphabétique de la clé "name" sous la clé "key_main"
    clean_file(file_path = "app/data/version/new_version.json")    # Nettoyer le fichier "new_version.json"
    write_file(file_path = "app/data/version/new_version.json", data = new_version_file)  # Ecrire le contenu du fichier "new_version.json"
    new_version_file_json = json.dumps(new_version_file, indent=4)  # Ecrire le contenu du fichier "new_version.json" dans un JSON

    return new_version_file_json

# Recuperer les informations du formulaire dans template/index.html
@dependencies.route("/submit", methods=["POST"]) # Recuperer les informations
def submit():

    last_version = request.form.get("last_version") # Ancienne version du modpack
    new_version = request.form.get("new_version") # Nouvelle version du modpack

    project_id = request.form.get("id") # ID du modpack

    main(project_id, last_version, new_version) # Initialiser le fichier "List_dependencies.json"

    last_version_file_json = finish_last_version() # Trier les fichiers et les envoyer dans un JSON
    new_version_file_json = finish_new_version()


    return render_template("submit_result.html", 
                           last_version=last_version, 
                           new_version=new_version, 
                           last_version_file=last_version_file_json, 
                           new_version_file=new_version_file_json
                           )
