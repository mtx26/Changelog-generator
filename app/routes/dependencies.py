import requests
import json
from flask import Flask, request, render_template, Blueprint


dependencies = Blueprint("dependencies", __name__)

@dependencies.route("/")
def index():
    return render_template("index.html")



def add_dependencies(modpack_version, name, version_id, version_number, loaders):
    
    with open('app/data/list_dependencies.json', 'r') as file:
        modpack_info = json.load(file)
    
    if modpack_version not in modpack_info:
        modpack_info[modpack_version] = {
 
        }
        modpack_info[modpack_version][version_id] = {
            
            'name': name,
            'version_id': version_id,
            'version_number': version_number,
            'loaders': loaders

        }

    else:
        modpack_info[modpack_version][version_id] = {
            
            'name': name,
            'version_id': version_id,
            'version_number': version_number,
            'loaders': loaders

        }



    with open('app/data/list_dependencies.json', 'w') as file:
        json.dump(modpack_info, file, indent=4)



# Extraire les noms et versions des dependances avec le version_id
def get_name_and_version(version_id, modpack_version, dependency, headers):
    # print(version_id)
    if version_id == None:

        file_name = dependency.get("file_name", [])
        file_name = file_name[:-4] if file_name.endswith(".jar") else file_name

        name = file_name
        version_id = file_name       
        version_number = 'none'
        loaders = 'none'

        add_dependencies(modpack_version, name, version_id, version_number, loaders)

        #print(f"Version ID : {dependency}")
                                                                # Il faut extraire le file_name de dependency et retirer le .jar
    else:
        # Extraire les données de la version
        # URL de l'API Modrinth pour la version
        dependencies_version_url = f"https://api.modrinth.com/v2/version/{version_id}"
        # print(dependencies_version_url)

        reponse = requests.get(dependencies_version_url, headers=headers)

        # Vérifier si la requête a réussi
        if reponse.status_code == 200:
            data_version = reponse.json()
        else:
            print(f"Error: {reponse.status_code}")

        name = data_version.get("name")
        version_number = data_version.get("version_number", [])
        loaders = data_version.get("loaders", [])

        add_dependencies(modpack_version, name, version_id, version_number, loaders)



# Extraire les dependances avec entry
def get_dependencies(dependencies, modpack_version, headers):
    for dependency in dependencies:
        # print(dependency)
        #Extraire l'ID de la version
        version_id = dependency.get("version_id", [])

        get_name_and_version(version_id, modpack_version, dependency, headers)



def find_dependencies(data, headers, modpack_versions, counter = 0):
    #Extraire les noms et versions des dependances
    for entry in data:
            
        modpack_version = entry.get("version_number", [])

        #Vérifier si la version du modpack est dans la liste
        if modpack_version in modpack_versions:

            #Extraire les dependances
            dependencies  =  entry.get("dependencies", [])

            get_dependencies(dependencies, modpack_version, headers)
        else:
            print(f"Error: {modpack_version} not in {modpack_versions}")
        

        counter += 1

        if counter >= 2:
            break
    print("Done")



def initialize_id(id, version1, version2):
    # URL de l'API Modrinth pour la listes des versions
    version_url = f"https://api.modrinth.com/v2/project/{id}/version"


    # En-tete de la requête
    headers = {
        "User-Agent": "mtx26/changelog_generator/1.0.0 (mtx_26@outlook.be)"
    }

    # Obtenir les données de l'API
    reponse = requests.get(version_url, headers=headers)

    # Vérifier si la requête a réussi
    if reponse.status_code == 200: 
        data = reponse.json()
        print("Obtention des données de l'API Modrinth réussie !")
    else:
        print(f"Error: {reponse.status_code}") 

    modpack_versions = [version1, version2]

    # Nettoyer le fichier List_dependencies.json
    with open("app/data/list_dependencies.json", "w") as file:
        file.write("{}")

    find_dependencies(data, headers, modpack_versions)



@dependencies.route("/submit", methods=["POST"])
def submit():
    version1 = request.form.get("version1")
    version2 = request.form.get("version2")
    id = request.form.get("id")
    initialize_id(id, version1, version2)
    return f"Vous avez entré : {version1} et {version2} et {id}"