import os
from dotenv import load_dotenv

# Charger les variables d'environnement depuis un fichier .env
load_dotenv()

class Config:
    # Récupérer la clé secrète de l'environnement, avec une valeur par défaut pour le développement
    SECRET_KEY = os.getenv("SECRET_KEY", "default_dev_secret_key")
    
    # Autres configurations potentielles
    DEBUG = os.getenv("DEBUG", "False").lower() == "true"  # Activer/désactiver le mode débogage
    DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///app.db")  # URL de connexion à la base de données
    API_BASE_URL = os.getenv("API_BASE_URL", "https://api.example.com")  # Exemple d'API externe
