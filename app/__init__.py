from flask import Flask

def create_app():
    app = Flask(__name__)
    
    # Import des routes ou blueprints
    from .routes.dependencies import dependencies

    app.register_blueprint(dependencies)

    return app