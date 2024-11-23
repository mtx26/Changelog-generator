import requests
import json

# URL de l'API Modrinth pour la listes des versions
id = "YcOwVsza"
version_url = f"https://api.modrinth.com/v2/project/{id}/version"

# token d'authentification
acces_token = "mrp_fOcLmXf0VpeXx6If4AcygZ8Jj4tRYMYrXI6wuKOJSNGmlqn01wYRAF6LfTuL"

headers = {
    "User-Agent": "mtx26/changelog_generator/1.0.0 (mtx_26@outlook.be)"
}


reponse = requests.get(version_url, headers=headers)

# Vérifier si la requête a réussi
if reponse.status_code == 200: 
    data = reponse.json()
else:
    print(f"Error: {reponse.status_code}") 

# Enregistrer les dependances dans le fichier dependencies.json
with open("dependencies.json", "w") as json_file: 
    json.dump(data, json_file, indent=4)

    print("Dependencies data saved to 'dependencies.json'")

# Nettoyer le fichier List_dependencies.json
with open("List_dependencies.json", "w") as file:
    file.write("{}")



def add_dependencies(modpack_version, name, version_id, version_number, loaders):
    
    with open('List_dependencies.json', 'r') as file:
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



    with open('List_dependencies.json', 'w') as file:
        json.dump(modpack_info, file, indent=4)



# Extraire les noms et versions des dependances avec le version_id
def get_name_and_version(version_id, modpack_version, dependency):
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
def get_dependencies(dependencies, modpack_version):
    for dependency in dependencies:
        # print(dependency)
        #Extraire l'ID de la version
        version_id = dependency.get("version_id", [])

        get_name_and_version(version_id, modpack_version, dependency)





counter = 0

#Extraire les noms et versions des dependances
for entry in data:

    #Limite des 2 dernières versions
    if counter == 2:
        break 
    
    #Extraire la version du Modpack
    modpack_version = entry.get("version_number", [])

    #Extraire les dependances
    dependencies  =  entry.get("dependencies", [])

    get_dependencies(dependencies, modpack_version)

    counter += 1
print("Done")






