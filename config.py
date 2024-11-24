import os
from dotenv import load_dotenv

load_dotenv()

class Config:
    """Configuration générale pour toutes les environnements."""
    SECRET_KEY = os.getenv("SECRET_KEY", "default_dev_secret_key")
    DEBUG = os.getenv("DEBUG", "False").lower() == "true"
    DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///app.db")
    SESSION_COOKIE_SECURE = os.getenv("SESSION_COOKIE_SECURE", "False").lower() == "true"
    SESSION_COOKIE_HTTPONLY = True
    API_BASE_URL = os.getenv("API_BASE_URL", "https://api.example.com")
    LOG_LEVEL = os.getenv("LOG_LEVEL", "INFO").upper()

class DevelopmentConfig(Config):
    """Configuration utilisée pendant le développement."""
    DEBUG = True
    DATABASE_URL = "sqlite:///dev.db"

class ProductionConfig(Config):
    """Configuration utilisée en production."""
    DEBUG = False
    SESSION_COOKIE_SECURE = True

config_map = {
    "development": DevelopmentConfig,
    "production": ProductionConfig,
}
CURRENT_ENV = os.getenv("FLASK_ENV", "development").lower()
if CURRENT_ENV not in config_map:
    raise ValueError(f"Environnement Flask invalide : '{CURRENT_ENV}'. "
                     f"Les choix valides sont : {', '.join(config_map.keys())}")
AppConfig = config_map[CURRENT_ENV]
