import json

# Nettoyer le fichier "file_path"  
def clean_file(file_path):
    try:
        with open(file_path, "w") as file: # Ouvrir le fichier en mode ecriture
            file.write("{}") # Supprimer le contenu
    except Exception as e:
        print(f"Erreur : {e}, impossible de charger le fichier {file_path}.") # Le fichier n'a pas pu etre nettoyer

# Ecrire dans le fichier "file_path"
def write_file(file_path, data):
     try:
        with open(file_path, 'w') as file:
            json.dump(data, file, indent=4)
     except Exception as e:
        print(f"Erreur : {e}, impossible de charger le fichier {file_path}.") # Le fichier n'a pas pu etre ecrit

# Ouvrir le fichier "file_path"
def open_file(file_path):
    try:
        with open(file_path, 'r') as file:
            data = json.load(file)
        return data
    except Exception as e:
        print(f"Erreur : {e}, impossible de charger le fichier {file_path}.") # Le fichier n'a pas pu etre ouvert

# Trier la variable "data" par odre alphabétique de la clé "name" sous la clé "key_main"
def sort_data(data_sort, key_main, key_sorted):
    try:
        # Trier la liste des dépendances par la clé "name"
        data_sort[key_main] = sorted(data_sort[key_main], key=lambda x: x[key_sorted].lower())
        return data_sort
    except Exception as e:
        print(f"Erreur : {e}, impossible de trier la variable {data_sort}.")
