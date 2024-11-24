from app import create_app # Importer l'application Flask
import os

app = create_app() # Créer l'application Flask

# Lancer l'application Flask
if __name__ == "__main__":
    port = int(os.environ.get('PORT', 5000))  # Utiliser le port défini par Render, ou 5000 par défaut
    app = create_app()
    app.run(host='3.75.158.163', port=port, debug=True)