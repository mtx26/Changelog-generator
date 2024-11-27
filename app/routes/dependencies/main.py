import requests
import json
import asyncio
from asyncio import Semaphore
import httpx
from flask import Flask, request, render_template, Blueprint

from app.routes.dependencies.__init__ import *
from app.routes.compare.__init__ import status



dependencies = Blueprint("dependencies", __name__) # Créer un blueprint "dependencies"

@dependencies.route("/")
def index():
    return render_template("index.html") # Afficher la page "index.html"


async def fetch_with_retry(client, url, retries=3, delay=1):
    """Effectue une requête avec des tentatives en cas de réponse 429."""
    from app.routes.dependencies.config import headers
    for attempt in range(retries):
        try:
            response = await client.get(url, headers=headers)
            response.raise_for_status()
            return response.json()
        except httpx.HTTPStatusError as e:
            if e.response.status_code == 429 and attempt < retries - 1:
                print(f"Limite atteinte, nouvelle tentative dans {delay} seconde(s)...")
                await asyncio.sleep(delay)
                delay *= 2  # Augmente progressivement le délai
            else:
                raise

from asyncio import Semaphore

async def add_name_version_number_to_dependencies(data):
    semaphore = Semaphore(100)  # Limite à 50 requêtes simultanées
    project_cache = {}
    version_cache = {}

    async def fetch_dependency_info(client, dependency):
        async with semaphore:

            project_id = dependency.get("project_id")
            version_id = dependency.get("version_id")

            if project_id is not None:
                if project_id in project_cache:
                    name, loaders = project_cache[project_id]
                else:
                    project_url = f"https://api.modrinth.com/v2/project/{project_id}"
                    project_info = await fetch_with_retry(client, project_url)
                    name = project_info.get("title", "Nom non trouvé")
                    loaders = project_info.get("loaders", "Aucun loaders")
                    project_cache[project_id] = (name, loaders)

                if version_id in version_cache:
                    version_number = version_cache[version_id]
                else:
                    version_url = f"https://api.modrinth.com/v2/version/{version_id}"
                    version_info = await fetch_with_retry(client, version_url)
                    version_number = version_info.get("version_number", "Numéro de version non trouvé")
                    version_cache[version_id] = version_number

                dependency["name"] = name
                dependency["loaders"] = loaders
                dependency["version_number"] = version_number
            else:
                file_name = dependency["file_name"]
                dependency["name"] = file_name[:-4] if file_name.endswith(".jar") else file_name
                dependency["loaders"] = None
                dependency["version_number"] = None

    async with httpx.AsyncClient() as client:
        tasks = []
        for version_key in ['last', 'new']:
            version_data = data[version_key]
            dependencies = version_data["dependencies"]

            for dependency in dependencies:
                tasks.append(fetch_dependency_info(client, dependency))

        await asyncio.gather(*tasks)


    return data




# Extraire les dependances des versions du modpack : "versions_mapping"
def extract_dependencies_for_two_versions(API_project_versions, last_version, new_version):
    # "versions_mapping" est un dictionnaire : {"last": last_version, "new": new_version}
    if not API_project_versions:
        print("Erreur, le project n'a pas été trouvé")
        return
    
    versions_dependencies = {
        'last': {
            'version': last_version,
            'dependencies': []
        },
        'new': {
            'version': new_version,
            'dependencies': []
        }
    }
        
    for project_version in API_project_versions:
        modpack_version = project_version.get("version_number")

        if modpack_version == last_version or modpack_version == new_version:
            list_dependencies = project_version.get("dependencies", [])

        if modpack_version == last_version:
            versions_dependencies["last"]["dependencies"].extend(list_dependencies)

        if modpack_version == new_version:
            versions_dependencies["new"]["dependencies"].extend(list_dependencies)
    if not versions_dependencies['last']['dependencies'] or not versions_dependencies['new']['dependencies']:
        print("Erreur : Pas de dependence trouver")
        return None
    
    return versions_dependencies

# Fonction principale pour la recherche des dependances
def API_search_project(project_id):
        
    API_project_versions = get_project_versions(project_id)

    if API_project_versions:
        return API_project_versions
    else:
        print("Error: Project not found")
        return None

def sort_data_json(data):
    data["last"] = sort_data(data["last"], "dependencies", "name")
    data["new"] = sort_data(data["new"], "dependencies", "name")
    return data

# Recuperer les informations du formulaire dans template/index.html
@dependencies.route("/submit", methods=["POST"]) # Recuperer les informations
def submit():
    
    last_version = request.form.get("last_version") # Ancienne version du modpack
    new_version = request.form.get("new_version") # Nouvelle version du modpack

    project_id = request.form.get("id") # ID du modpack

    API_project_versions = API_search_project(project_id) # Initialiser le fichier "List_dependencies.json"

    versions_dependencies = extract_dependencies_for_two_versions(API_project_versions, last_version, new_version)
   
    data = asyncio.run(add_name_version_number_to_dependencies(versions_dependencies))

    data = sort_data_json(data)

    write_file(file_path="app/data/version/project_info.json", data=data)

    last_version_file = json.dumps(data["last"], indent=4)
    new_version_file = json.dumps(data["new"], indent=4)

    changelog = status(data)



    return render_template("submit_result.html", 
                           last_version=last_version, 
                           new_version=new_version,
                           last_version_file = last_version_file,
                           new_version_file =  new_version_file,
                           changelog = changelog
                           )

