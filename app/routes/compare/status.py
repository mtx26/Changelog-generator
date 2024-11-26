# Indexation des dépendances par project_id
from app.routes.dependencies.__init__ import *


# Comparaison des dépendances
def status():
    last_dependencies = open_file("app/data/version/last_version.json")
    new_dependencies = open_file("app/data/version/new_version.json")

    # Explication de l'indexation des dépendances par project_id
    # old_indexed = {}
    # for dep in old_dependencies["dependencies"]:
        # project_id = dep["project_id"]
        # old_indexed[project_id] = dep

    last_indexed = {dependency["project_id"]: dependency for dependency in last_dependencies["dependencies"]}
    new_indexed = {dependency["project_id"]: dependency for dependency in new_dependencies["dependencies"]}

    # Détection des ajouts, suppressions, et mises à jour
    added = [
        {"project_id": project_id, "new_version": new_indexed[project_id]["version_number"]}
        for project_id in new_indexed if project_id not in last_indexed # or last_indexed[project_id]["version_number"] != new_indexed[project_id]["version_number"]
    ]

    removed = [
        {"project_id": project_id, "old_version": last_indexed[project_id]["version_number"]}
        for project_id in last_indexed if project_id not in new_indexed # or last_indexed[project_id]["version_number"] != new_indexed[project_id]["version_number"]
    ]

    updated = [
        {
            "project_id": project_id,
            "old_version": last_indexed[project_id]["version_number"],
            "new_version": new_indexed[project_id]["version_number"]
        }
        for project_id in last_indexed
        if project_id in new_indexed and last_indexed[project_id]["version_number"] != new_indexed[project_id]["version_number"]
    ]

    # Résultat
    print("Ajouts :")
    print(added)
    print("\nSuppressions :")
    print(removed)
    print("\nMises à jour :")
    print(updated)
