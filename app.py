from app import create_app # Importer l'application Flask

app = create_app() # Créer l'application Flask

# Lancer l'application Flask
if __name__ == "__main__":
    app.run(debug=True)
