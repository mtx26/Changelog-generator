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
        print("L'URL de l'API Modrinth n'est pas configurée. L'application fonctionnera en mode sans API.")

# Valider la configuration
try:
    validate_config()
except RuntimeError as e:
    logging.error(f"Erreur de configuration : {str(e)}")
    exit(1)  # Quitter l'application si la validation échoue

# Configurer les logs
log_level = app.config.get("LOG_LEVEL", "INFO").upper()
logging.basicConfig(level=getattr(logging, log_level), format='%(asctime)s - %(levelname)s - %(message)s')

# Lancer l'application Flask est désormais pris en charge par Gunicorn
if __name__ == "__main__":
    pass
