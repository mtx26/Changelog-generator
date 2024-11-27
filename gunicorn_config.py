import os
from dotenv import load_dotenv

# Charger les variables d'environnement
load_dotenv()

# Configuration de base
SECRET_KEY = os.getenv("SECRET_KEY", "default_dev_secret_key")
DEBUG = os.getenv("DEBUG", "False").lower() == "true"
DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///app.db")
SESSION_COOKIE_SECURE = os.getenv("SESSION_COOKIE_SECURE", "False").lower() == "true"
SESSION_COOKIE_HTTPONLY = True
API_BASE_URL = os.getenv("API_BASE_URL", "https://api.example.com")
LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO").upper()

# Configuration spécifique à l'environnement
FLASK_ENV = os.getenv("FLASK_ENV", "development").lower()

if FLASK_ENV == "development":
    DEBUG = True
    DATABASE_URL = "sqlite:///dev.db"
elif FLASK_ENV == "production":
    DEBUG = False
    SESSION_COOKIE_SECURE = True
else:
    raise ValueError(f"Environnement Flask invalide : '{FLASK_ENV}'.")
'''
ip = os.getenv("GUNICORN_IP")  # Valeur par défaut si la variable est absente
port = os.getenv("GUNICORN_PORT")   # Valeur par défaut si la variable est absente

if ip is not "none" and port is not "none":
    bind = f"{ip}:{port}"
    '''

# Configuration Gunicorn

workers = int(os.getenv("WORKERS", "3"))
threads = int(os.getenv("THREADS", "2"))
timeout = int(os.getenv("TIMEOUT", "30"))
loglevel = LOG_LEVEL.lower()  # Utilise le niveau de log configuré.

# Journaux
accesslog = "-"  # Journal des requêtes (STDOUT)
errorlog = "-"   # Journal des erreurs (STDOUT)
"""

# Vérifier si SSL doit être activé
ssl_enabled = os.getenv("SSL_ENABLED", "False").lower() == "true"

# Spécifier les chemins des certificats
certfile = os.path.join(os.path.dirname(__file__), 'certs', 'server.crt')  # Remplace 'server.crt' par ton fichier de certificat
keyfile = os.path.join(os.path.dirname(__file__), 'certs', 'server.key')  # Remplace 'server.key' par ta clé privée

# Vérifier si les fichiers de certificat existent et si SSL est activé
if ssl_enabled and os.path.exists(certfile) and os.path.exists(keyfile):
    ssl_options = {
        "certfile": certfile,
        "keyfile": keyfile,
    }
    print("SSL activé avec les certificats.")
    # Passer ces options à Gunicorn ou à votre serveur
else:
    ssl_options = None
    print("SSL désactivé ou certificat/clé manquants.")
    """