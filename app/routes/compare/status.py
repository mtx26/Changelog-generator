# Indexation des dépendances par project_id
from app.routes.dependencies.__init__ import *



# Comparaison des dépendances
def status(data):

    last_dependencies = data['last']
    new_dependencies = data['new']

    last_indexed = {}
    for dep in last_dependencies['dependencies']:
        project_id = dep['project_id']  # Extraire la clé
        last_indexed[project_id] = dep  # Ajouter la clé et la valeur

    new_indexed = {}
    for dep in new_dependencies['dependencies']:
        project_id = dep['project_id']  # Extraire la clé
        new_indexed[project_id] = dep  # Ajouter la clé et la valeur
    changelog = ""

    # Détection des ajouts, suppressions, et mises à jour
    for project_id in new_indexed:
        if project_id not in last_indexed:
            changelog += "## Added\n"
            changelog += f"- **{new_indexed[project_id]['name']}**\n"

    for project_id in last_indexed:
        if project_id not in new_indexed:
            changelog += "## Removed\n"
            changelog += f"- **{last_indexed[project_id]['name']}**\n"
    print(changelog)

    for project_id in last_indexed:
        if project_id in new_indexed:
            if last_indexed[project_id]['version_number'] != new_indexed[project_id]['version_number']:
                changelog += "## Updated\n"
                changelog += f"- **{last_indexed[project_id]['name']}**\n"
                changelog += f"   - Old Version:  {last_indexed[project_id]['version_number']}\n"
                changelog += f"   - New Version:  {new_indexed[project_id]['version_number']}\n"
    print(changelog)
    return changelog
