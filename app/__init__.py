from flask import Flask

# Créer une application Flask
def create_app():
    app = Flask(__name__)
    

    from .routes.dependencies import dependencies    # Import le blueprint "dependencies"

    app.register_blueprint(dependencies)    # Enregistrer le blueprint "dependencies" dans l'application

    return app 