window.onerror = function(message, source, lineno, colno, error) {
    console.log('Erreur capturée : ', message);
    const errorData = {
        message: message,
        url: window.location.href,
        fileName: source,
        lineNumber: lineno,
        columnNumber: colno,
        errorObj: error ? error.stack : null,
        userAgent: navigator.userAgent
    };

    fetch('/errors/js/error', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(errorData),
    });
    console.log('Erreur sauvegardé ');

    // Retourne false pour que l'erreur soit également affichée dans la console du navigateur
    return false;
};
