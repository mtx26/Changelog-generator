function copyText(elementId, buttonId) {
    // Récupère l'élément contenant le texte à copier
    const textElement = document.getElementById(elementId);

    // Essaie de copier le texte dans le presse-papiers
    navigator.clipboard.writeText(textElement.textContent).then(() => {
        // Si la copie réussit, met à jour le texte du bouton
        const button = document.getElementById(buttonId);
        button.textContent = 'Copié!';
    }).catch((err) => {
        // Si une erreur se produit, affiche un message d'erreur
        console.error('Erreur lors de la copie:', err);
        alert('Une erreur est survenue lors de la copie.');
    });
}
