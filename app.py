import os
from app import create_app  # Importer la fonction pour créer l'application Flask
from config import AppConfig  # Importer la configuration de l'application
import logging

# Créer l'application Flask avec la configuration appropriée
app = create_app()
app.config.from_object(AppConfig)

# Fonction pour valider les configurations essentielles
def validate_config():
    """Vérifie si les configurations essentielles sont définies."""
    if not app.config.get("SECRET_KEY"):
        raise RuntimeError("La clé secrète (SECRET_KEY) n'est pas définie !")
    
    # Vérification de la base de données
    database_url = app.config.get("DATABASE_URL")
    if not database_url or database_url.startswith("sqlite://"):
        print("Attention : aucune base de données n'est configurée. L'application fonctionnera en mode sans base.")
    
    # Vérification de l'URL de l'API
    api_base_url = app.config.get("API_BASE_URL")
    if not api_base_url or not api_base_url.startswith("https://"):
        print("Attention : aucune URL d'API n'est configurée. L'application fonctionnera en mode sans API.")

# Valider la configuration
try:
    validate_config()
except RuntimeError as e:
    logging.error(f"Erreur de configuration : {str(e)}")
    exit(1)  # Quitter l'application si la validation échoue

# Configurer les logs
log_level = app.config.get("LOG_LEVEL", "INFO").upper()
logging.basicConfig(level=getattr(logging, log_level), format='%(asctime)s - %(levelname)s - %(message)s')

# Ne pas appeler app.run() ici, Gunicorn s'en charge
if __name__ == "__main__":
    # Utiliser le port défini dans les variables d'environnement, sinon 4000 par défaut
    port = int(os.environ.get("PORT", 4000))  
    
    # Activer ou désactiver le mode debug en fonction de la configuration
    debug = app.config.get("DEBUG", False)
    
    # Lancer l'application Flask
    app.run(host="0.0.0.0", port=port, debug=debug)