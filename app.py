from app import create_app  # Importer la fonction pour créer l'application Flask
import os
from config import AppConfig  # Importer la configuration de l'application
import logging

# Créer l'application Flask avec la configuration appropriée
app = create_app()
app.config.from_object(AppConfig)

# Fonction pour valider les configurations essentielles
def validate_config():
    """Vérifie si les configurations critiques sont définies."""
    if not app.config.get("SECRET_KEY"):
        raise RuntimeError("La clé secrète (SECRET_KEY) n'est pas définie !")
    
    # Vérification conditionnelle si une base de données est nécessaire
    database_url = app.config.get("DATABASE_URL")
    if database_url is None or database_url.startswith("placeholder"):
        print("Attention : aucune base de données n'est configurée. L'application fonctionnera en mode sans base.")

validate_config()

if __name__ == "__main__":
    # Récupérer le port et le mode debug depuis la configuration
    port = int(os.environ.get("PORT", 4000))
    debug = app.config.get("DEBUG", False)

    # Configurer le niveau des logs
    log_level = app.config.get("LOG_LEVEL", "INFO")
    logging.basicConfig(level=getattr(logging, log_level))

    
    # Afficher les informations sur l'environnement
    env = os.environ.get("FLASK_ENV", "development")
    print(f"Lancement de l'application en mode '{env}'")
    print(f"Port : {port}, Debug : {debug}, Niveau des logs : {log_level}")

    # Lancer l'application Flask
    app.run(host="0.0.0.0", port=port, debug=debug)