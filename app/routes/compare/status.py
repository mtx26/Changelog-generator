# Indexation des dépendances par project_id
from app.routes.dependencies.__init__ import *



# Comparaison des dépendances
def status(data):

    last_dependencies = data['last']
    new_dependencies = data['new']

    last_indexed = {}
    new_indexed = {}
    removed_projects_with_null_id = {}
    added_projects_with_null_id = {}

    for dep in last_dependencies['dependencies']:
        project_id = dep['project_id']  # Extraire la clé
        if project_id is None:
            name = dep['name']
            removed_projects_with_null_id[name] = dep
        else:
            last_indexed[project_id] = dep  # Ajouter la clé et la valeur

    for dep in new_dependencies['dependencies']:
        project_id = dep['project_id']  # Extraire la clé
        if project_id is None:
            name = dep['name']
            added_projects_with_null_id[name] = dep
        else:
            new_indexed[project_id] = dep  # Ajouter la clé et la valeur

    to_remove_from_removed = []
    to_remove_from_added = []

    for dep in removed_projects_with_null_id:
        for dep2 in added_projects_with_null_id:
            if dep == dep2:
                to_remove_from_removed.append(dep)
                to_remove_from_added.append(dep2)

    # Supprimer les éléments après avoir terminé l'itération
    for dep in to_remove_from_removed:
        removed_projects_with_null_id.pop(dep)

    for dep2 in to_remove_from_added:
        added_projects_with_null_id.pop(dep2)

    changelog = ""

    # Ajouter les projets "Added"
    added_projects = [project_id for project_id in new_indexed if project_id not in last_indexed]
    if added_projects:
        changelog += "## Added\n"
        for project_id in added_projects:
            changelog += f"- **{new_indexed[project_id]['name']}: {new_indexed[project_id]['version_number']}**\n"

    if added_projects == []:
        changelog += "## Added\n"
    for name in added_projects_with_null_id:
        changelog += f"- **{added_projects_with_null_id[name]['name']}**\n"

    # Ajouter les projets "Removed"Zx²x
    removed_projects = [project_id for project_id in last_indexed if project_id not in new_indexed]
    if removed_projects:
        changelog += "## Removed\n"
        for project_id in removed_projects:
            changelog += f"- **{last_indexed[project_id]['name']}**\n"

    if removed_projects == []:
        changelog += "## Removed\n"        
    for name in removed_projects_with_null_id:
        changelog += f"- **{removed_projects_with_null_id[name]['name']}**\n"

    # Ajouter les projets "Updated"
    updated_projects = [project_id for project_id in last_indexed if project_id in new_indexed and last_indexed[project_id]['version_number'] != new_indexed[project_id]['version_number']]
    if updated_projects:
        changelog += "## Updated\n"
        for project_id in updated_projects:
            changelog += f"- **{last_indexed[project_id]['name']}**\n"
            changelog += f"   - Old Version:  {last_indexed[project_id]['version_number']}\n"
            changelog += f"   - New Version:  {new_indexed[project_id]['version_number']}\n"




    return changelog
