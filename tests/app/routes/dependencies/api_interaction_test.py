import requests

# Fonction pour obtenir les versions du modpack
def get_project_versions(id, headers):

    url = f"https://api.modrinth.com/v2/project/{id}/version"    # URL de l'API Modrinth pour la listes des versions
    try:
        reponse = requests.get(url, headers=headers)    # Obtenir les données de l'API
        if reponse.status_code == 200: 
            return reponse.json()
        else:
            # La listes des versions n'a pas pu étre obtenue
            print(f"Erreur: {reponse.status_code} lors de la recupération de la listes des versions.")
            return None
        
    # La listes des versions n'a pas pu étre obtenue
    except requests.exceptions.RequestException as e:
        print(f"Error: {e} lors de la recupération de la listes des versions.")
        return None


# Fonction pour obtenir les dependances du modpack
def get_depenencies_versions(id, headers):

    url = f"https://api.modrinth.com/v2/version/{id}"    # URL de l'API Modrinth pour la listes des versions
    try:
        reponse = requests.get(url, headers=headers)    # Obtenir les données de l'API
        if reponse.status_code == 200: 
            return reponse.json()
        else:
            # Les données des dependances n'a pas pu étre obtenue
            print(f"Erreur: {reponse.status_code} lors de la recupération des données des dependances.")
            return None
        
    # Les données des dependances n'a pas pu étre obtenue
    except requests.exceptions.RequestException as e:
        print(f"Error: {e} lors de la recupération des données des dependances.")
        return None