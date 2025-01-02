function updateProgressBar(percentage) {
    const progressBar = document.querySelector('.progress-bar');
    progressBar.style.width = percentage + '%';
    progressBar.setAttribute('aria-valuenow', percentage);
    progressBar.textContent = percentage + '%';
}

function copyText(elementId, buttonId) {
    var textToCopy = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(textToCopy).then(function() {
        alert('Texte copié dans le presse-papiers !');
    }, function(err) {
        console.error('Erreur lors de la copie : ', err);
    });
}