function updateProgressBar(percentage) {
    const progressBar = document.querySelector('.progress-bar');
    progressBar.style.width = percentage + '%';
    progressBar.setAttribute('aria-valuenow', percentage);
    progressBar.textContent = percentage + '%';
}

function copyText(elementId, buttonId) {
    // Get the text content from the specified element
    const textToCopy = document.getElementById(elementId).innerText;
    console.log('Copying text:', textToCopy); // Log the text being copied

    // Use the Clipboard API to copy the text
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(textToCopy).then(() => {
            const button = document.getElementById(buttonId);
            button.innerText = 'Copié!';
            setTimeout(() => {
                button.innerText = 'Copier le code';
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy using Clipboard API: ', err);
            fallbackCopyTextToClipboard(textToCopy, buttonId);
        });
    } else {
        fallbackCopyTextToClipboard(textToCopy, buttonId);
    }
}

function fallbackCopyTextToClipboard(text, buttonId) {
    const tempTextArea = document.createElement('textarea');
    tempTextArea.value = text;
    document.body.appendChild(tempTextArea);
    tempTextArea.select();
    document.execCommand('copy');
    document.body.removeChild(tempTextArea);

    const button = document.getElementById(buttonId);
    button.innerText = 'Copié!';
    setTimeout(() => {
        button.innerText = 'Copier le code';
    }, 2000);
}
